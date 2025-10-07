<?php include __DIR__ . '/includes/header.php'; ?>
<?php
$stats = [
  'clients' => $conn->query("SELECT COUNT(*) c FROM clients")->fetch_assoc()['c'] ?? 0,
  'invoices'=> $conn->query("SELECT COUNT(*) c FROM invoices")->fetch_assoc()['c'] ?? 0,
  'revenue' => $conn->query("SELECT COALESCE(SUM(total_amount),0) t FROM invoices")->fetch_assoc()['t'] ?? 0,
];
?>
<h2>Dashboard</h2>
<div class="grid">
  <div class="card">
    <div class="label">Total Clients</div>
    <div class="value"><?php echo (int)$stats['clients']; ?></div>
  </div>
  <div class="card">
    <div class="label">Invoices</div>
    <div class="value"><?php echo (int)$stats['invoices']; ?></div>
  </div>
  <div class="card">
    <div class="label">Revenue (<?php echo htmlspecialchars('INR'); ?>)</div>
    <div class="value"><?php echo money_fmt($stats['revenue']); ?></div>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
