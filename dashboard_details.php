<?php
include 'config.php';   
    // Automatically close expired jobs
    $today = date('Y-m-d H:i:s');

    $updateStatus = $conn->prepare("
        UPDATE job_postings
        SET job_status = 'Closed'
        WHERE deadline < ?
        AND job_status = 'Active'
    ");

    $updateStatus->bind_param("s", $today);
    $updateStatus->execute();
    $updateStatus->close();

    if (!isset($_GET['type'])) {
        exit("Invalid request.");
    }

    $type = $_GET['type'];

    ?>

    <style>

    .table-container{
        overflow-x:auto;
    }

    table{
        width:100%;
        border-collapse:collapse;
        margin-top:10px;
    }

    table th{
        background:#1976d2;
        color:#fff;
        padding:10px;
        text-align:left;
    }

    table td{
        padding:10px;
        border-bottom:1px solid #ddd;
    }

    table tr:hover{
        background:#f5f5f5;
    }

    .badge{
        padding:5px 10px;
        border-radius:20px;
        color:#fff;
        font-size:12px;
    }

    .submitted{
        background:#0d6efd;
    }

    .shortlisted{
        background:#198754;
    }

    .rejected{
        background:#dc3545;
    }

    .hired{
        background:#6f42c1;
    }

    .pending{
        background:#ffc107;
        color:#000;
    }

    .approved{
        background:#198754;
    }

    </style>

    <div class="table-container">

    <?php

    switch($type){

    /*====================================================
    INTERNAL JOBS
    ====================================================*/

    case 'internal':

    $sql = "SELECT
                position,
                deadline,
                job_status
            FROM job_postings
            ORDER BY date_posted DESC";

    $result = $conn->query($sql);

    echo "<table>";

    echo "
    <tr>
        <th>Position</th>
        <th>Closing Date</th>
        <th>Status</th>
    </tr>";

    if($result->num_rows > 0){

        while($row = $result->fetch_assoc()){

            echo "
            <tr>
                <td>{$row['position']}</td>
                <td>{$row['deadline']}</td>
                <td>{$row['job_status']}</td>
            </tr>";

        }

    }else{

        echo "<tr><td colspan='3'>No Internal Jobs Found.</td></tr>";

    }

    echo "</table>";

    break;


    /*====================================================
    EXTERNAL JOBS
    ====================================================*/

    case 'external':

    $sql = "SELECT
                title,
                company,
                location,
                closing_date,
                source
            FROM external_jobs
            ORDER BY date_fetched DESC";

    $result = $conn->query($sql);

    echo "<table>";

    echo "
    <tr>
        <th>Job Title</th>
        <th>Company</th>
        <th>Location</th>
        <th>Closing Date</th>
        <th>Source</th>
    </tr>";

    if($result->num_rows > 0){

        while($row = $result->fetch_assoc()){

            echo "
            <tr>
                <td>{$row['title']}</td>
                <td>{$row['company']}</td>
                <td>{$row['location']}</td>
                <td>{$row['closing_date']}</td>
                <td>{$row['source']}</td>
            </tr>";

        }

    }else{

        echo "<tr><td colspan='5'>No External Jobs Found.</td></tr>";

    }

    echo "</table>";

    break;


    /*====================================================
    JOB APPLICATIONS
    ====================================================*/

    case 'applications':

    $sql = "SELECT
                users.fullname,
                job_applications.position,
                job_applications.application_status,
                job_applications.submission_date
            FROM job_applications
            INNER JOIN users
            ON users.user_id = job_applications.user_id
            ORDER BY submission_date DESC";

    $result = $conn->query($sql);

    echo "<table>";

    echo "
    <tr>
        <th>Applicant</th>
        <th>Position</th>
        <th>Status</th>
        <th>Applied On</th>
    </tr>";

    if($result->num_rows > 0){

        while($row = $result->fetch_assoc()){

            $status = strtolower($row['application_status']);

            echo "
            <tr>
                <td>{$row['fullname']}</td>
                <td>{$row['position']}</td>
                <td><span class='badge {$status}'>{$row['application_status']}</span></td>
                <td>{$row['submission_date']}</td>
            </tr>";

        }

    }else{

        echo "<tr><td colspan='4'>No Applications Found.</td></tr>";

    }

    echo "</table>";

    break;


    /*====================================================
    SHORTLISTED
    ====================================================*/

    case 'shortlisted':

    $sql = "SELECT
                users.fullname,
                job_applications.position,
                job_applications.submission_date
            FROM job_applications
            INNER JOIN users
            ON users.user_id = job_applications.user_id
            WHERE application_status='Shortlisted'
            ORDER BY submission_date DESC";

    $result = $conn->query($sql);

    echo "<table>";

    echo "
    <tr>
        <th>Applicant</th>
        <th>Position</th>
        <th>Date</th>
    </tr>";

    if($result->num_rows > 0){

        while($row = $result->fetch_assoc()){

            echo "
            <tr>
                <td>{$row['fullname']}</td>
                <td>{$row['position']}</td>
                <td>{$row['submission_date']}</td>
            </tr>";

        }

    }else{

        echo "<tr><td colspan='3'>No Shortlisted Applicants.</td></tr>";

    }

    echo "</table>";

    break;


    /*====================================================
    REJECTED
    ====================================================*/

    case 'rejected':

    $sql = "SELECT
                users.fullname,
                job_applications.position,
                job_applications.submission_date
            FROM job_applications
            INNER JOIN users
            ON users.user_id = job_applications.user_id
            WHERE application_status='Rejected'
            ORDER BY submission_date DESC";

    $result = $conn->query($sql);

    echo "<table>";

    echo "
    <tr>
        <th>Applicant</th>
        <th>Position</th>
        <th>Date</th>
    </tr>";

    if($result->num_rows > 0){

        while($row = $result->fetch_assoc()){

            echo "
            <tr>
                <td>{$row['fullname']}</td>
                <td>{$row['position']}</td>
                <td>{$row['submission_date']}</td>
            </tr>";

        }

    }else{

        echo "<tr><td colspan='3'>No Rejected Applicants.</td></tr>";

    }

    echo "</table>";

    break;


    /*====================================================
    LEAVE REQUESTS
    ====================================================*/

    case 'leave':

    $sql = "SELECT
                users.fullname,
                consultant_leaves.leave_type,
                consultant_leaves.start_date,
                consultant_leaves.end_date,
                consultant_leaves.reason,
                consultant_leaves.status
            FROM consultant_leaves
            INNER JOIN users
            ON users.user_id = consultant_leaves.user_id
            ORDER BY consultant_leaves.created_at DESC";

    $result = $conn->query($sql);

    echo "<table>";

    echo "
    <tr>
        <th>Employee</th>
        <th>Leave Type</th>
        <th>Start Date</th>
        <th>End Date</th>
        <th>Reason</th>
        <th>Status</th>
    </tr>";

    if($result->num_rows > 0){

        while($row = $result->fetch_assoc()){

            $status = strtolower($row['status']);

            echo "
            <tr>
                <td>{$row['fullname']}</td>
                <td>{$row['leave_type']}</td>
                <td>{$row['start_date']}</td>
                <td>{$row['end_date']}</td>
                <td>{$row['reason']}</td>
                <td><span class='badge {$status}'>{$row['status']}</span></td>
            </tr>";

        }

    }else{

        echo "<tr><td colspan='6'>No Leave Requests Found.</td></tr>";

    }

    echo "</table>";

    break;


    /*====================================================
    INVALID TYPE
    ====================================================*/

    default:

    echo "<h3>Invalid Request.</h3>";

    }

?>

</div>