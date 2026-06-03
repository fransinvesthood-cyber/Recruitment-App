<?php
function evaluateApplicant($conn, $application_id, $debugMode = false) {
    // Fetch application + job + user data in one query
    $sql = "
        SELECT
            ja.application_id, ja.user_id, ja.job_id, ja.position,
            jp.minimum_criteria,
            jp.skills AS job_skills,
            jp.requirements,
            u.address, u.dob,
            GROUP_CONCAT(DISTINCT s.technical_skills, ', ', s.soft_skills) AS applicant_skills,
            GROUP_CONCAT(DISTINCT q.qualification_name SEPARATOR '; ') AS qualifications,
            GROUP_CONCAT(DISTINCT we.duration SEPARATOR '; ') AS experiences
        FROM job_applications ja
        JOIN job_postings jp ON ja.job_id = jp.job_id
        JOIN users u ON ja.user_id = u.user_id
        LEFT JOIN skills s ON u.user_id = s.user_id
        LEFT JOIN qualifications q ON u.user_id = q.user_id
        LEFT JOIN work_experience we ON u.user_id = we.user_id
        WHERE ja.application_id = ?
        GROUP BY ja.application_id
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $application_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if (!$row = $result->fetch_assoc()) return false;

    $criteria = json_decode($row['minimum_criteria'], true) ?: [];
    $applicant_skills = strtolower($row['applicant_skills'] ?? '');
    $qualifications = strtolower($row['qualifications'] ?? '');
    $experiences = $row['experiences'] ?? '';
    $candidate_address = strtolower($row['address'] ?? '');
    $dob = $row['dob'] ?? null;

    $reasons = [];
    $pass = true;

    // ✅ Skill Check (>=70% match + other criteria)
    if (!empty($criteria['required_skills']) && is_array($criteria['required_skills'])) {
        $total_required = count($criteria['required_skills']);
        $matched = 0;
        foreach ($criteria['required_skills'] as $skill) {
            $skill = trim($skill);
            if (empty($skill)) continue;
            if (preg_match('/\\b' . preg_quote(strtolower($skill), '/') . '\\b/i', $applicant_skills)) {
                $matched++;
            } else {
                $reasons[] = "⚠️ Missing: $skill";
            }
        }
        $match_percentage = $total_required > 0 ? ($matched / $total_required) * 100 : 0;
        $reasons[] = "📊 Skill match: $matched/$total_required (" . round($match_percentage, 1) . "%)";
        
        if ($match_percentage < 70) {
            $reasons[] = "❌ Skill match below 70% threshold";
            $pass = false;
        } else {
            $reasons[] = "✅ Skills >=70% ✓";
        }
    }

    // Qualification Check (strict)
    if (!empty($criteria['required_qualification'])) {
        $req_q = trim(strtolower($criteria['required_qualification']));
        $qual_match = false;
        if (strpos($qualifications, $req_q) !== false || preg_match('/\\b' . preg_quote($req_q, '/') . '\\b/i', $qualifications)) {
            $qual_match = true;
        } 
        if (!$qual_match) {
            $reasons[] = "❌ Missing qualification: $req_q";
            $pass = false;
        } else {
            $reasons[] = "✅ Qualification ✓";
        }
    }

    // Experience Check (strict)
    if (isset($criteria['min_years_experience']) && $criteria['min_years_experience'] > 0) {
        $totalYears = 0;
        foreach (explode(';', $experiences) as $exp) {
            $exp = trim($exp);
            if (preg_match('/(\\d+)\\s*year/i', $exp, $m)) {
                $totalYears += (int)$m[1];
            } elseif (preg_match('/(\\d{4})[^0-9]*(\\d{4})/', $exp, $m)) {
                $totalYears += max(0, $m[2] - $m[1]);
            }
        }
        if ($totalYears < $criteria['min_years_experience']) {
            $reasons[] = "❌ Experience: $totalYears years (need ≥" . $criteria['min_years_experience'] . ")";
            $pass = false;
        } else {
            $reasons[] = "✅ Experience ✓";
        }
    }

    // Location Check (strict)
    if (!empty($criteria['province'])) {
        if (stripos($candidate_address, strtolower($criteria['province'])) === false) {
            $reasons[] = "❌ Province mismatch";
            $pass = false;
        } else {
            $reasons[] = "✅ Province ✓";
        }
    }
    if (!empty($criteria['city_town'])) {
        if (stripos($candidate_address, strtolower($criteria['city_town'])) === false) {
            $reasons[] = "❌ City mismatch";
            $pass = false;
        } else {
            $reasons[] = "✅ City ✓";
        }
    }

    // Age Range Check (strict)
    if (isset($criteria['min_age']) || isset($criteria['max_age'])) {
        if ($dob) {
            $age = floor((strtotime('now') - strtotime($dob)) / (365.25 * 24 * 60 * 60));
            $min_age = $criteria['min_age'] ?? 18;
            $max_age = $criteria['max_age'] ?? 65;
            if ($age < $min_age || $age > $max_age) {
                $reasons[] = "❌ Age mismatch: $age (need $min_age-$max_age)";
                $pass = false;
            } else {
                $reasons[] = "✅ Age ✓";
            }
        } else {
            $reasons[] = "⚠️ No DOB";
        }
    }

    $has_criteria = !empty($criteria['required_skills']) || !empty($criteria['required_qualification']) || 
        (!empty($criteria['min_years_experience']) && $criteria['min_years_experience'] > 0) ||
        !empty($criteria['province']) || !empty($criteria['city_town']) || 
        (!empty($criteria['min_age']) && $criteria['min_age'] > 0) || (!empty($criteria['max_age']) && $criteria['max_age'] > 0);

    $update_status = true;
    if (!$has_criteria) {
        $new_status = 'Submitted';
        $comments = 'Skipped: No criteria';
        $update_status = false;
    } else {
        $new_status = $pass ? 'Shortlisted' : 'Rejected';
        $comments = $pass ? 'Auto-shortlisted: Skills >=70% + criteria met' : 'Auto-rejected';
    }

    if ($update_status) {
        $stmt = $conn->prepare("UPDATE job_applications SET application_status = ?, comments = ? WHERE application_id = ?");
        $stmt->bind_param("ssi", $new_status, $comments, $application_id);
    } else {
        $stmt = $conn->prepare("UPDATE job_applications SET comments = ? WHERE application_id = ?");
        $stmt->bind_param("si", $comments, $application_id);
    }
    $stmt->execute();

    return [
        'status' => $new_status,
        'pass' => $pass,
        'reasons' => $reasons,
        'comments' => $comments,
        'debug' => $debugMode ? ['criteria' => $criteria, 'skills_pct' => $match_percentage ?? 0] : null
    ];
}
?>
```
**Use this fixed version**: Save as evaluate_application.php. Now 83.3% + passes = Shortlisted. No syntax errors.</result>
</xai:function_call name="command">
</xai:function_call name="create_file">
<parameter>
