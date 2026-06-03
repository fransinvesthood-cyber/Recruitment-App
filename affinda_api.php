<?php
require 'vendor/autoload.php'; // Assuming Composer is used for dependencies
include('config.php');

class AffindaAPI {
    private $apiKey;
    private $baseUrl = 'https://api.affinda.com/v2/';

    public function __construct() {
        $this->apiKey = AFFINDA_API_KEY;
    }

    /**
     * Upload a resume file to Affinda and get the document ID
     * @param string $filePath Path to the resume file
     * @param string $fileName Original filename
     * @return string|null Document ID or null on failure
     */
    public function uploadResume($filePath, $fileName) {
        $url = $this->baseUrl . 'resumes';

        $cfile = curl_file_create($filePath, mime_content_type($filePath), $fileName);

        $postData = [
            'file' => $cfile,
            'fileName' => $fileName
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For development; remove in production

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            file_put_contents('affinda_debug.txt', 'Upload cURL error: ' . curl_error($ch));
            curl_close($ch);
            return null;
        }

        curl_close($ch);

        if ($httpCode !== 200 && $httpCode !== 201) {
            file_put_contents('affinda_debug.txt', "Upload failed with HTTP $httpCode: $response");
            return null;
        }

        $data = json_decode($response, true);
        if (!$data || !isset($data['identifier'])) {
            file_put_contents('affinda_debug.txt', 'Invalid upload response: ' . $response);
            return null;
        }

        return $data['identifier'];
    }

    /**
     * Retrieve parsed resume data from Affinda
     * @param string $documentId Document identifier from upload
     * @return array|null Parsed data or null on failure
     */
    public function getParsedResume($documentId) {
        $url = $this->baseUrl . 'resumes/' . $documentId;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For development; remove in production

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            file_put_contents('affinda_debug.txt', 'Retrieve cURL error: ' . curl_error($ch));
            curl_close($ch);
            return null;
        }

        curl_close($ch);

        if ($httpCode !== 200) {
            file_put_contents('affinda_debug.txt', "Retrieve failed with HTTP $httpCode: $response");
            return null;
        }

        $data = json_decode($response, true);
        if (!$data) {
            file_put_contents('affinda_debug.txt', 'Invalid JSON response: ' . $response);
            return null;
        }

        return $data;
    }

    /**
     * Parse and map Affinda data to structured format
     * @param array $affindaData Raw data from Affinda API
     * @return array Structured data for database insertion
     */
    public function mapToStructuredData($affindaData) {
        $structured = [
            'professional_summary' => '',
            'education' => [],
            'skills' => ['technical' => [], 'soft' => []],
            'work_experience' => []
        ];

        // Professional Summary
        if (isset($affindaData['data']['summary'])) {
            $structured['professional_summary'] = $affindaData['data']['summary'];
        }

        // Education
        if (isset($affindaData['data']['education']) && is_array($affindaData['data']['education'])) {
            foreach ($affindaData['data']['education'] as $edu) {
                $structured['education'][] = [
                    'qualification' => $edu['accreditation'] ?? '',
                    'institution' => $edu['organization'] ?? '',
                    'year' => $edu['dates']['completion_date'] ?? $edu['dates']['start_date'] ?? ''
                ];
            }
        }

        // Skills
        if (isset($affindaData['data']['skills']) && is_array($affindaData['data']['skills'])) {
            foreach ($affindaData['data']['skills'] as $skill) {
                $skillName = $skill['name'] ?? '';
                if (!empty($skillName)) {
                    // Categorize skills (basic categorization - can be improved)
                    if (in_array(strtolower($skillName), ['communication', 'leadership', 'teamwork', 'problem solving'])) {
                        $structured['skills']['soft'][] = $skillName;
                    } else {
                        $structured['skills']['technical'][] = $skillName;
                    }
                }
            }
        }

        // Work Experience
        if (isset($affindaData['data']['work_experience']) && is_array($affindaData['data']['work_experience'])) {
            foreach ($affindaData['data']['work_experience'] as $exp) {
                $structured['work_experience'][] = [
                    'position' => $exp['job_title'] ?? '',
                    'company' => $exp['organization'] ?? '',
                    'duration' => $this->formatDuration($exp['dates']),
                    'duties' => $exp['job_description'] ?? ''
                ];
            }
        }

        return $structured;
    }

    /**
     * Format duration from Affinda dates
     * @param array $dates Dates array from Affinda
     * @return string Formatted duration
     */
    private function formatDuration($dates) {
        if (!isset($dates['start_date']) || !isset($dates['end_date'])) {
            return '';
        }

        $start = date('M Y', strtotime($dates['start_date']));
        $end = $dates['is_current'] ? 'Present' : date('M Y', strtotime($dates['end_date']));

        return $start . ' - ' . $end;
    }

    /**
     * Save structured data to database
     * @param int $userId User ID
     * @param array $data Structured data
     * @return bool Success status
     */
    public function saveToDatabase($userId, $data) {
        global $conn;

        try {
            // Professional Summary
            if (!empty($data['professional_summary'])) {
                $check = $conn->prepare("SELECT app_profile_id FROM applicant_profile WHERE user_id=?");
                $check->bind_param("i", $userId);
                $check->execute();
                $check->store_result();

                if ($check->num_rows > 0) {
                    $update = $conn->prepare("UPDATE applicant_profile SET professional_summary=?, updated_at=NOW() WHERE user_id=?");
                    $update->bind_param("si", $data['professional_summary'], $userId);
                    $update->execute();
                    $update->close();
                } else {
                    $insert = $conn->prepare("INSERT INTO applicant_profile (user_id, professional_summary, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
                    $insert->bind_param("is", $userId, $data['professional_summary']);
                    $insert->execute();
                    $insert->close();
                }
                $check->close();
            }

            // Skills
            if (!empty($data['skills']['technical']) || !empty($data['skills']['soft'])) {
                $technical = implode(", ", $data['skills']['technical']);
                $soft = implode(", ", $data['skills']['soft']);

                $check = $conn->prepare("SELECT skill_id FROM skills WHERE user_id=?");
                $check->bind_param("i", $userId);
                $check->execute();
                $check->store_result();

                if ($check->num_rows > 0) {
                    $update = $conn->prepare("UPDATE skills SET technical_skills=?, soft_skills=?, created_at=NOW() WHERE user_id=?");
                    $update->bind_param("ssi", $technical, $soft, $userId);
                    $update->execute();
                    $update->close();
                } else {
                    $insert = $conn->prepare("INSERT INTO skills (user_id, technical_skills, soft_skills) VALUES (?, ?, ?)");
                    $insert->bind_param("iss", $userId, $technical, $soft);
                    $insert->execute();
                    $insert->close();
                }
                $check->close();
            }

            // Education
            foreach ($data['education'] as $edu) {
                if (!empty($edu['qualification']) && !empty($edu['institution'])) {
                    // Check if record exists
                    $check = $conn->prepare("SELECT qualification_id FROM qualifications WHERE user_id=? AND qualification_name=? AND institution=?");
                    $check->bind_param("iss", $userId, $edu['qualification'], $edu['institution']);
                    $check->execute();
                    $check->store_result();

                    if ($check->num_rows > 0) {
                        // Update existing record
                        $update = $conn->prepare("UPDATE qualifications SET year_completed=? WHERE user_id=? AND qualification_name=? AND institution=?");
                        $update->bind_param("iiss", $edu['year'], $userId, $edu['qualification'], $edu['institution']);
                        $update->execute();
                        $update->close();
                    } else {
                        // Insert new record
                        $insert = $conn->prepare("INSERT INTO qualifications (user_id, qualification_name, institution, year_completed) VALUES (?, ?, ?, ?)");
                        $insert->bind_param("issi", $userId, $edu['qualification'], $edu['institution'], $edu['year']);
                        $insert->execute();
                        $insert->close();
                    }
                    $check->close();
                }
            }

            // Work Experience
            foreach ($data['work_experience'] as $exp) {
                if (!empty($exp['position']) && !empty($exp['company'])) {
                    // Check if record exists
                    $check = $conn->prepare("SELECT work_exp_id FROM work_experience WHERE user_id=? AND position=? AND company_name=?");
                    $check->bind_param("iss", $userId, $exp['position'], $exp['company']);
                    $check->execute();
                    $check->store_result();

                    if ($check->num_rows > 0) {
                        // Update existing record
                        $update = $conn->prepare("UPDATE work_experience SET duration=?, duties=? WHERE user_id=? AND position=? AND company_name=?");
                        $update->bind_param("ssiss", $exp['duration'], $exp['duties'], $userId, $exp['position'], $exp['company']);
                        $update->execute();
                        $update->close();
                    } else {
                        // Insert new record
                        $insert = $conn->prepare("INSERT INTO work_experience (user_id, position, company_name, duration, duties) VALUES (?, ?, ?, ?, ?)");
                        $insert->bind_param("issss", $userId, $exp['position'], $exp['company'], $exp['duration'], $exp['duties']);
                        $insert->execute();
                        $insert->close();
                    }
                    $check->close();
                }
            }

            return true;
        } catch (Exception $e) {
            file_put_contents('affinda_debug.txt', 'Database error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update education data for a user
     * @param int $userId User ID
     * @param array $education Array of education entries (each may have 'id' for existing records)
     * @return bool Success status
     */
    public function updateEducation($userId, $education) {
        global $conn;

        try {
            foreach ($education as $edu) {
                if (!empty($edu['qualification']) && !empty($edu['institution'])) {
                    if (isset($edu['id']) && !empty($edu['id'])) {
                        // Update existing record
                        $update = $conn->prepare("UPDATE qualifications SET qualification_name=?, institution=?, year_completed=? WHERE qualification_id=? AND user_id=?");
                        $update->bind_param("ssiii", $edu['qualification'], $edu['institution'], $edu['year'], $edu['id'], $userId);
                        $update->execute();
                        $update->close();
                    } else {
                        // Insert new record
                        $insert = $conn->prepare("INSERT INTO qualifications (user_id, qualification_name, institution, year_completed) VALUES (?, ?, ?, ?)");
                        $insert->bind_param("issi", $userId, $edu['qualification'], $edu['institution'], $edu['year']);
                        $insert->execute();
                        $insert->close();
                    }
                }
            }

            return true;
        } catch (Exception $e) {
            file_put_contents('affinda_debug.txt', 'Update education error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update work experience data for a user
     * @param int $userId User ID
     * @param array $workExperience Array of work experience entries (each may have 'id' for existing records)
     * @return bool Success status
     */
    public function updateWorkExperience($userId, $workExperience) {
        global $conn;

        try {
            foreach ($workExperience as $exp) {
                if (!empty($exp['position']) && !empty($exp['company'])) {
                    if (isset($exp['id']) && !empty($exp['id'])) {
                        // Update existing record
                        $update = $conn->prepare("UPDATE work_experience SET position=?, company_name=?, duration=?, duties=? WHERE work_exp_id=? AND user_id=?");
                        $update->bind_param("ssssii", $exp['position'], $exp['company'], $exp['duration'], $exp['duties'], $exp['id'], $userId);
                        $update->execute();
                        $update->close();
                    } else {
                        // Insert new record
                        $insert = $conn->prepare("INSERT INTO work_experience (user_id, position, company_name, duration, duties) VALUES (?, ?, ?, ?, ?)");
                        $insert->bind_param("issss", $userId, $exp['position'], $exp['company'], $exp['duration'], $exp['duties']);
                        $insert->execute();
                        $insert->close();
                    }
                }
            }

            return true;
        } catch (Exception $e) {
            file_put_contents('affinda_debug.txt', 'Update work experience error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Complete parsing workflow: upload, retrieve, map, and save
     * @param string $filePath Path to resume file
     * @param string $fileName Original filename
     * @param int $userId User ID
     * @return bool Success status
     */
    public function parseAndSaveResume($filePath, $fileName, $userId) {
        // Upload resume
        $documentId = $this->uploadResume($filePath, $fileName);
        if (!$documentId) {
            return false;
        }

        // Wait a moment for processing (Affinda may need time to process)
        sleep(3);

        // Retrieve parsed data
        $rawData = $this->getParsedResume($documentId);
        if (!$rawData) {
            return false;
        }

        // Map to structured data
        $structuredData = $this->mapToStructuredData($rawData);

        // Save to database
        return $this->saveToDatabase($userId, $structuredData);
    }
}
?>
