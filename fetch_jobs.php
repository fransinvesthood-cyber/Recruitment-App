<?php
session_start(); //Start the session

//Database connection
include ('config.php');

//Get search query and page number from AJAX request
$search = isset($_POST['search']) ? '%' . $_POST['search'] . '%' : '';
$page = isset($_POST['page']) ? (int)$_POST['page'] : 0;
$limit = 10; //Number of posts to load per request
$offset = $page * $limit;

//Query to get job postings with search filter, including both internal and external jobs
$sql = "(SELECT
            job_postings.job_id,
            job_postings.position,
            companies.company_name,
            job_postings.location,
            job_postings.job_description,
            CONCAT('R', FORMAT(job_postings.salary, 2)) AS salary,
            job_postings.date_posted,
            users.fullname AS recruiter_name,
            users.email AS recruiter_email,
            departments.department_name,
            'internal' AS type,
            '' AS url,
            '' AS source,
            '' AS closing_date
        FROM job_postings
        INNER JOIN users ON job_postings.admin_id = users.user_id
        INNER JOIN departments ON job_postings.department_id = departments.department_id
        INNER JOIN companies ON job_postings.company_id = companies.company_id
        WHERE
            job_postings.position LIKE ? OR
            departments.department_name LIKE ? OR
            companies.company_name LIKE ? OR
            job_postings.location LIKE ?
        )
        UNION ALL
        (SELECT
            external_job_id AS job_id,
            title AS position,
            company AS company_name,
            location,
            description AS job_description,
            salary,
            date_fetched AS date_posted,
            '' AS recruiter_name,
            '' AS recruiter_email,
            '' AS department_name,
            'external' AS type,
            url,
            source,
            closing_date
        FROM external_jobs
        WHERE
            title LIKE ? OR
            company LIKE ? OR
            location LIKE ?
        )
        ORDER BY date_posted DESC
        LIMIT $limit OFFSET $offset";

$stmt = $conn->prepare($sql);
$stmt->bind_param('sssssss', $search, $search, $search, $search, $search, $search, $search);
$stmt->execute();
$result = $stmt->get_result();

//Check if there are any job postings
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $is_external = $row['type'] === 'external';
        $view_link = $is_external ? $row['url'] : 'job_details.php?job_id=' . $row['job_id'];
        $target = $is_external ? 'target="_blank"' : '';

        //Return the job postings in HTML format
        echo "<div class='job-listing'>";
        echo "<a class='title' href='" . $view_link . "' " . $target . "><i class='fas fa-briefcase' style='color: #f39c12; font-size: 1.2em; margin-right: 5px;'></i>" . htmlspecialchars($row['position']) . "</a>";
        echo "<p><i class='fas fa-map-marker-alt' style='color: #3498db; font-size: 1.2em; margin-right: 5px;'></i>" . htmlspecialchars($row['location']) . "</p>";
        echo "<p><i class='fas fa-building' style='color: #e74c3c; font-size: 1.2em; margin-right: 5px;'></i>" . htmlspecialchars($row['company_name']) . "</p>";
        $description = $row['job_description'];
        $clean_description = str_replace(["\n", "\r", "\\n", "/n"], ' ', $description); // Remove newlines and literal newline strings
        $clean_description = htmlspecialchars($clean_description);
        $truncated_description = strlen($clean_description) > 300 ? substr($clean_description, 0, 300) . '...' : $clean_description;
        echo "<p><strong></strong> " . $truncated_description . "</p>";
        echo "<p><i class='fas fa-calendar' style='color: #9b59b6; font-size: 1.2em; margin-right: 5px;'></i>Posted on: " . date('F j, Y', strtotime($row['date_posted'])) . "</p>";
        $share_url = $is_external ? $row['url'] : 'job_details.php?job_id=' . $row['job_id'];
        echo "<button onclick=\"openShareModal('" . urlencode($share_url) . "', '" . htmlspecialchars($row['position']) . "')\"><i class='fas fa-share'></i> Share</button>";
        echo "<button><a href='" . $view_link . "' " . $target . "><i class='fas fa-eye'></i> <span>View Post</span></a></button>";

        // Apply button logic - only for internal jobs
        if (!$is_external) {
            if (isset($_SESSION['user_id'])) {
                $user_id = $_SESSION['user_id'];
                $job_id = $row['job_id'];

                // Check if the user already applied for this job
                $check_sql = "SELECT * FROM job_applications WHERE job_id = ? AND user_id = ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("ii", $job_id, $user_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();

                if ($check_result->num_rows > 0) {
                    // Already applied
                    echo "<button disabled style='background-color:gray; cursor:not-allowed;'><i class='fas fa-check'></i> Already Applied</button>";
                } else {
                    // Not yet applied
                    echo "<button><a href='apply.php?job_id=" . $row['job_id'] . "&position=" . urlencode($row['position']) . "'><i class='fas fa-paper-plane'></i> Apply Now</a></button>";
                }

                $check_stmt->close();
            } else {
                echo "<button onclick='showLoginMessage()'><i class='fas fa-paper-plane'></i> Apply Now</button>";
            }
        } else {
            echo "<button><a href='" . $row['url'] . "' target='_blank'><i class='fas fa-external-link-alt'></i> Apply Externally</a></button>";
        }

        echo "</div><br>";
    }
} else {
    echo "No posted jobs at the moment.";
}

$stmt->close();
$conn->close();
?>

<script>
function showLoginMessage() {
    alert("You cannot perform this action. Please login to apply.");
    window.location.href = 'login_signup.php';
}
</script>