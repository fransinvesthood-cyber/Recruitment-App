<?php
//Database Connection
include ('config.php');

//Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit"])) {
    $fullname = htmlspecialchars($_POST["fullname"]);
    $email = filter_var($_POST["email"], FILTER_VALIDATE_EMAIL);
    $subject = htmlspecialchars($_POST["subject"]);
    $message = htmlspecialchars($_POST["message"]);

    if ($email) {
        $stmt = $conn->prepare("INSERT INTO contact_messages (fullname, email, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $fullname, $email, $subject, $message);

        if ($stmt->execute()) {
            echo "<script>alert('Message sent successfully!!'); window.location.href='contact.php';</script>";
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Invalid email format!";
    }
}

$conn->close();
?>