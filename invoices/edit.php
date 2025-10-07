<?php include __DIR__ . '/../includes/header.php'; ?>
<?php
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { set_flash('Invalid invoice id', 'error'); header('Location: ' . BASE_URL . '/invoices/list.php'); exit; }

$inv = $conn->query("SELECT * FROM invoices WHERE id=$id")->fetch_assoc();
if (!$inv) { set_flash('Invoice not found', 'error'); header('Location: ' . BASE_URL . '/invoices/list.php'); exit; }
$clients = $conn->query("SELECT id, name FROM clients ORDER BY name ASC");
$itemsRes = $conn->query("SELECT * FROM invoice_items WHERE invoice_id=$id ORDER BY id ASC");
$items = [];
while ($r = $itemsRes->fetch_assoc()) { $items[] = $r; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic header fields
    $client_id = (int)($_POST['client_id'] ?? 0);
  $invoice_no = sanitize($conn, $_POST['invoice_no'] ?? $inv['invoice_no']);
    $invoice_date = $_POST['invoice_date'] ?? $inv['invoice_date'];
    $currency = sanitize($conn, $_POST['currency'] ?? $inv['currency']);
  $approx_inr = null;
  if ($currency !== 'INR') {
    $approx_inr = (float)($_POST['approx_inr'] ?? 0);
  }
    $notes = sanitize($conn, $_POST['notes'] ?? $inv['notes']);
    $terms = sanitize($conn, $_POST['terms'] ?? $inv['terms']);

    // Items
    $desc = $_POST['description'] ?? [];
    $hsn  = $_POST['hsn'] ?? [];
    $qty  = $_POST['quantity'] ?? [];
    $rate = $_POST['rate'] ?? [];
    $gstp = $_POST['gst_percent'] ?? [];

    $subtotal = 0; $total_gst = 0; $grand = 0;
    $newItems = [];
    for ($i=0; $i<count($desc); $i++) {
        $d = sanitize($conn, $desc[$i] ?? '');
        if ($d === '') continue;
        $h = sanitize($conn, $hsn[$i] ?? '');
        $q = (float)($qty[$i] ?? 0);
        $r = (float)($rate[$i] ?? 0);
        $g = (float)($gstp[$i] ?? 0);
        if ($q <= 0 || $r < 0 || $g < 0) continue;
        $amount = $q * $r;
        $gst_amount = $amount * ($g/100.0);
        $line_total = $amount + $gst_amount;
        $subtotal += $amount; $total_gst += $gst_amount; $grand += $line_total;
        $newItems[] = ['d'=>$d,'h'=>$h,'q'=>$q,'r'=>$r,'g'=>$g,'amount'=>$amount,'gst_amount'=>$gst_amount,'line_total'=>$line_total];
    }

    if ($client_id <= 0) { set_flash('Please select a client', 'error'); }
    elseif (empty($newItems)) { set_flash('Add at least one valid line item', 'error'); }
    else {
    // ensure invoice_no is unique (exclude current invoice)
    $chk = $conn->prepare("SELECT id FROM invoices WHERE invoice_no = ? AND id != ? LIMIT 1");
    $chk->bind_param('si', $invoice_no, $id);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows > 0) {
      set_flash('Invoice number already in use by another invoice', 'error');
    } else {
        // update invoice and replace items inside a transaction
        $conn->begin_transaction();
        try {
      if ($approx_inr !== null && $approx_inr > 0) {
        $ustmt = $conn->prepare("UPDATE invoices SET invoice_no=?, invoice_date=?, client_id=?, currency=?, subtotal=?, total_gst=?, total_amount=?, approx_inr_value=?, notes=?, terms=? WHERE id=?");
        $ustmt->bind_param("ssisddddssi", $invoice_no, $invoice_date, $client_id, $currency, $subtotal, $total_gst, $grand, $approx_inr, $notes, $terms, $id);
      } else {
        $ustmt = $conn->prepare("UPDATE invoices SET invoice_no=?, invoice_date=?, client_id=?, currency=?, subtotal=?, total_gst=?, total_amount=?, approx_inr_value=NULL, notes=?, terms=? WHERE id=?");
        $ustmt->bind_param("ssisdddssi", $invoice_no, $invoice_date, $client_id, $currency, $subtotal, $total_gst, $grand, $notes, $terms, $id);
      }
            if (!$ustmt->execute()) throw new Exception('Failed updating invoice');

            // remove old items
            $dstmt = $conn->prepare("DELETE FROM invoice_items WHERE invoice_id=?");
            $dstmt->bind_param('i', $id);
            $dstmt->execute();

            // insert new items
            $istmt = $conn->prepare("INSERT INTO invoice_items (invoice_id, description, hsn_code, quantity, rate, gst_percent, amount, gst_amount, line_total) VALUES (?,?,?,?,?,?,?,?,?)");
            foreach ($newItems as $it) {
                $istmt->bind_param("issdddddd", $id, $it['d'], $it['h'], $it['q'], $it['r'], $it['g'], $it['amount'], $it['gst_amount'], $it['line_total']);
                if (!$istmt->execute()) throw new Exception('Failed inserting item');
            }

            $conn->commit();
            set_flash('Invoice updated');
            header('Location: ' . BASE_URL . '/invoices/view.php?id=' . $id);
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            set_flash('Failed to update invoice: ' . $e->getMessage(), 'error');
        }
        }
    }
}
?>
<h2>Edit Invoice: <?php echo htmlspecialchars($inv['invoice_no']); ?></h2>
<form method="post" class="form" id="invoiceEditForm">
  <div class="row">
    <label>Invoice No <input name="invoice_no" value="<?php echo htmlspecialchars($inv['invoice_no']); ?>" required></label>
    <label>Client* 
      <select name="client_id" required>
        <option value="">-- Select --</option>
        <?php while ($c = $clients->fetch_assoc()): ?>
          <option value="<?php echo (int)$c['id']; ?>" <?php echo ($c['id']==$inv['client_id'])? 'selected':''; ?>><?php echo htmlspecialchars($c['name']); ?></option>
        <?php endwhile; ?>
      </select>
    </label>
    <label>Invoice Date* <input type="date" name="invoice_date" value="<?php echo htmlspecialchars($inv['invoice_date']); ?>" required></label>
    <label>Currency
      <select name="currency">
        <?php
          $currencies = ['AUD','USD','GBP','INR','SGD','CAD','EUR','AED','SAR','NZD','MYR','HKD'];
          $selCur = htmlspecialchars($_POST['currency'] ?? $inv['currency']);
          foreach ($currencies as $cc) {
            echo '<option value="' . $cc . '"' . ($selCur===$cc? ' selected':'') . '>' . $cc . '</option>';
          }
        ?>
      </select>
    </label>
    <label id="approxInrLabel" style="display:none">Approx INR Value <input type="number" step="0.01" name="approx_inr" id="approx_inr" value="<?php echo htmlspecialchars($inv['approx_inr_value'] ?? ''); ?>"></label>
  </div>

  <h3>Items</h3>
  <table class="table" id="itemsTable">
    <thead>
      <tr>
        <th>Description</th><th>HSN Code</th><th>Rate</th><th>GST %</th><th>Line Total</th><th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($items)): ?>
      <tr>
        <td><input name="description[]" required></td>
        <td><input name="hsn[]"></td>
        <td><input type="number" step="0.01" min="0" name="rate[]" required></td>
        <td><input type="number" step="0.01" min="0" name="gst_percent[]" value="18"></td>
        <td class="line-total">0.00</td>
        <td><button type="button" class="btn" onclick="removeRow(this)">�</button></td>
      </tr>
      <?php else: ?>
        <?php foreach ($items as $it): ?>
          <tr>
            <td><input name="description[]" value="<?php echo htmlspecialchars($it['description']); ?>" required></td>
            <td><input name="hsn[]" value="<?php echo htmlspecialchars($it['hsn_code']); ?>"></td>
            <td><input type="number" step="0.01" min="0" name="rate[]" value="<?php echo htmlspecialchars($it['rate']); ?>" required></td>
            <td><input type="number" step="0.01" min="0" name="gst_percent[]" value="<?php echo htmlspecialchars($it['gst_percent']); ?>"></td>
            <td class="line-total"><?php echo money_fmt($it['line_total']); ?></td>
            <td><button type="button" class="btn" onclick="removeRow(this)">�</button></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
  <button type="button" class="btn" onclick="addRow()">+ Add Item</button>

  <div class="totals">
    <div>Subtotal: <span id="subtotal">0.00</span></div>
    <div>Total GST: <span id="total_gst">0.00</span></div>
    <div><strong>Grand Total:</strong> <span id="grand_total">0.00</span></div>
  </div>

  <label>Notes<textarea name="notes" rows="3"><?php echo htmlspecialchars($inv['notes']); ?></textarea></label>
  <label>Terms & Conditions<textarea name="terms" rows="3"><?php echo htmlspecialchars($inv['terms']); ?></textarea></label>

  <button class="btn primary" type="submit">Update Invoice</button>
</form>
<script>
function recalc() {
  let subtotal=0, total_gst=0, grand=0;
  document.querySelectorAll('#itemsTable tbody tr').forEach(tr => {
    // quantity removed; assume 1
    const qty = 1.0;
    const rate = parseFloat(tr.querySelector('input[name="rate[]"]').value) || 0;
    const gstp = parseFloat(tr.querySelector('input[name="gst_percent[]"]').value) || 0;
    const amount = qty * rate;
    const gstAmt = amount * (gstp/100);
    const lineTotal = amount + gstAmt;
    const lineEl = tr.querySelector('.line-total'); if (lineEl) lineEl.textContent = lineTotal.toFixed(2);
    subtotal += amount; total_gst += gstAmt; grand += lineTotal;
  });
  document.getElementById('subtotal').textContent = subtotal.toFixed(2);
  document.getElementById('total_gst').textContent = total_gst.toFixed(2);
  document.getElementById('grand_total').textContent = grand.toFixed(2);
}
function addRow() {
  const tr = document.createElement('tr');
  tr.innerHTML = `<td><input name="description[]" required></td>
    <td><input name="hsn[]"></td>
    <td><input type="number" step="0.01" min="0" name="rate[]" required></td>
    <td><input type="number" step="0.01" min="0" name="gst_percent[]" value="18"></td>
    <td class="line-total">0.00</td>
        <td><button type="button" class="btn" onclick="removeRow(this)"></button></td>`;
  document.querySelector('#itemsTable tbody').appendChild(tr);
}
function removeRow(btn) {
  btn.closest('tr').remove();
  recalc();
}
document.getElementById('itemsTable').addEventListener('input', recalc);
recalc();
// show/hide approx INR field when currency changes
function toggleApproxInrEdit() {
  const sel = document.querySelector('select[name="currency"]');
  if (!sel) return;
  const val = sel.value;
  const lbl = document.getElementById('approxInrLabel');
  if (val !== 'INR') lbl.style.display = 'block'; else lbl.style.display = 'none';
}
const curSel = document.querySelector('select[name="currency"]');
if (curSel) { curSel.addEventListener('change', toggleApproxInrEdit); toggleApproxInrEdit(); }
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
