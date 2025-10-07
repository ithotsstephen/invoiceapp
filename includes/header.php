<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/functions.php';
// Simple auth enforcement: require a logged-in user for all pages except auth endpoints
if (empty($_SESSION['user'])) {
  $script = $_SERVER['SCRIPT_NAME'] ?? '';
  if (!preg_match('#/auth/(login|logout)\.php$#', $script)) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Invoice App</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/styles.css">
  <?php confirm_dialog_js(); ?>
  <?php print_invoice_css(); ?>
</head>
<body>
  <header class="app-header">
    <div class="left">
      <?php 
        // Support both remote URLs and local paths for the logo
        $logo_path = $COMPANY['logo_path'] ?? '';
        $logoUrl = $logo_path;
        $showLogo = false;
        if (preg_match('#^https?://#i', $logo_path)) {
            $showLogo = true;
        } elseif ($logo_path) {
            $logoFs = BASE_PATH . '/' . ltrim($logo_path, '/');
            $logoUrl = BASE_URL . '/' . ltrim($logo_path, '/');
            if (file_exists($logoFs)) { $showLogo = true; }
        }
      ?>
      <?php if ($showLogo): ?>
        <img src="<?= htmlspecialchars($logoUrl) ?>" alt="Logo" class="logo">
      <?php else: ?>
        <div class="logo placeholder">LOGO</div>
      <?php endif; ?>
    </div>
    <div class="right">
      <h1 class="company-name"><?php echo htmlspecialchars($COMPANY['name']); ?></h1>
      <pre class="company-address"><?php echo htmlspecialchars($COMPANY['address']); ?></pre>
      <div><?php echo htmlspecialchars($COMPANY['email']); ?> | <?php echo htmlspecialchars($COMPANY['phone']); ?></div>
    </div>
  </header>

  <nav class="nav">
    <a href="<?= BASE_URL ?>/index.php">Dashboard</a>
    <a href="<?= BASE_URL ?>/clients/list.php">Clients</a>
    <a href="<?= BASE_URL ?>/clients/add.php">Add Client</a>
    <a href="<?= BASE_URL ?>/invoices/list.php">Invoices</a>
    <a href="<?= BASE_URL ?>/invoices/create.php">Create Invoice</a>
    <a href="<?= BASE_URL ?>/clients/summary.php">Client Summary</a>
    <a href="<?= BASE_URL ?>/auth/logout.php">Logout</a>
  </nav>
  <main class="container">
    <?php flash_message(); ?>
