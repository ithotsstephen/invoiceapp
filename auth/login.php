<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config.php';
// Simple hard-coded login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username === 'accounts@ithots.com' && $password === 'Ithots*123!@#') {
        $_SESSION['user'] = $username;
        header('Location: ' . BASE_URL . '/');
        exit;
    } else {
        $error = 'Invalid username or password';
    }
}
?><!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Login</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/styles.css">
  <style>body{background:#f5f5f5;font-family:Arial} .login{width:360px;margin:60px auto;background:#fff;padding:20px;border:1px solid #e5e5e5}</style>
</head>
<body>
  <div class="login">
    <h2>Sign in</h2>
    <?php if ($error): ?><div class="flash error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <form method="post">
      <label>Username <input name="username" type="email" value="<?= htmlspecialchars($_POST['username'] ?? 'accounts@ithots.com') ?>" required></label>
      <label>Password <input name="password" type="password" required></label>
      <div style="margin-top:10px"><button class="btn primary" type="submit">Login</button></div>
    </form>
  </div>
</body>
</html>
