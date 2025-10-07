<?php $PRINT_MODE = $PRINT_MODE ?? false; if (!$PRINT_MODE) { include __DIR__ . '/../includes/header.php'; } ?>
<?php
$id = (int)($_GET['id'] ?? 0);
$inv = $conn->query("SELECT i.*, c.name c_name, c.company c_company, c.email c_email, c.phone c_phone, c.country c_country, c.address c_address
                     FROM invoices i JOIN clients c ON i.client_id=c.id WHERE i.id=$id")->fetch_assoc();
if (!$inv) { set_flash('Invoice not found', 'error'); header('Location: ' . BASE_URL . '/invoices/list.php'); exit; }
$stmt = $conn->prepare("SELECT id, description, hsn_code, rate, gst_percent, line_total FROM invoice_items WHERE invoice_id = ? ORDER BY id ASC");
$stmt->bind_param('i', $id);
$stmt->execute();

// Collect rows robustly: prefer get_result, otherwise bind_result fallback
$rows = [];
if (method_exists($stmt, 'get_result')) {
  $res = $stmt->get_result();
  if ($res) {
    while ($r = $res->fetch_assoc()) { $rows[] = $r; }
  }
} else {
  // bind_result fallback
  $stmt->store_result();
  $stmt->bind_result($col_id, $col_desc, $col_hsn, $col_rate, $col_gstp, $col_line_total);
  while ($stmt->fetch()) {
    $rows[] = [
      'id' => $col_id,
      'description' => $col_desc,
      'hsn_code' => $col_hsn,
      'rate' => $col_rate,
      'gst_percent' => $col_gstp,
      'line_total' => $col_line_total
    ];
  }
}
?>
<section class="invoice">
  <div class="invoice-header">
    <!-- <div class="title">INVOICE</div> -->
    <!-- <div class="meta">
      <div><strong>Invoice No.:</strong> <?php echo htmlspecialchars($inv['invoice_no']); ?></div>
      <div><strong>Date:</strong> <?php echo htmlspecialchars($inv['invoice_date']); ?></div>
    </div> -->
  </div>

   <div class="bill-to">
<img src="<?= BASE_URL ?>/assets/img/logo_new.png" alt="Company Logo" width="180" style="padding-top: 15px;">
    <br><h3 style="margin: 0; padding: 0; !important">iThots Technology Solutions Pvt. Ltd</h3>
#44, 37th Street, G.K.M Colony,<br>
Chennai - 600082.<br>
www.ithots.com | accounts@ithots.com<br>

  </div>


  <div class="bill-to">
    <p style="margin: 0; padding: 0; !important">Bill To:</p>
    <div><strong><?php echo htmlspecialchars($inv['c_name']); ?></strong></div>
    <?php if ($inv['c_company']) echo '<div>'.htmlspecialchars($inv['c_company']).'</div>'; ?>
    <?php if ($inv['c_address']) echo '<div>'.htmlspecialchars($inv['c_address']).'</div>'; ?>
     <?php if ($inv['c_country']) echo '<div>'.htmlspecialchars($inv['c_country']).'</div>'; ?>
     <br>
    <?php if ($inv['c_email']) echo '<div>'.htmlspecialchars($inv['c_email']).'</div>'; ?>
    <?php if ($inv['c_phone']) echo '<div>'.htmlspecialchars($inv['c_phone']).'</div>'; ?>
  </div>



 <div class="title" style="text-align: center; font-size: 14px; font-weight: bold;">INVOICE</div>





<div class="meta" style="padding-bottom: 15px; line-height: 1;">
      <div><strong>Invoice No.:</strong> <?php echo htmlspecialchars($inv['invoice_no']); ?></div>
      <div><strong>Date:</strong> <?php echo htmlspecialchars($inv['invoice_date']); ?></div>
    </div>




  <table class="table invoice-table">
    <colgroup>
      <col class="description">
      <col class="hsn">
      <col class="unit">
      <col class="gst">
      <col class="total">
    </colgroup>
    <thead>
      <tr>
        <th>Description</th><th>HSN Code</th><th>Unit Price</th><th>GST %</th><th>Line Total</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($rows)): ?>
        <?php foreach ($rows as $it): ?>
          <tr>
            <td><?php echo htmlspecialchars($it['description']); ?></td>
            <td><?php echo htmlspecialchars($it['hsn_code']); ?></td>
            <td><?php echo money_fmt($it['rate']); ?></td>
            <td><?php echo money_fmt($it['gst_percent']); ?></td>
            <td><?php echo money_fmt($it['line_total']); ?></td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="5" style="text-align:center; color:#666;">No line items found for this invoice.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>

  <div class="totals right">
    <div>Subtotal: <?php echo money_fmt($inv['subtotal']); ?> <?php echo htmlspecialchars($inv['currency']); ?></div>
    <?php if (strtoupper((string)($inv['currency'] ?? '')) === 'INR'): ?>
      <div>Total GST: <?php echo money_fmt($inv['total_gst']); ?> <?php echo htmlspecialchars($inv['currency']); ?></div>
    <?php endif; ?>
    <div class="grand"><strong>Total (<?php echo htmlspecialchars($inv['currency']); ?>):</strong> <?php echo money_fmt($inv['total_amount']); ?></div>
    <div class="amount-words" style="margin-top:8px; font-style:italic;">Amount in words: <strong><?php echo htmlspecialchars(amount_in_words($inv['total_amount'])); ?></strong></div>
  </div>

  <?php if ($inv['notes']): ?>
    <div class="notes"><strong>Notes:</strong><br><?php echo nl2br(htmlspecialchars($inv['notes'])); ?></div>
  <?php endif; ?>
 <!-- <div class="thanks" style="margin-top: 30px; line-height: 1.6; font-size: 14px;"> -->
  <div>
  PAN No: AADCI8476L<br>
  LUT No: AD330821001209K<br>
  GSTIN: 33AADCI8476L1Z3
  <br><br>
  
  <strong>Bank Details</strong><br>
  Cheques payable to Ithots Technology Solutions Pvt. Ltd. or Online Transfer
  <br><br>
  
 <strong> IThots Technology Solutions Pvt. Ltd.</strong><br>
  Bank Name: ICICI Bank<br>
  Account No: 218405000239<br>
  Branch: Kolathur Branch<br>
  IFSC Code: ICIC0002184<br>
  SWIFT Code: ICICINBB
</div>
    <div class="thanks">Thank you for your business!</div>
  <?php if ($inv['terms']): ?>
    <div class="terms"><strong>Terms &amp; Conditions:</strong><br><?php echo nl2br(htmlspecialchars($inv['terms'])); ?></div>
  <?php endif; ?>

  <div class="actions no-print">
    <a class="btn" href="<?= BASE_URL ?>/invoices/print.php?id=<?php echo (int)$inv['id']; ?>" target="_blank">Print / Download</a>
    <a class="btn" href="<?= BASE_URL ?>/invoices/list.php">Back</a>
  </div>
</section>
<?php if (!$PRINT_MODE) { include __DIR__ . '/../includes/footer.php'; } ?>
