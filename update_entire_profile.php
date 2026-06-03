<?php
include('config.php');
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Error: You must be logged in to update your profile.");
}

$user_id = $_SESSION['user_id'];

$message = '';
$messageClass = 'success';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_entire_profile'])) {
    // Personal Information Update
    $fullname = trim($_POST['fullname']);
    $gender = trim($_POST['gender']);
    $dob = trim($_POST['dob']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    $sql = "UPDATE users SET fullname = ?, gender = ?, dob = ?, email = ?, phone = ?, address = ? WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssi", $fullname, $gender, $dob, $email, $phone, $address, $user_id);
    if (!$stmt->execute()) {
        $message = "Error updating personal information: " . $stmt->error;
        $messageClass = 'error';
    }
    $stmt->close();

    // Education Update - Delete existing and insert new
    $conn->query("DELETE FROM qualifications WHERE user_id = $user_id");

    if (isset($_POST['institution']) && is_array($_POST['institution'])) {
        $institutions = $_POST['institution'];
        $qualifications = $_POST['qualification_name'];
        $years = $_POST['year_completed'];

        for ($i = 0; $i < count($institutions); $i++) {
            if (!empty($institutions[$i]) && !empty($qualifications[$i])) {
                $sql = "INSERT INTO qualifications (user_id, qualification_name, institution, year_completed) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("issi", $user_id, $qualifications[$i], $institutions[$i], $years[$i]);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    // Skills Update
    $soft_skills = trim($_POST['soft_skills']);
    $technical_skills = trim($_POST['technical_skills']);

    // Check if skills exist, update or insert
    $sql = "SELECT COUNT(*) FROM skills WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        $sql = "UPDATE skills SET soft_skills = ?, technical_skills = ? WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $soft_skills, $technical_skills, $user_id);
    } else {
        $sql = "INSERT INTO skills (user_id, soft_skills, technical_skills) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $user_id, $soft_skills, $technical_skills);
    }
    if (!$stmt->execute()) {
        $message = "Error updating skills: " . $stmt->error;
        $messageClass = 'error';
    }
    $stmt->close();

    // Work Experience Update - Delete existing and insert new
    $conn->query("DELETE FROM work_experience WHERE user_id = $user_id");

    if (isset($_POST['position']) && is_array($_POST['position'])) {
        $positions = $_POST['position'];
        $companies = $_POST['company_name'];
        $durations = $_POST['duration'];
        $duties_list = $_POST['duties'];

        for ($i = 0; $i < count($positions); $i++) {
            if (!empty($positions[$i]) && !empty($companies[$i])) {
                $sql = "INSERT INTO work_experience (user_id, position, company_name, duration, duties) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("issss", $user_id, $positions[$i], $companies[$i], $durations[$i], $duties_list[$i]);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    // Update session variables if no errors
    if ($messageClass == 'success') {
        $_SESSION['fullname'] = $fullname;
        $_SESSION['email'] = $email;
        $_SESSION['dob'] = $dob;
        $_SESSION['phone'] = $phone;
        $_SESSION['gender'] = $gender;
        $_SESSION['address'] = $address;
        $_SESSION['soft_skills'] = $soft_skills;
        $_SESSION['technical_skills'] = $technical_skills;

        // For education and work, we can fetch and update session if needed, but for simplicity, skip or fetch later
        $message = "Profile updated successfully!";
    }

    $_SESSION['message'] = $message;
    $_SESSION['messageClass'] = $messageClass;

    header("Location: applicant.php");
    exit();
} else {
    header("Location: applicant.php");
    exit();
}
?>
