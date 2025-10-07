<?php
// Usage: php tools/inspect_invoice_items.php <invoice_id>
require_once __DIR__ . '/../config.php';

$argv_id = isset($argv[1]) ? (int)$argv[1] : 0;
if ($argv_id <= 0) {
    echo "Usage: php tools/inspect_invoice_items.php <invoice_id>\n";
    exit(1);
}

$id = $argv_id;
$stmt = $conn->prepare("SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY id ASC");
if ($stmt === false) {
    echo "Prepare failed: " . $conn->error . "\n";
    exit(1);
}
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$rows = [];
while ($r = $res->fetch_assoc()) { $rows[] = $r; }
if (empty($rows)) {
    echo "No items found for invoice id: $id\n";
    exit(0);
}
foreach ($rows as $row) {
    echo "Item ID: " . $row['id'] . "\n";
    echo "  Description: " . $row['description'] . "\n";
    echo "  HSN: " . $row['hsn_code'] . "\n";
    echo "  Qty: " . $row['quantity'] . "\n";
    echo "  Rate: " . $row['rate'] . "\n";
    echo "  GST%: " . $row['gst_percent'] . "\n";
    echo "  Amount: " . $row['amount'] . "\n";
    echo "  GST Amt: " . $row['gst_amount'] . "\n";
    echo "  Line Total: " . $row['line_total'] . "\n";
    echo str_repeat('-',40) . "\n";
}
