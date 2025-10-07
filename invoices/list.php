<?php include __DIR__ . '/../includes/header.php'; ?>
<h2>Invoices</h2>

<?php
// ----- Filters and sorting (from GET) -----
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$country = $_GET['country'] ?? '';
$client_filter = isset($_GET['client_id']) ? (int)$_GET['client_id'] : 0;
$sort = $_GET['sort'] ?? 'date_desc';

// Validate dates
function valid_date($d) {
    if (!$d) return false;
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt && $dt->format('Y-m-d') === $d;
}
if (!valid_date($from)) $from = '';
if (!valid_date($to)) $to = '';

// build WHERE clauses
$where = [];
if ($client_filter) { $where[] = 'i.client_id=' . $client_filter; }
if ($from) { $where[] = "i.invoice_date >= '" . $conn->real_escape_string($from) . "'"; }
if ($to) { $where[] = "i.invoice_date <= '" . $conn->real_escape_string($to) . "'"; }
if ($country) { $where[] = "c.country = '" . $conn->real_escape_string($country) . "'"; }

// Sorting whitelist
$order = 'i.id DESC';
switch ($sort) {
    case 'date_asc': $order = 'i.invoice_date ASC'; break;
    case 'date_desc': $order = 'i.invoice_date DESC'; break;
    case 'country_asc': $order = 'c.country ASC'; break;
    case 'country_desc': $order = 'c.country DESC'; break;
    case 'amount_asc': $order = 'i.total_amount ASC'; break;
    case 'amount_desc': $order = 'i.total_amount DESC'; break;
}

// Fetch distinct countries for filter select
$countriesRes = $conn->query("SELECT DISTINCT IFNULL(NULLIF(country, ''), 'Unknown') AS country FROM clients ORDER BY country ASC");

// Pagination params
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = (int)($_GET['per_page'] ?? 25);
if ($per_page <= 0) $per_page = 25;

// Client summary (count + total) respecting date filters
$clientSummarySql = "SELECT c.id, c.name, COUNT(i.id) AS invoice_count, COALESCE(SUM(i.total_amount),0) AS total_amount
    FROM clients c LEFT JOIN invoices i ON i.client_id=c.id";
$summaryWhere = [];
if ($from) $summaryWhere[] = "i.invoice_date >= '" . $conn->real_escape_string($from) . "'";
if ($to) $summaryWhere[] = "i.invoice_date <= '" . $conn->real_escape_string($to) . "'";
if (!empty($summaryWhere)) $clientSummarySql .= ' AND ' . implode(' AND ', $summaryWhere);
$clientSummarySql .= ' GROUP BY c.id ORDER BY c.name ASC';
$clientSummaryRes = $conn->query($clientSummarySql);

// Build invoice list query
// Count total matching rows for pagination
$countSql = "SELECT COUNT(*) as cnt FROM invoices i JOIN clients c ON i.client_id=c.id";
if (!empty($where)) { $countSql .= ' WHERE ' . implode(' AND ', $where); }
$countRes = $conn->query($countSql);
$total = ($countRes && $countRes->fetch_assoc()) ? (int)$countRes->fetch_assoc()['cnt'] : 0;
$total_pages = (int)ceil($total / $per_page);
if ($page > $total_pages && $total_pages > 0) $page = $total_pages;
$offset = ($page - 1) * $per_page;

$sql = "SELECT i.*, c.name AS client_name, c.country AS client_country FROM invoices i JOIN clients c ON i.client_id=c.id";
if (!empty($where)) { $sql .= ' WHERE ' . implode(' AND ', $where); }
$sql .= ' ORDER BY ' . $order;
$sql .= " LIMIT " . intval($per_page) . " OFFSET " . intval($offset);
$res = $conn->query($sql);
?>

<!-- Filters form -->
<form method="get" class="filters" style="margin-bottom:1rem; display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
  <label>From <input type="date" name="from" value="<?php echo htmlspecialchars($from); ?>"></label>
  <label>To <input type="date" name="to" value="<?php echo htmlspecialchars($to); ?>"></label>
  <label>Country
    <select name="country">
      <option value="">-- All --</option>
      <?php while ($c = $countriesRes->fetch_assoc()): ?>
        <option value="<?php echo htmlspecialchars($c['country']); ?>" <?php echo ($c['country']==$country)?'selected':''; ?>><?php echo htmlspecialchars($c['country']); ?></option>
      <?php endwhile; ?>
    </select>
  </label>
  <label>Client
    <select name="client_id">
      <option value="0">-- All --</option>
      <?php
        $cr = $conn->query("SELECT id, name FROM clients ORDER BY name ASC");
        while ($cc = $cr->fetch_assoc()): ?>
        <option value="<?php echo (int)$cc['id']; ?>" <?php echo ($cc['id']==$client_filter)?'selected':''; ?>><?php echo htmlspecialchars($cc['name']); ?></option>
      <?php endwhile; ?>
    </select>
  </label>
  <label>Sort
    <select name="sort">
      <option value="date_desc" <?php echo ($sort=='date_desc')?'selected':''; ?>>Date (newest)</option>
      <option value="date_asc" <?php echo ($sort=='date_asc')?'selected':''; ?>>Date (oldest)</option>
      <option value="country_asc" <?php echo ($sort=='country_asc')?'selected':''; ?>>Country A→Z</option>
      <option value="country_desc" <?php echo ($sort=='country_desc')?'selected':''; ?>>Country Z→A</option>
      <option value="amount_desc" <?php echo ($sort=='amount_desc')?'selected':''; ?>>Amount (high→low)</option>
      <option value="amount_asc" <?php echo ($sort=='amount_asc')?'selected':''; ?>>Amount (low→high)</option>
    </select>
  </label>
  <button class="btn" type="submit">Apply</button>
  <a class="btn" href="<?= BASE_URL ?>/invoices/list.php">Reset</a>
  <a class="btn" href="<?= BASE_URL ?>/clients/summary.php">Client Summary</a>
</form>

<!-- Client summary -->
<!-- <h3>Client summary</h3>
<table class="table">
  <thead><tr><th>Client</th><th>Invoice Count</th><th>Total Amount</th></tr></thead>
  <tbody>
    <?php while ($cs = $clientSummaryRes->fetch_assoc()): ?>
      <tr>
        <td><a href="?client_id=<?php echo (int)$cs['id']; ?>"><?php echo htmlspecialchars($cs['name']); ?></a></td>
        <td><?php echo (int)$cs['invoice_count']; ?></td>
        <td><?php echo money_fmt($cs['total_amount']); ?></td>
      </tr>
    <?php endwhile; ?>
  </tbody>
</table> -->

<!-- Invoice list -->
<table class="table">
    <thead>
      <tr>
        <th>Invoice No.</th><th>Date</th><th>Client</th><th>Country</th><th>Total</th><th>Actions</th>
      </tr>
    </thead>
  <tbody>
    <?php while ($row = $res->fetch_assoc()): ?>
      <tr>
        <td><?php echo htmlspecialchars($row['invoice_no']); ?></td>
        <td><?php echo htmlspecialchars($row['invoice_date']); ?></td>
        <td><?php echo htmlspecialchars($row['client_name']); ?></td>
        <td><?php echo htmlspecialchars($row['client_country']); ?></td>
        <td><?php echo money_fmt($row['total_amount']); ?> <?php echo htmlspecialchars($row['currency']); ?></td>
        <td>
          <a class="btn" href="<?= BASE_URL ?>/invoices/view.php?id=<?php echo (int)$row['id']; ?>">View</a>
          <a class="btn" href="<?= BASE_URL ?>/invoices/print.php?id=<?php echo (int)$row['id']; ?>" target="_blank">Print</a>
          <a class="btn" href="<?= BASE_URL ?>/invoices/edit.php?id=<?php echo (int)$row['id']; ?>">Edit</a>
          <a class="btn danger" href="<?= BASE_URL ?>/invoices/delete.php?id=<?php echo (int)$row['id']; ?>"
             onclick="return confirmDelete('Delete this invoice?');">Delete</a>
        </td>
      </tr>
    <?php endwhile; ?>
  </tbody>
</table>

<!-- Pagination controls -->
<?php if ($total > 0): ?>
  <div style="display:flex; justify-content:space-between; align-items:center; margin-top:12px;">
    <div>
      Showing <?php echo ($offset+1); ?> - <?php echo min($offset + $per_page, $total); ?> of <?php echo $total; ?> invoices
    </div>
    <div>
      <form method="get" style="display:inline-block; margin-right:12px;">
        <?php foreach ($_GET as $k=>$v) { if ($k==='per_page' || $k==='page') continue; ?><input type="hidden" name="<?php echo htmlspecialchars($k); ?>" value="<?php echo htmlspecialchars($v); ?>"><?php } ?>
        <label>Per page
          <select name="per_page" onchange="this.form.submit()">
            <option value="10" <?php echo ($per_page==10)?'selected':''; ?>>10</option>
            <option value="25" <?php echo ($per_page==25)?'selected':''; ?>>25</option>
            <option value="50" <?php echo ($per_page==50)?'selected':''; ?>>50</option>
            <option value="100" <?php echo ($per_page==100)?'selected':''; ?>>100</option>
          </select>
        </label>
      </form>
      <nav style="display:inline-block">
        <?php
        $baseQuery = $_GET; unset($baseQuery['page']);
        $build_link = function($p) use ($baseQuery) {
          $q = $baseQuery; $q['page'] = $p; return '?' . http_build_query($q);
        };
        ?>
        <?php if ($page > 1): ?><a class="btn" href="<?php echo $build_link($page-1); ?>">&laquo; Prev</a><?php endif; ?>
        <?php
        // show a range of pages around current
        $start = max(1, $page - 3);
        $end = min($total_pages, $page + 3);
        for ($p=$start;$p<=$end;$p++): ?>
          <a class="btn" href="<?php echo $build_link($p); ?>" <?php echo ($p==$page)?'style="font-weight:700;"':''; ?>><?php echo $p; ?></a>
        <?php endfor; ?>
        <?php if ($page < $total_pages): ?><a class="btn" href="<?php echo $build_link($page+1); ?>">Next &raquo;</a><?php endif; ?>
      </nav>
    </div>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
