<?php include __DIR__ . '/../includes/header.php'; ?>
<?php
// Client summary page with optional from/to date filters
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';

function valid_date($d) {
    if (!$d) return false;
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt && $dt->format('Y-m-d') === $d;
}
if (!valid_date($from)) $from = '';
if (!valid_date($to)) $to = '';

$clientSummarySql = "SELECT c.id, c.name, COUNT(i.id) AS invoice_count, COALESCE(SUM(i.total_amount),0) AS total_amount
    FROM clients c LEFT JOIN invoices i ON i.client_id=c.id";
$summaryWhere = [];
if ($from) $summaryWhere[] = "i.invoice_date >= '" . $conn->real_escape_string($from) . "'";
if ($to) $summaryWhere[] = "i.invoice_date <= '" . $conn->real_escape_string($to) . "'";
if (!empty($summaryWhere)) $clientSummarySql .= ' AND ' . implode(' AND ', $summaryWhere);
$clientSummarySql .= ' GROUP BY c.id ORDER BY c.name ASC';
$clientSummaryRes = $conn->query($clientSummarySql);
?>
<h2>Client Summary</h2>
<form method="get" style="margin-bottom:1rem; display:flex; gap:8px; align-items:center;">
  <label>From <input type="date" name="from" value="<?php echo htmlspecialchars($from); ?>"></label>
  <label>To <input type="date" name="to" value="<?php echo htmlspecialchars($to); ?>"></label>
  <button class="btn" type="submit">Apply</button>
  <a class="btn" href="<?= BASE_URL ?>/clients/summary.php">Reset</a>
</form>

<table class="table">
  <thead><tr><th>Client</th><th>Invoice Count</th><th>Total Amount</th><th></th></tr></thead>
  <tbody>
    <?php while ($cs = $clientSummaryRes->fetch_assoc()): ?>
      <tr>
        <td><?php echo htmlspecialchars($cs['name']); ?></td>
        <td><?php echo (int)$cs['invoice_count']; ?></td>
         <td><?php echo money_fmt($row['invoice_total']); ?></td>
        <td><a class="btn" href="<?= BASE_URL ?>/invoices/list.php?client_id=<?php echo (int)$cs['id']; ?>&from=<?php echo htmlspecialchars($from); ?>&to=<?php echo htmlspecialchars($to); ?>">View Invoices</a></td>
      </tr>
    <?php endwhile; ?>
  </tbody>
</table>

<?php include __DIR__ . '/../includes/footer.php'; ?>
