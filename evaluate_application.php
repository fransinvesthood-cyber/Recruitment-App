<?php
function normalizeQualification($qual) {
    if (empty($qual)) return '';
    
    // Clean punctuation, multiple spaces
    $qual = preg_replace('/[[:punct:]¡¿]/u', ' ', $qual);
    $qual = trim(preg_replace('/\\s+/', ' ', strtolower($qual)));
    
    // Comprehensive abbreviation expansion
    $abbr_map = [
        // Degrees
        'bsc' => 'bachelor science', 'bs' => 'bachelor science', 'ba' => 'bachelor arts',
        'btech' => 'bachelor technology', 'beng' => 'bachelor engineering',
        'bit' => 'bachelor information technology', 'bsit' => 'bachelor information technology',
        'bcit' => 'bachelor information technology',
        // Diplomas
        'dit' => 'diploma information technology', 'ndit' => 'national diploma information technology',
        'n6' => 'national diploma', 'n5' => 'national certificate', 'n4' => 'national certificate',
        // IT/General
        'it' => 'information technology',
        // Masters/PhD
        'msc' => 'master science', 'ma' => 'master arts', 'mphil' => 'master philosophy',
        'phd' => 'doctorate', 'dphil' => 'doctorate',
        // General
        'matric' => 'matric', 'grade 12' => 'matric', 'nsc' => 'matric', 'grade12' => 'matric'
    ];
    
    foreach ($abbr_map as $abbr => $full) {
        if (stripos($qual, $abbr) !== false) {
            $qual = str_ireplace($abbr, $full, $qual);
            break; // Apply first match
        }
    }
    
    return $qual;
}

function getJobQualifications($conn, $job_id) {
    $stmt = $conn->prepare("SELECT qualification FROM job_qualifications WHERE job_id = ?");
    $stmt->bind_param("i", $job_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $quals = [];
    while ($row = $result->fetch_assoc()) {
        $normalized = normalizeQualification($row['qualification']);
        if (!empty($normalized)) $quals[] = $normalized;
    }
    $stmt->close();
    return $quals;
}

function evaluateApplicant($conn, $application_id, $debugMode = false) {
// Fetch application + job + user data in one query
    $sql = "
        SELECT
            ja.application_id, ja.user_id, ja.job_id, ja.position, ja.qualification,
            jp.minimum_criteria,
            jp.skills AS job_skills,
            jp.requirements,
            u.address, u.dob,
            ss.profile_data,
            GROUP_CONCAT(DISTINCT s.technical_skills, ', ', s.soft_skills) AS applicant_skills,
            GROUP_CONCAT(DISTINCT q.qualification_name SEPARATOR '; ') AS qualifications,
            GROUP_CONCAT(DISTINCT we.duration SEPARATOR '; ') AS experiences
        FROM job_applications ja
        JOIN job_postings jp ON ja.job_id = jp.job_id
        LEFT JOIN application_snapshots ss ON ja.application_id = ss.application_id

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
// Use SNAPSHOT DATA primarily (skills-style) + DB fallback + ja.qualification
    $all_candidate_quals = [];
    $debug_quals = ['db' => [], 'snapshot' => [], 'self_reported' => []];
    
    // PRIMARY: Parse Affinda snapshot (expanded fields)
    if (!empty($row['profile_data'])) {
        $snapshot = json_decode($row['profile_data'], true);
        if (is_array($snapshot)) {
$qual_fields = ['qualification_name', 'qualifications', 'highest_qualification', 
                           'qualification_level', 'level', 'degree', 'diplomas', 'education', 
                           'certificates', 'degree_name', 'field_of_study', 'academic_degree', 
                           'degree_level', 'qual_name', 'qual_level', 'education_qualification'];
            foreach ($qual_fields as $field) {
                if (!empty($snapshot[$field])) {
                    if (is_array($snapshot[$field])) {
                        foreach ($snapshot[$field] as $item) {
                            $norm = normalizeQualification(is_string($item) ? $item : json_encode($item));
                            if (!empty($norm)) $all_candidate_quals[] = $norm;
                        }
                    } else {
                        $norm = normalizeQualification($snapshot[$field]);
                        if (!empty($norm)) {
                            $all_candidate_quals[] = $norm;
                            $debug_quals['snapshot'][$field] = $snapshot[$field];
                        }
                    }
                }
            }
        }
    }
    
    // FALLBACK 1: DB qualifications
    $db_quals_raw = strtolower($row['qualifications'] ?? '');
    if (!empty($db_quals_raw)) {
        $db_qual_list = array_filter(array_map('trim', explode(';', $db_quals_raw)));
        foreach ($db_qual_list as $q) {
            $norm = normalizeQualification($q);
            if (!empty($norm)) {
                $all_candidate_quals[] = $norm;
                $debug_quals['db'][] = $q;
            }
        }
    }
    
    // FALLBACK 2: Self-reported ja.qualification
    $self_reported = normalizeQualification($row['qualification'] ?? '');
    if (!empty($self_reported)) {
        $all_candidate_quals[] = $self_reported;
        $debug_quals['self_reported'][] = $row['qualification'];
    }
    
    $all_candidate_quals = array_unique(array_filter($all_candidate_quals));
    
    $experiences = $row['experiences'] ?? '';
    $candidate_address = strtolower($row['address'] ?? '');
    $dob = $row['dob'] ?? null;

    $reasons = [];
    $pass = true;

// ✅ Skill Check (70% match required for shortlisting) - with normalization
if (!empty($criteria['required_skills']) && is_array($criteria['required_skills'])) {

    // Skill normalization map
$skill_map = [
        'good communication' => 'communication',
        'effective communication' => 'communication',
        'excellent communication' => 'communication',
        'communication skills' => 'communication',
        'strong communication' => 'communication',
        'leadership' => 'leadership',
        'teamwork' => 'teamwork',
        'team work' => 'teamwork',
        'team player' => 'teamwork',
'problem solving' => 'problem-solving',
        'problem-solving' => 'problem-solving',
        'verbal communication' => 'communication',
        'written communication' => 'communication',
        'interpersonal skills' => 'communication',
        'presentation skills' => 'communication',
        'articulation' => 'communication',
        'public speaking' => 'communication',
'communication' => 'communication',
        'teamwork' => 'teamwork',
        'team player' => 'teamwork',
        'collaboration' => 'teamwork',
        'collaborative' => 'teamwork',
        'works well in a team' => 'teamwork',
        'cross-functional teamwork' => 'teamwork',
'partnership' => 'teamwork',
'problem-solving' => 'problem-solving',
        'analytical thinking' => 'problem solving',
        'critical thinking' => 'problem solving',
        'troubleshooting' => 'problem solving',
        'debugging' => 'problem solving',
        'issue resolution' => 'problem solving',
        'solution-oriented' => 'problem solving',
'decision making' => 'problem solving',
        'team leadership' => 'leadership',
        'leading teams' => 'leadership',
        'management' => 'leadership',
        'people management' => 'leadership',
        'supervision' => 'leadership',
        'mentoring' => 'leadership',
'coaching' => 'leadership',
        'flexibility' => 'adaptability',
        'versatile' => 'adaptability',
        'open to change' => 'adaptability',
        'resilient' => 'adaptability',
        'adjustable' => 'adaptability',
'dynamic' => 'adaptability',
        'willingness to learn' => 'willingness_to_learn',
        'eager to learn' => 'willingness_to_learn',
        'quick learner' => 'willingness_to_learn',
        'fast learner' => 'willingness_to_learn',
        'self-motivated learner' => 'willingness_to_learn',
        'growth mindset' => 'willingness_to_learn',
'continuous learning' => 'willingness_to_learn',
        'time management' => 'time_management',
        'deadline driven' => 'time_management',
        'ability to meet deadlines' => 'time_management',
        'prioritization' => 'time_management',
        'task management' => 'time_management',
        'organizational skills' => 'time_management',
'multitasking' => 'time_management',
        'attention to detail' => 'attention_to_detail',
        'detail-oriented' => 'attention_to_detail',
        'accuracy' => 'attention_to_detail',
        'precision' => 'attention_to_detail',
'thoroughness' => 'attention_to_detail',
        'hardworking' => 'work_ethic',
        'strong work ethic' => 'work_ethic',
        'dedicated' => 'work_ethic',
        'committed' => 'work_ethic',
        'reliable' => 'work_ethic',
        'responsible' => 'work_ethic'
        // Add more as needed
    ];

    $total_required = count($criteria['required_skills']);
    $matched = 0;
    foreach ($criteria['required_skills'] as $skill) {
        $skill = trim(strtolower($skill));
        if (empty($skill)) continue;

        // Normalize required skill
        $norm_skill_original = $skill_map[$skill] ?? $skill;
        $norm_skill = str_replace([' ', '-'], '-', $norm_skill_original);
        $norm_skill = strtolower(trim($norm_skill));

        // Check if applicant has normalized skill (word boundary)
        // Normalize applicant_skills for matching (replace spaces and hyphens with hyphen)
        $applicant_normalized = preg_replace('/\\s+/', '-', $applicant_skills);
        $applicant_normalized = strtolower(trim($applicant_normalized));
        if (preg_match('/\\b' . preg_quote($norm_skill, '/') . '\\b/i', $applicant_normalized)) {
            $matched++;
            $reasons[] = "✅ Found: $skill";
        } else {
$reasons[] = "⚠️ Missing: $skill";
        }
    }
    $match_percentage = $total_required > 0 ? ($matched / $total_required) * 100 : 0;
    $reasons[] = "📊 Skill match: $matched/$total_required (" . round($match_percentage, 1) . "%)";
    
    if ($match_percentage < 70) {
        $reasons[] = "❌ Skill match <70%";
        $pass = false;
    } else {
        $reasons[] = "✅ Skills OK";
    }
}


    // ✅ Multiple Qualification Check (OR any from job_qualifications table or JSON fallback)
    $job_quals = getJobQualifications($conn, $row['job_id']);
    if (empty($job_quals)) {
        // Fallback to legacy JSON single qual
        if (!empty($criteria['required_qualification'])) {
            $job_quals = [trim(strtolower($criteria['required_qualification']))];
        }
    }
    
    if (!empty($job_quals)) {
// Expanded comprehensive qual_map for IT/SA qualifications
        $qual_map = [
            // Degrees - IT/Tech
            "bachelor's degree in information technology" => "bachelor.*?(information technology|it)|bsit|bit|bcit|bsc.*?(it|information technology)",
            "bachelor of science in information technology" => "bachelor science.*?(it|information technology)|bscit|bsit|bit",
            "bachelor of information technology" => "bachelor.*?(information technology|it)|bit|bsit|bscit",
            "bsc computer science" => "bachelor science.*?(computer science|cs)|bsc.*?(cs|computer science)",
            "bachelor of computer science" => "bachelor.*?(computer science|cs)|bsc.*?(cs|computer science)",
            
            // Degrees - General
            "bachelor's degree" => "bachelor|degree|bsc|ba|bs|btech|beng",
            "bachelor of science" => "bachelor science|bsc|bs",
            "bachelor of arts" => "bachelor arts|ba",
            "bachelor of technology" => "bachelor technology|btech",
            
            // Diplomas/Certificates
            "diploma in information technology" => "diploma.*?(information technology|it)|dit|national diploma.*?(it|information technology)|n6.*?(it|information technology)",
            "diploma in it" => "diploma.*?(it|information technology)|dit|national diploma it|n6 it",
            "national diploma information technology" => "national diploma.*?(it|information technology)|ndit|dit|n6 it",
            "national diploma it" => "national diploma.*?(it|information technology)|ndit|dit|n6 it",
            "n6 information technology" => "n6.*?(it|information technology)|national.*?(n6|diploma).*?(it|information technology)",
            
            // Certificates
            "certificate in it" => "certificate.*?(information technology|it)|nqf.*?(5|certificate).*?(it|information)",
            "higher certificate" => "higher certificate|nqf.*?(5|certificate)",
            
            // Matric/Grade 12
            "matric" => "matric|grade 12|grade12|national senior certificate|nsc",
            
            // Postgraduate
            "master's degree" => "master|masters|msc|ma|mphil|postgraduate",
            "honours degree" => "honours|honors|postgraduate diploma",
            
            // Doctorate
            "phd" => "phd|dphil|doctorate|doctoral",
            
            // Generic IT
            "information technology" => "information technology|it",
            "computer science" => "computer science|cs"
        ];
        
        $any_qual_match = false;
        $matched_quals = [];
        $failed_quals = [];
        
        foreach ($job_quals as $req_q) {
            $req_q_original = $req_q;
            $req_q = normalizeQualification($req_q);
            
            if (empty($req_q)) continue;
            
            $qual_match = false;
            $match_type = '';
            
            // Tier 1: Direct normalized match or qual_map pattern
            if (in_array($req_q, $all_candidate_quals)) {
                $qual_match = true;
                $match_type = 'exact';
            } elseif (isset($qual_map[$req_q_original])) {
                $pattern = '/' . str_replace('|', '|', $qual_map[$req_q_original]) . '/i';
                foreach ($all_candidate_quals as $cand_qual) {
                    if (preg_match($pattern, $cand_qual)) {
                        $qual_match = true;
                        $match_type = 'pattern';
                        break;
                    }
                }
            }
            
// Tier 2: Flexible substring (75% similarity) + keyword overlap
            if (!$qual_match) {
                foreach ($all_candidate_quals as $cand_qual) {
                    similar_text($req_q, $cand_qual, $percent);
                    if ($percent >= 75) {
                        $qual_match = true;
                        $match_type = 'fuzzy(' . round($percent) . '%)';
                        break;
                    }
                    // Tier 3: Keyword overlap >=60%
                    $req_words = array_filter(explode(' ', $req_q));
                    $cand_words = array_filter(explode(' ', $cand_qual));
                    $overlap = count(array_intersect($req_words, $cand_words));
                    if (!empty($req_words) && $overlap / count($req_words) >= 0.6) {
                        $qual_match = true;
                        $match_type = 'overlap(' . $overlap . '/' . count($req_words) . ')';
                        break;
                    }
                }
            }
            
            if ($qual_match) {
                $matched_quals[] = "$req_q_original ($match_type)";
                $any_qual_match = true;
                break; // OR logic
            } else {
                $failed_quals[] = $req_q_original;
            }
        }
        
        if ($any_qual_match) {
            $reasons[] = "✅ Qual match: " . implode(', ', array_slice($matched_quals, 0, 2));
        } else {
            $reasons[] = "❌ No qualification match";
            $reasons[] = "   Candidate quals: " . (!empty($all_candidate_quals) ? implode(', ', array_slice($all_candidate_quals, 0, 3)) : 'None found');
            $reasons[] = "   Required: " . implode(', ', array_slice($job_quals, 0, 3));
            $pass = false;
        }
    }


    // ✅ Experience Check
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
            $reasons[] = "<span style='color:#ff4444; font-weight:bold;'>❌ Insufficient experience: $totalYears years (need ≥ {$criteria['min_years_experience']})</span>";
            $pass = false;
        } else {
            $reasons[] = "✅ Experience match ($totalYears years)";
        }
    }

    // ✅ Location Check (province & city from address)
    if (!empty($criteria['province'])) {
        $prov = $criteria['province'];
        if (stripos($candidate_address, strtolower($prov)) === false) {
            $reasons[] = "<span style='color:#ff4444; font-weight:bold;'>❌ Province mismatch: '$candidate_address' vs required '$prov'</span>";
            $pass = false;
        } else {
            $reasons[] = "✅ Province match: $prov (found in: $candidate_address)";
        }
    }
    if (!empty($criteria['city_town'])) {
        $city = $criteria['city_town'];
        if (stripos($candidate_address, strtolower($city)) === false) {
            $reasons[] = "<span style='color:#ff4444; font-weight:bold;'>❌ City/Town mismatch: '$candidate_address' vs required '$city'</span>";
            $pass = false;
        } else {
            $reasons[] = "✅ City/Town match: $city (found in: $candidate_address)";
        }
    }

    // ✅ Age Range Check
    if (isset($criteria['min_age']) || isset($criteria['max_age'])) {
        if ($dob) {
            $age = floor((strtotime('now') - strtotime($dob)) / (365.25 * 24 * 60 * 60));
            $min_age = $criteria['min_age'] ?? 18;
            $max_age = $criteria['max_age'] ?? 65;
            
            if ($age < $min_age || $age > $max_age) {
                $reasons[] = "<span style='color:#ff4444; font-weight:bold;'>❌ Age mismatch: $age (required: $min_age-$max_age)</span>";
                $pass = false;
            } else {
                $reasons[] = "✅ Age match: $age years";
            }
        } else {
            $reasons[] = "⚠️ Age unavailable (no DOB)";
        }
    }

// STRICT CHECK: Skip auto-reject if no criteria defined
    $job_quals = getJobQualifications($conn, $row['job_id']);
    $has_criteria = !empty($criteria['required_skills']) || 
                    !empty($job_quals) || !empty($criteria['required_qualification']) || 
                    (!empty($criteria['min_years_experience']) && $criteria['min_years_experience'] > 0) ||
                    !empty($criteria['province']) || !empty($criteria['city_town']) ||
                    (!empty($criteria['min_age']) && $criteria['min_age'] > 0) ||
                    (!empty($criteria['max_age']) && $criteria['max_age'] > 0);


    $current_status = 'Submitted'; // Default assumption for new apps
    $update_status = true;

    if (!$has_criteria) {
        $reasons[] = "ℹ️ No minimum requirements configured - keeping status as Submitted";
        $new_status = 'Submitted';
        $comments = 'Skipped auto-evaluation: No minimum criteria defined for job';
        $update_status = false; // Don't change status, just add comment
    } else {
        // Normal evaluation logic
        $new_status = $pass ? 'Shortlisted' : 'Rejected';
        $comments = $pass ? 'Auto-shortlisted: All criteria met' : 'Auto-rejected: ' . implode('; ', array_slice($reasons, 0, 3));
    }

    // Update status in DB (only if needed)
    if ($update_status) {
        $stmt = $conn->prepare("
            UPDATE job_applications 
            SET application_status = ?, comments = ?
            WHERE application_id = ?");
        $stmt->bind_param("ssi", $new_status, $comments, $application_id);
    } else {
        // Just update comments when keeping status
        $stmt = $conn->prepare("
            UPDATE job_applications 
            SET comments = ?
            WHERE application_id = ?");
        $stmt->bind_param("si", $comments, $application_id);
    }
    $stmt->execute();

    return [
        'status' => $new_status,
        'pass' => $pass,
        'reasons' => $reasons,
        'comments' => $comments,
'debug' => $debugMode ? [
            'criteria' => $criteria,
            'job_quals' => $job_quals,
            'candidate_quals' => $debug_quals,
            'all_candidate_quals' => $all_candidate_quals,
            'address' => $row['address'],
            'age' => $dob ? floor((strtotime('now') - strtotime($dob)) / (365.25 * 24 * 60 * 60)) : null
        ] : null

    ];
}
?>
