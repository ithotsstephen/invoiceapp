<?php include __DIR__ . '/../includes/header.php'; ?>
<?php
// Fetch clients for dropdown
$clients = $conn->query("SELECT id, name FROM clients ORDER BY name ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = (int)($_POST['client_id'] ?? 0);
    $invoice_date = $_POST['invoice_date'] ?: date('Y-m-d');
    $currency = sanitize($conn, $_POST['currency'] ?? 'INR');
  $approx_inr = null;
  if ($currency !== 'INR') {
    $approx_inr = (float)($_POST['approx_inr'] ?? 0);
  }
    $notes = sanitize($conn, $_POST['notes'] ?? '');
    $terms = sanitize($conn, $_POST['terms'] ?? '');

    if ($client_id <= 0) { set_flash('Please select a client', 'error'); }
    else {
        // Invoice number generation with FY and prevention of duplicates
        $invoice_no = next_invoice_number($conn, $COMPANY['prefix'], $invoice_date);

        // Items arrays
  $desc = $_POST['description'] ?? [];
  $hsn  = $_POST['hsn'] ?? [];
  // Quantity removed; assume quantity = 1 for each line
  $rate = $_POST['rate'] ?? [];
  $gstp = $_POST['gst_percent'] ?? [];

        $subtotal = 0; $total_gst = 0; $grand = 0;
        $items = [];

    for ($i=0; $i<count($desc); $i++) {
      $d = sanitize($conn, $desc[$i] ?? '');
      if ($d === '') continue;
      $h = sanitize($conn, $hsn[$i] ?? '');
      // quantity removed; default to 1
      $q = 1.0;
      $r = (float)($rate[$i] ?? 0);
      $g = (float)($gstp[$i] ?? 0);

      if ($r < 0 || $g < 0) { continue; }

      $amount = $q * $r; // effectively = $r
            $gst_amount = $amount * ($g/100.0);
            $line_total = $amount + $gst_amount;

            $subtotal += $amount;
            $total_gst += $gst_amount;
            $grand += $line_total;

      $items[] = compact('d','h','q','r','g','amount','gst_amount','line_total');
        }

        if (empty($items)) { set_flash('Add at least one valid line item', 'error'); }
        else {
            // Insert invoice (include approx_inr_value when provided)
      if ($approx_inr !== null && $approx_inr > 0) {
        $stmt = $conn->prepare("INSERT INTO invoices (invoice_no, invoice_date, client_id, currency, subtotal, total_gst, total_amount, approx_inr_value, notes, terms) VALUES (?,?,?,?,?,?,?,?,?,?)");
        // types: s, s, i, s, d, d, d, d, s, s
        $stmt->bind_param("ssisddddss", $invoice_no, $invoice_date, $client_id, $currency, $subtotal, $total_gst, $grand, $approx_inr, $notes, $terms);
      } else {
        $stmt = $conn->prepare("INSERT INTO invoices (invoice_no, invoice_date, client_id, currency, subtotal, total_gst, total_amount, notes, terms) VALUES (?,?,?,?,?,?,?,?,?)");
        // types: s, s, i, s, d, d, d, s, s
        $stmt->bind_param("ssisdddss", $invoice_no, $invoice_date, $client_id, $currency, $subtotal, $total_gst, $grand, $notes, $terms);
      }
            if ($stmt->execute()) {
                $invoice_id = $conn->insert_id;
                // Insert items
        $istmt = $conn->prepare("INSERT INTO invoice_items (invoice_id, description, hsn_code, quantity, rate, gst_percent, amount, gst_amount, line_total) VALUES (?,?,?,?,?,?,?,?,?)");
        foreach ($items as $it) {
          // types: i, s, s, d, d, d, d, d, d
          $istmt->bind_param("issdddddd", $invoice_id, $it['d'], $it['h'], $it['q'], $it['r'], $it['g'], $it['amount'], $it['gst_amount'], $it['line_total']);
          $istmt->execute();
        }
                set_flash('Invoice created: ' . $invoice_no);
                header('Location: ' . BASE_URL . '/invoices/view.php?id=' . $invoice_id);
                exit;
            } else {
                set_flash('Failed to create invoice', 'error');
            }
        }
    }
}
?>
<h2>Create Invoice</h2>
<form method="post" class="form" id="invoiceForm">
  <div class="row">
    <label>Client* 
      <select name="client_id" required>
        <option value="">-- Select --</option>
        <?php while ($c = $clients->fetch_assoc()): ?>
          <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
        <?php endwhile; ?>
      </select>
    </label>
    <label>Invoice Date* <input type="date" name="invoice_date" value="<?php echo date('Y-m-d'); ?>" required></label>
    <label>Currency 
      <select name="currency">
        <?php
          $currencies = ['AUD','USD','GBP','INR','SGD','CAD','EUR','AED','SAR','NZD','MYR','HKD'];
          $selCur = htmlspecialchars($_POST['currency'] ?? 'INR');
          foreach ($currencies as $cc) {
            echo '<option value="' . $cc . '"' . ($selCur===$cc? ' selected':'') . '>' . $cc . '</option>';
          }
        ?>
      </select>
    </label>
    <label id="approxInrLabel" style="display:none">Approx INR Value <input type="number" step="0.01" name="approx_inr" id="approx_inr" value="<?php echo htmlspecialchars($_POST['approx_inr'] ?? ''); ?>"></label>
  </div>

  <h3>Items</h3>
  <table class="table" id="itemsTable">
    <thead>
      <tr>
        <th>Description</th><th>HSN Code</th><th>Quantity</th><th>Rate</th><th>GST %</th><th>Amount</th><th>GST Amt</th><th>Line Total</th><th></th>
      </tr>
    </thead>
      <tbody>
        <tr>
          <td><input name="description[]" required></td>
          <td><input name="hsn[]"></td>
          <td><input type="number" step="0.01" min="0" name="quantity[]" required></td>
          <td><input type="number" step="0.01" min="0" name="rate[]" required></td>
          <td><input type="number" step="0.01" min="0" name="gst_percent[]" value="18"></td>
          <td class="line-total">0.00</td>
        <td><button type="button" class="btn" onclick="removeRow(this)">✕</button></td>
      </tr>
    </tbody>
  </table>
  <button type="button" class="btn" onclick="addRow()">+ Add Item</button>

  <div class="totals">
    <div>Subtotal: <span id="subtotal">0.00</span></div>
    <div>Total GST: <span id="total_gst">0.00</span></div>
    <div><strong>Grand Total:</strong> <span id="grand_total">0.00</span></div>
  </div>

  <label>Notes<textarea name="notes" rows="3"></textarea></label>
  <label>Terms & Conditions<textarea name="terms" rows="3"></textarea></label>

  <button class="btn primary" type="submit">Create Invoice</button>
</form>
<script>
// Simple line calc
function recalc() {
  let subtotal=0, total_gst=0, grand=0;
  document.querySelectorAll('#itemsTable tbody tr').forEach(tr => {
    const qty = parseFloat(tr.querySelector('input[name="quantity[]"]').value) || 0;
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
        <td><input type="number" step="0.01" min="0" name="quantity[]" required></td>
        <td><input type="number" step="0.01" min="0" name="rate[]" required></td>
        <td><input type="number" step="0.01" min="0" name="gst_percent[]" value="18"></td>
        <td class="line-total">0.00</td>
        <td><button type="button" class="btn" onclick="removeRow(this)">✕</button></td>`;
  document.querySelector('#itemsTable tbody').appendChild(tr);
}
function removeRow(btn) {
  btn.closest('tr').remove();
  recalc();
}
document.getElementById('itemsTable').addEventListener('input', recalc);
recalc();
// show/hide approx INR field when currency changes
function toggleApproxInr() {
  const cur = document.querySelector('select[name="currency"]').value;
  const lbl = document.getElementById('approxInrLabel');
  if (cur !== 'INR') { lbl.style.display = 'block'; } else { lbl.style.display = 'none'; }
}
document.querySelector('select[name="currency"]').addEventListener('change', toggleApproxInr);
toggleApproxInr();
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
