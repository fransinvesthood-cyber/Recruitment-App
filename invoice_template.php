<?php
// Embed company logo as base64
$imagePath = 'img/investhoodit-logo.jpeg';
$imageData = base64_encode(file_get_contents($imagePath));
$imageSrc = 'data:image/jpeg;base64,' . $imageData;
?>

<style>
    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 12px;
        margin: 20px;
        color: #333;
    }

    h2, h3, h4 {
        color: #667eea;
        margin-bottom: 10px;
    }

    .invoice-container {
        padding: 20px;
        border: 1px solid #ccc;
    }

    .company-info {
        display: flex;
        align-items: flex-start;
        gap: 10px;
    }

    .company-info img {
        height: 70px;
        display: block;
    }

    .consultant-info {
        margin-bottom: 20px;
        padding: 10px;
        background: #f5f5f5;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
        margin-bottom: 20px;
    }

    th, td {
        border: 1px solid #ddd;
        padding: 8px;
    }

    th {
        background-color: #667eea;
        color: white;
    }

    .totals {
        width: 100%;
        border-top: 2px solid #667eea;
        padding-top: 10px;
    }

    .totals td {
        padding: 6px;
    }

    .totals .label {
        text-align: right;
        font-weight: bold;
    }

    .notes {
        padding: 10px;
        background: #f8f9fa;
        margin-top: 20px;
    }
</style>

<h2 style="text-align: center; margin-bottom: 10px; color: #333;">Invoice Statement</h2>

<div class="invoice-container">

    <!-- Header: Company Info (Left) + Invoice Info (Right) -->
    <table style="width: 100%; margin-bottom: 20px; border-bottom: 2px solid #eee; padding-bottom: 10px;">
        <tr>
            <td style="width: 60%; vertical-align: top;">
                <div class="company-info">
                    <img src="<?php echo $imageSrc; ?>" alt="Company Logo" style="height: 130px; width: 130px;">
                    <div>
                        <p>136 2nd St, Randjespark<br>
                           Midrand, 1682<br>
                           South Africa</p>
                        <p>Phone: 068 246 0562<br>
                           Email: admin@investhoodit.co.za</p>
                    </div>
                </div>
            </td>
            <td style="width: 40%; text-align: right; vertical-align: top;">
                <div class="invoice-details">
                    <h3>Invoice Details</h3>
                    <p><strong>Invoice #:</strong> <?php echo htmlspecialchars($invoice['invoice_number']); ?></p>
                    <p><strong>Date:</strong> <?php echo date('M d, Y', strtotime($invoice['invoice_date'])); ?></p>
                    <p><strong>Due Date:</strong> <?php echo date('M d, Y', strtotime($invoice['due_date'])); ?></p>
                    <p><strong>Status:</strong> <?php echo ucfirst($invoice['status']); ?></p>
                </div>
            </td>
        </tr>
    </table>

    <!-- Consultant Info -->
    <div class="consultant-info">
        <h4>Bill To:</h4>
        <p><strong><?php echo htmlspecialchars($invoice['consultant_name']); ?></strong><br>
           <?php echo htmlspecialchars($invoice['consultant_email']); ?><br>
           <strong>Project:</strong> <?php echo htmlspecialchars($invoice['client_project']); ?>
        </p>
    </div>

    <!-- Itemized Table -->
    <?php if (!empty($items)): ?>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Project</th>
                <th>Description</th>
                <th>Hours</th>
                <th>Rate</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><?php echo date('M d, Y', strtotime($item['work_date'])); ?></td>
                <td><?php echo htmlspecialchars($item['client_project']); ?></td>
                <td><?php echo htmlspecialchars($item['description']); ?></td>
                <td><?php echo $item['hours_worked']; ?>h</td>
                <td>R<?php echo number_format($item['hourly_rate'], 2); ?></td>
                <td>R<?php echo number_format($item['amount'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p>No detailed items available for this invoice.</p>
    <?php endif; ?>

    <!-- Totals Section -->
    <table class="totals">
        <tr>
            <td class="label">Total Hours:</td>
            <td><?php echo $invoice['total_hours']; ?>h</td>
        </tr>
        <tr>
            <td class="label">Hourly Rate:</td>
            <td>R<?php echo number_format($invoice['hourly_rate'], 2); ?></td>
        </tr>
        <tr>
            <td class="label">Subtotal:</td>
            <td>R<?php echo number_format($subtotal, 2); ?></td>
        </tr>
        <tr>
            <td class="label">VAT (15%):</td>
            <td>R<?php echo number_format($tax_amount, 2); ?></td>
        </tr>
        <tr>
            <td class="label">Total Amount:</td>
            <td><strong>R<?php echo number_format($total_amount, 2); ?></strong></td>
        </tr>
    </table>

    <!-- Optional Notes -->
    <?php if (!empty($invoice['notes'])): ?>
    <div class="notes">
        <h4>Notes:</h4>
        <p><?php echo nl2br(htmlspecialchars($invoice['notes'])); ?></p>
    </div>
    <?php endif; ?>
</div>