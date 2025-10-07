<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/functions.php';
$PRINT_MODE = true;
$id = (int)($_GET['id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Print Invoice</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/styles.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/print.css" media="print">
  <style>
    .nav, .app-footer, .actions, .no-print, .app-header { display:none !important; }
    @media print { @page { margin: 12mm; } .container { padding: 0; } }
  </style>
  <style>
    /* Ensure fixed table layout and column widths for PDF/print output */
    .invoice-table { table-layout: fixed; width: 100%; }
    .invoice-table td, .invoice-table th { word-wrap: break-word; overflow-wrap: break-word; }

    /* Column widths (sum to 100%) - adjust percentages as needed */
    .invoice-table col.description { width: 50%; }
    .invoice-table col.hsn         { width: 12%; }
    .invoice-table col.unit        { width: 12%; }
    .invoice-table col.gst         { width: 8%; }
    .invoice-table col.total       { width: 18%; }

    /* Slightly reduce font-size on print to improve layout */
    @media print {
      body { font-size: 12px; }
      .invoice-table th, .invoice-table td { font-size: 11px; }
    }
  </style>
</head>
<body>
  <div class="container">
    <?php include __DIR__ . '/view.php'; ?>
  </div>
  <script>window.onload = () => window.print();</script>
</body>
</html>
