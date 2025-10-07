<?php include __DIR__ . '/../includes/header.php';
// Print/export of clients with invoice counts and totals
$sort = $_GET['sort'] ?? 'name_asc';
$order = 'c.name ASC';
switch ($sort) {
    case 'amount_desc': $order = 'ci.inv_total DESC'; break;
    case 'amount_asc': $order = 'ci.inv_total ASC'; break;
    case 'country_asc': $order = 'c.country ASC'; break;
    case 'country_desc': $order = 'c.country DESC'; break;
}

$sql = "SELECT c.*, COALESCE(ci.inv_count,0) AS invoice_count, COALESCE(ci.inv_total,0) AS invoice_total
          FROM clients c LEFT JOIN (
            SELECT client_id, COUNT(*) AS inv_count,
              SUM(CASE WHEN currency = 'INR' THEN total_amount
                       WHEN approx_inr_value IS NOT NULL THEN approx_inr_value
                       ELSE 0 END) AS inv_total
            FROM invoices GROUP BY client_id
          ) ci ON ci.client_id = c.id
          ORDER BY " . $order;
$res = $conn->query($sql);

if (isset($_GET['format']) && $_GET['format'] === 'csv') {
    // output CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="clients.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID','Name','Company','Email','Phone','Country','Invoice Count','Total Invoiced']);
    while ($r = $res->fetch_assoc()) {
        fputcsv($out, [(int)$r['id'], $r['name'], $r['company'], $r['email'], $r['phone'], $r['country'], (int)$r['invoice_count'], (float)$r['invoice_total']]);
    }
    fclose($out);
    exit;
}
?>
<h2>Client List (Printable)</h2>
<div style="margin-bottom:12px;">
  <a class="btn" href="?format=csv">Download CSV</a>
  <a class="btn" href="javascript:window.print()">Print</a>
  <a class="btn" href="<?= BASE_URL ?>/clients/list.php">Back</a>
  <div style="margin-top:8px;">Sort: 
    <a href="?sort=name_asc">Name A→Z</a> | 
    <a href="?sort=amount_desc">Amount (high→low)</a> | 
    <a href="?sort=amount_asc">Amount (low→high)</a> | 
    <a href="?sort=country_asc">Country A→Z</a>
  </div>
</div>
<table class="table">
  <thead>
    <tr><th>ID</th><th>Name</th><th>Company</th><th>Email</th><th>Phone</th><th>Country</th><th>Invoices</th><th>Total</th></tr>
  </thead>
  <tbody>
    <?php while ($row = $res->fetch_assoc()): ?>
      <tr>
        <td><?php echo (int)$row['id']; ?></td>
        <td><?php echo htmlspecialchars($row['name']); ?></td>
        <td><?php echo htmlspecialchars($row['company']); ?></td>
        <td><?php echo htmlspecialchars($row['email']); ?></td>
        <td><?php echo htmlspecialchars($row['phone']); ?></td>
        <td><?php echo htmlspecialchars($row['country']); ?></td>
        <td><?php echo (int)$row['invoice_count']; ?></td>
        <td><?php echo money_fmt($row['invoice_total']); ?></td>
      </tr>
    <?php endwhile; ?>
  </tbody>
</table>

<?php include __DIR__ . '/../includes/footer.php'; ?>
