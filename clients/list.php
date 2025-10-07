<?php include __DIR__ . '/../includes/header.php'; ?>
<h2>Clients</h2>
<a class="btn" href="<?= BASE_URL ?>/clients/print.php">Print / Download</a>
<?php
// Pagination params
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = max(5, (int)($_GET['per_page'] ?? 10));
$offset = ($page - 1) * $per_page;
// Sorting
$sort = $_GET['sort'] ?? 'id_desc';
$order = 'c.id DESC';
switch ($sort) {
  case 'country_asc': $order = 'c.country ASC'; break;
  case 'country_desc': $order = 'c.country DESC'; break;
  case 'invoices_asc': $order = 'ci.inv_count ASC'; break;
  case 'invoices_desc': $order = 'ci.inv_count DESC'; break;
  case 'amount_asc': $order = 'ci.inv_total ASC'; break;
  case 'amount_desc': $order = 'ci.inv_total DESC'; break;
}
?>
<div style="margin-bottom:8px; display:flex; gap:8px; align-items:center;">
  <form method="get" style="display:inline-block;">
    <label>Per page:
      <select name="per_page" onchange="this.form.submit()">
        <option value="5" <?php echo $per_page==5?'selected':''; ?>>5</option>
        <option value="10" <?php echo $per_page==10?'selected':''; ?>>10</option>
        <option value="25" <?php echo $per_page==25?'selected':''; ?>>25</option>
        <option value="50" <?php echo $per_page==50?'selected':''; ?>>50</option>
      </select>
    </label>
  </form>
</div>
<div style="margin-bottom:8px; display:flex; gap:8px; align-items:center;">
  
  <!-- <div style="margin-left:8px">Sort: 
    <a href="?sort=amount_desc">Amount ↓</a> | <a href="?sort=amount_asc">Amount ↑</a> | <a href="?sort=country_asc">Country</a>
  </div>
</div> -->
<table class="table">
  <thead>
    <tr>
      <th>ID</th><th>Name</th><th>Company</th><th>Email</th><th>Phone</th>
      <?php
        // helper to build sort links preserving per_page and page
        $baseQs = $_GET; unset($baseQs['sort']);
        function sort_link($label, $key, $baseQs) {
          $q = $baseQs; $q['sort'] = $key;
          // determine indicator
          $indicator = '';
          global $sort;
          if ($sort === $key) $indicator = ' ▲';
          // when key is the inverse (e.g. country_desc vs country_asc) show proper arrow
          if ($sort === preg_replace('/_asc$/','_desc',$key)) $indicator = ' ▼';
          return '<a href="?' . http_build_query($q) . '">' . $label . $indicator . '</a>';
        }
      ?>
      <th><?php echo sort_link('Country', ($sort=='country_asc'?'country_desc':'country_asc'), $baseQs); ?></th>
      <th><?php echo sort_link('Invoices', ($sort=='invoices_asc'?'invoices_desc':'invoices_asc'), $baseQs); ?></th>
      <th><?php echo sort_link('Total Invoiced', ($sort=='amount_asc'?'amount_desc':'amount_asc'), $baseQs); ?></th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php
    // Fetch client list with invoice counts and totals (with pagination)
    $sql = "SELECT c.*, COALESCE(ci.inv_count,0) AS invoice_count, COALESCE(ci.inv_total,0) AS invoice_total
              FROM clients c LEFT JOIN (
                SELECT client_id, COUNT(*) AS inv_count,
                  SUM(CASE WHEN currency = 'INR' THEN total_amount
                           WHEN approx_inr_value IS NOT NULL THEN approx_inr_value
                           ELSE 0 END) AS inv_total
                FROM invoices GROUP BY client_id
              ) ci ON ci.client_id = c.id
              ORDER BY " . $order . "
              LIMIT " . intval($per_page) . " OFFSET " . intval($offset);
    $res = $conn->query($sql);
    while ($row = $res->fetch_assoc()): ?>
      <tr>
        <td><?php echo (int)$row['id']; ?></td>
        <td><?php echo htmlspecialchars($row['name']); ?></td>
        <td><?php echo htmlspecialchars($row['company']); ?></td>
        <td><?php echo htmlspecialchars($row['email']); ?></td>
        <td><?php echo htmlspecialchars($row['phone']); ?></td>
        <td><?php echo htmlspecialchars($row['country']); ?></td>
        <td><a href="<?= BASE_URL ?>/invoices/list.php?client_id=<?php echo (int)$row['id']; ?>"><?php echo (int)$row['invoice_count']; ?></a></td>
        <td><?php echo money_fmt($row['invoice_total']); ?></td>
        <td>
          <a class="btn" href="<?= BASE_URL ?>/clients/edit.php?id=<?php echo (int)$row['id']; ?>">Edit</a>
          <a class="btn danger" href="<?= BASE_URL ?>/clients/delete.php?id=<?php echo (int)$row['id']; ?>"
             onclick="return confirmDelete('Delete this client? Invoices will remain but new ones can’t link.');">Delete</a>
        </td>
      </tr>
    <?php endwhile; ?>
  </tbody>
</table>
<?php
$countRes = $conn->query("SELECT COUNT(*) AS c FROM clients");
$totalClients = $countRes->fetch_assoc()['c'] ?? 0;
$totalPages = max(1, ceil($totalClients / $per_page));
?>
<div class="pagination" style="margin-top:12px;">
  <?php if ($page > 1): ?>
    <a class="btn" href="?page=<?php echo $page-1; ?>&per_page=<?php echo $per_page; ?>">&laquo; Prev</a>
  <?php endif; ?>
  Page <?php echo $page; ?> of <?php echo $totalPages; ?>
  <?php if ($page < $totalPages): ?>
    <a class="btn" href="?page=<?php echo $page+1; ?>&per_page=<?php echo $per_page; ?>">Next &raquo;</a>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
