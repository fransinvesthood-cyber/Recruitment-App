<?php
include('config.php');

$year = 2025;
$countryCode = 'ZA'; // South Africa
$url = "https://date.nager.at/api/v3/PublicHolidays/$year/$countryCode";

// Fetch API data
$response = file_get_contents($url);
if ($response === false) {
    die("Failed to fetch holiday data.");
}

$holidays = json_decode($response, true);
if (!is_array($holidays)) {
    die("Invalid API response.");
}

// Prepare insert with duplicate check
$stmt = $conn->prepare("INSERT IGNORE INTO public_holidays (holiday_date, holiday_name) VALUES (?, ?)");

foreach ($holidays as $holiday) {
    $date = $holiday['date']; // format: YYYY-MM-DD
    $name = $holiday['localName'];

    $stmt->bind_param("ss", $date, $name);
    $stmt->execute();
}

echo "Public holidays for $year have been inserted.";
?>