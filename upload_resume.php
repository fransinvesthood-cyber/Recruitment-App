<?php
require 'vendor/autoload.php'; 
use Smalot\PdfParser\Parser;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Element\TextRun;

include('config.php');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
session_start();

// ---------------- Check Login ----------------
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = "You must be logged in.";
    $_SESSION['messageClass'] = "alert alert-danger";
    header("Location: applicant.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// ---------------- Upload Resume ----------------
if (!isset($_FILES['resume']) || $_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['message'] = "No resume uploaded or an error occurred.";
    $_SESSION['messageClass'] = "alert alert-danger";
    header("Location: applicant.php");
    exit();
}

$resume_type = strtolower(pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION));
$allowed_types = ['pdf', 'doc', 'docx'];
if (!in_array($resume_type, $allowed_types)) {
    $_SESSION['message'] = "Only PDF, DOC, and DOCX files are allowed.";
    $_SESSION['messageClass'] = "alert alert-danger";
    header("Location: applicant.php");
    exit();
}

$resume_filename = $_FILES['resume']['name'];
$resume_data = file_get_contents($_FILES['resume']['tmp_name']);

// ---------------- Save or Update Resume in DB ----------------
$check_stmt = $conn->prepare("SELECT resume_id FROM resume WHERE user_id = ?");
$check_stmt->bind_param("i", $user_id);
$check_stmt->execute();
$check_stmt->store_result();

if ($check_stmt->num_rows > 0) {
    $update_stmt = $conn->prepare("UPDATE resume SET resume_filename=?, resume=?, uploaded_at=NOW() WHERE user_id=?");
    $update_stmt->bind_param("ssi", $resume_filename, $resume_data, $user_id);
    $update_stmt->send_long_data(1, $resume_data);
    $update_stmt->execute();
    $update_stmt->close();
} else {
    $insert_stmt = $conn->prepare("INSERT INTO resume (user_id, resume_filename, resume) VALUES (?, ?, ?)");
    $insert_stmt->bind_param("iss", $user_id, $resume_filename, $resume_data);
    $insert_stmt->send_long_data(2, $resume_data);
    $insert_stmt->execute();
    $insert_stmt->close();
}
$check_stmt->close();

// ---------------- Extract Resume Text ----------------
function extractTextFromResume($filePath, $ext) {
    if ($ext === 'pdf') {
        $parser = new Parser();
        $pdf = $parser->parseFile($filePath);
        return $pdf->getText();
    } elseif ($ext === 'docx' || $ext === 'doc') {
        try {
            $phpWord = IOFactory::load($filePath);
        } catch (\Exception $e) {
            file_put_contents('gemini_debug.txt', "Word file load failed: ".$e->getMessage());
            return '';
        }
        $text = "";
        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                // If element exposes text directly, use it when it's a string
                if (method_exists($element, 'getText')) {
                    $val = $element->getText();
                    if (is_string($val)) {
                        $text .= $val . " ";
                        continue;
                    }
                    // Handle TextRun (container of inline elements)
                    if ($element instanceof TextRun) {
                        foreach ($element->getElements() as $child) {
                            if (method_exists($child, 'getText')) {
                                $childVal = $child->getText();
                                if (is_string($childVal)) $text .= $childVal . " ";
                            }
                        }
                        continue;
                    }
                    // Fallback: try casting if object implements __toString
                    if (is_object($val) && method_exists($val, '__toString')) {
                        $text .= (string)$val . " ";
                        continue;
                    }
                }
                // Some elements (like tables) don't have getText; try probing children if available
                if (method_exists($element, 'getElements')) {
                    foreach ($element->getElements() as $child) {
                        if (method_exists($child, 'getText')) {
                            $childVal = $child->getText();
                            if (is_string($childVal)) $text .= $childVal . " ";
                        }
                    }
                }
            }
        }
        return $text;
    }
    return '';
}

$resumeText = extractTextFromResume($_FILES['resume']['tmp_name'], $resume_type);
if (empty($resumeText)) {
    $_SESSION['message'] = "Failed to extract text from resume. Cannot parse.";
    $_SESSION['messageClass'] = "alert alert-danger";
    header("Location: applicant.php");
    exit();
}

// Trim overly long resumes (Gemini limit safeguard)
if (strlen($resumeText) > 8000) {
    $resumeText = substr($resumeText, 0, 8000);
}

// ---------------- Call Affinda or Gemini API ----------------
if (USE_AFFINDA) {
    require_once 'affinda_api.php';
    $affinda = new AffindaAPI();
    $parsingSuccess = $affinda->parseAndSaveResume($_FILES['resume']['tmp_name'], $resume_filename, $user_id);
} else {
    // Fallback to Gemini API with retry and local fallback
    function parseResumeWithGemini($resumeText, $apiKey) {
        $promptTemplate = <<<EOT
You are a resume parser. Extract structured data and return ONLY a valid JSON object, no extra text.
JSON format:
{
  "skills": { "technical":[], "soft":[] },
  "education": [ { "qualification":"", "institution":"", "year":"" } ],
  "work_experience": [ { "position":"", "company":"", "duration":"", "duties":"" } ]
}
Resume Text:
%s
EOT;

        $url = "https://generativelanguage.googleapis.com/v1/models/gemini-2.0-flash:generateContent?key=$apiKey";

        $maxRetries = 3;
        $waitSeconds = 2;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            $prompt = sprintf($promptTemplate, $resumeText);

            // Call API
            $response = @file_get_contents($url, false, stream_context_create([
                "http" => [
                    "method" => "POST",
                    "header" => "Content-Type: application/json",
                    "content" => json_encode([
                        "contents" => [[ "parts" => [["text" => $prompt]] ]]
                    ]),
                    "ignore_errors" => true
                ]
            ]));

            if (!$response) {
                file_put_contents("gemini_debug.txt", "API request failed (attempt $attempt): " . print_r(error_get_last(), true), FILE_APPEND);
                // Wait and retry
                if ($attempt < $maxRetries) {
                    sleep($waitSeconds);
                    $waitSeconds *= 2;
                    continue;
                }
                break;
            }

            // Save raw response for debugging
            file_put_contents("gemini_raw_response.json", $response);

            $parsed = json_decode($response, true);
            if (isset($parsed['error'])) {
                // Likely a 429 quota or other transient error
                file_put_contents("gemini_debug.txt", "API error (attempt $attempt): " . $response, FILE_APPEND);
                $code = $parsed['error']['code'] ?? null;
                if ($code == 429 && $attempt < $maxRetries) {
                    sleep($waitSeconds);
                    $waitSeconds *= 2;
                    continue;
                }
                // Non-retryable or maxed out
                break;
            }

            // Expecting 'candidates' structure
            if (!isset($parsed['candidates'][0]['content']['parts'][0]['text'])) {
                file_put_contents("gemini_debug.txt", "Unexpected API response format (attempt $attempt): " . $response, FILE_APPEND);
                // Give it another try if possible
                if ($attempt < $maxRetries) {
                    sleep($waitSeconds);
                    $waitSeconds *= 2;
                    continue;
                }
                break;
            }

            $rawText = $parsed['candidates'][0]['content']['parts'][0]['text'] ?? '';
            $rawText = preg_replace('/```json|```/i', '', $rawText);
            $rawText = trim($rawText);

            // Extract the first JSON object in the text
            $json = null;
            if (preg_match('/(\{(?:[^{}]|(?R))*\})/s', $rawText, $matches)) {
                $json = $matches[1];
            }

            if (!$json) {
                file_put_contents('gemini_debug.txt', "No JSON found in response (attempt $attempt):\n" . $rawText, FILE_APPEND);
                if ($attempt < $maxRetries) {
                    sleep($waitSeconds);
                    $waitSeconds *= 2;
                    continue;
                }
                break;
            }

            $data = json_decode($json, true);
            if (!$data) {
                file_put_contents('gemini_debug.txt', "Invalid JSON detected (attempt $attempt):\n" . $json, FILE_APPEND);
                if ($attempt < $maxRetries) {
                    sleep($waitSeconds);
                    $waitSeconds *= 2;
                    continue;
                }
                break;
            }

            // Success
            return $data;
        }

        // Retries exhausted or non-retryable error — fall back to a local heuristic parser
        file_put_contents('gemini_debug.txt', "Gemini parsing not available; falling back to local parser.", FILE_APPEND);
        return parseResumeLocally($resumeText);
    }

    // Simple local fallback parser (best-effort): extracts skills, education entries and experience durations
    function parseResumeLocally($text) {
        $textLower = strtolower($text);

        // Common skills lists (extend as needed)
        $technical = [
            'php','mysql','javascript','html','css','laravel','symfony','react','vue','node','python','django','flask','c#','java','sql','git'
        ];
        $soft = ['communication','leadership','team','management','problem solving','organization','adaptability','time management'];

        $foundTech = [];
        foreach ($technical as $t) {
            if (stripos($textLower, $t) !== false) $foundTech[] = $t;
        }
        $foundSoft = [];
        foreach ($soft as $s) {
            if (stripos($textLower, $s) !== false) $foundSoft[] = $s;
        }

        // Education: find degree keywords and optional year
        $education = [];
        if (preg_match_all('/(Bachelor(?:\'s)?|Master(?:\'s)?|B\.Sc|M\.Sc|BA|BS|MBA)[^\n,]*(\d{4})?/i', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $education[] = ['qualification' => trim($m[0]), 'institution' => '', 'year' => ($m[2] ?? '')];
            }
        }

        // Work experience: find durations like '2 years' or '2020-2023'
        $work = [];
        if (preg_match_all('/(\d{4})\s*[\-–]\s*(\d{4})/i', $text, $years, PREG_SET_ORDER)) {
            foreach ($years as $y) {
                $work[] = ['position' => '', 'company' => '', 'duration' => $y[0], 'duties' => ''];
            }
        } elseif (preg_match_all('/(\d+)\s+years?/i', $text, $yrs, PREG_SET_ORDER)) {
            foreach ($yrs as $r) {
                $work[] = ['position' => '', 'company' => '', 'duration' => $r[0], 'duties' => ''];
            }
        }

        return [
            'skills' => ['technical' => array_values(array_unique($foundTech)), 'soft' => array_values(array_unique($foundSoft))],
            'education' => $education,
            'work_experience' => $work
        ];
    }

    $data = parseResumeWithGemini($resumeText, GEMINI_API_KEY);
    $parsingSuccess = ($data !== null);

    // ---------------- Save Parsed Data ----------------
    if ($parsingSuccess) {
        // Skills
        if (!empty($data['skills'])) {
            $technical = implode(", ", $data['skills']['technical'] ?? []);
            $soft = implode(", ", $data['skills']['soft'] ?? []);

            $check = $conn->prepare("SELECT skill_id FROM skills WHERE user_id=?");
            $check->bind_param("i", $user_id);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $update = $conn->prepare("UPDATE skills SET technical_skills=?, soft_skills=?, created_at=NOW() WHERE user_id=?");
                $update->bind_param("ssi", $technical, $soft, $user_id);
                $update->execute();
                $update->close();
            } else {
                $insert = $conn->prepare("INSERT INTO skills (user_id, technical_skills, soft_skills) VALUES (?, ?, ?)");
                $insert->bind_param("iss", $user_id, $technical, $soft);
                $insert->execute();
                $insert->close();
            }
            $check->close();
        }

        // Education
        foreach ($data['education'] ?? [] as $edu) {
            $qualification = $edu['qualification'] ?? '';
            $institution  = $edu['institution'] ?? '';
            $year         = $edu['year'] ?? '';

            if ($qualification && $institution) {
                $insert = $conn->prepare("INSERT INTO qualifications (user_id, qualification_name, institution, year_completed) VALUES (?, ?, ?, ?)");
                $insert->bind_param("issi", $user_id, $qualification, $institution, $year);
                $insert->execute();
                $insert->close();
            }
        }

        // Work Experience
        foreach ($data['work_experience'] ?? [] as $exp) {
            $position = $exp['position'] ?? '';
            $company  = $exp['company'] ?? '';
            $duration = $exp['duration'] ?? '';
            $duties   = $exp['duties'] ?? '';

            if ($position && $company) {
                $insert = $conn->prepare("INSERT INTO work_experience (user_id, position, company_name, duration, duties) VALUES (?, ?, ?, ?, ?)");
                $insert->bind_param("issss", $user_id, $position, $company, $duration, $duties);
                $insert->execute();
                $insert->close();
            }
        }
    }
}

// ---------------- Handle Parsing Result ----------------
if ($parsingSuccess) {
    $apiName = USE_AFFINDA ? "Affinda" : "Gemini";
    $_SESSION['message'] = "✅ Resume uploaded successfully!!";
    $_SESSION['messageClass'] = "alert alert-success";
} else {
    $debugFile = USE_AFFINDA ? "affinda_debug.txt" : "gemini_debug.txt";
    $_SESSION['message'] = "⚠️ Resume uploaded but parsing failed. Check $debugFile for details.";
    $_SESSION['messageClass'] = "alert alert-warning";
}

$conn->close();
header("Location: my_profile.php");
exit();
?>