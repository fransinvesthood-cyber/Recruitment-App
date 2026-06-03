<?php
require 'vendor/autoload.php'; // Adjust if you didn't install via Composer

use Dompdf\Dompdf;
use Dompdf\Options;

session_start();
include('config.php');

if (!isset($_SESSION['user_id']) || !isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Unauthorized or invalid request.");
}

$invoice_id = $_GET['id'];

// Fetch invoice details
$invoice_sql = "SELECT i.*, u.fullname as consultant_name, u.email as consultant_email
                FROM invoices i 
                JOIN users u ON i.user_id = u.user_id 
                WHERE i.invoice_id = ?";
$invoice_stmt = $conn->prepare($invoice_sql);
$invoice_stmt->bind_param("i", $invoice_id);
$invoice_stmt->execute();
$invoice_result = $invoice_stmt->get_result();

if ($invoice_result->num_rows === 0) {
    die("Invoice not found.");
}

$invoice = $invoice_result->fetch_assoc();

// Fetch invoice items
$items_sql = "SELECT ii.*, ct.work_date, ct.client_project, ct.description
              FROM invoice_items ii
              JOIN consultant_timesheets ct ON ii.timesheet_id = ct.consult_timesheet_id
              WHERE ii.invoice_id = ?
              ORDER BY ct.work_date";
$items_stmt = $conn->prepare($items_sql);
$items_stmt->bind_param("i", $invoice_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();

$items = [];
while ($row = $items_result->fetch_assoc()) {
    $items[] = $row;
}

// Totals
$subtotal = $invoice['amount_due'];
$tax_rate = 0.15;
$tax_amount = $subtotal * $tax_rate;
$total_amount = $subtotal + $tax_amount;

// Generate HTML content
ob_start();
include('invoice_template.php'); // This will contain the same HTML code from your invoice page
$html = ob_get_clean();

// Configure Dompdf
$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);

// Set paper size and orientation
$dompdf->setPaper('A4', 'portrait');

// Render and output PDF
$dompdf->render();
$dompdf->stream("invoice_" . $invoice['invoice_number'] . ".pdf", ["Attachment" => 1]);
exit;
?>