<?php
// Web-based inspector: open in browser to inspect invoice_items for an invoice id
// Usage: https://your-base-url/tools/inspect_invoice_items_web.php?id=123
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config.php';
header('Content-Type: text/html; charset=utf-8');
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    echo '<h2>Usage</h2><p>Provide an invoice id: ?id=123</p>'; exit;
}
$stmt = $conn->prepare("SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY id ASC");
if ($stmt === false) {
    echo '<h2>Error preparing statement</h2><pre>' . htmlspecialchars($conn->error) . '</pre>'; exit;
}
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$rows = [];
while ($r = $res->fetch_assoc()) { $rows[] = $r; }
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Invoice Items for <?php echo $id; ?></title></head><body>
<h1>Invoice Items for ID: <?php echo $id; ?></h1>
<?php if (empty($rows)): ?>
  <p><strong>No items found.</strong></p>
<?php else: ?>
  <table border="1" cellpadding="6" cellspacing="0">
    <tr><th>ID</th><th>Description</th><th>HSN</th><th>Qty</th><th>Rate</th><th>GST %</th><th>Amount</th><th>GST Amt</th><th>Line Total</th></tr>
    <?php foreach ($rows as $row): ?>
      <tr>
        <td><?php echo htmlspecialchars($row['id']); ?></td>
        <td><?php echo htmlspecialchars($row['description']); ?></td>
        <td><?php echo htmlspecialchars($row['hsn_code']); ?></td>
        <td><?php echo htmlspecialchars($row['quantity']); ?></td>
        <td><?php echo htmlspecialchars($row['rate']); ?></td>
        <td><?php echo htmlspecialchars($row['gst_percent']); ?></td>
        <td><?php echo htmlspecialchars($row['amount']); ?></td>
        <td><?php echo htmlspecialchars($row['gst_amount']); ?></td>
        <td><?php echo htmlspecialchars($row['line_total']); ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>
</body></html>
