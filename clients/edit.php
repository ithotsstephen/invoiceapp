<?php include __DIR__ . '/../includes/header.php'; ?>
<?php
$id = (int)($_GET['id'] ?? 0);
$client = $conn->query("SELECT * FROM clients WHERE id=$id")->fetch_assoc();
if (!$client) { set_flash('Client not found', 'error'); header('Location: ' . BASE_URL . '/clients/list.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($conn, $_POST['name'] ?? '');
    $company = sanitize($conn, $_POST['company'] ?? '');
    $email = sanitize($conn, $_POST['email'] ?? '');
    $phone = sanitize($conn, $_POST['phone'] ?? '');
    $country = sanitize($conn, $_POST['country'] ?? '');
    $address = sanitize($conn, $_POST['address'] ?? '');

    if (!$name) { set_flash('Name is required', 'error'); }
    elseif ($email && !validate_email($email)) { set_flash('Invalid email format', 'error'); }
    else {
        $stmt = $conn->prepare("UPDATE clients SET name=?, company=?, email=?, phone=?, country=?, address=? WHERE id=?");
        $stmt->bind_param("ssssssi", $name, $company, $email, $phone, $country, $address, $id);
        if ($stmt->execute()) { set_flash('Client updated'); header('Location: ' . BASE_URL . '/clients/list.php'); exit; }
        else { set_flash('Failed to update client', 'error'); }
    }
}
?>
<h2>Edit Client</h2>
<form method="post" class="form">
  <label>Name*<input name="name" value="<?php echo htmlspecialchars($client['name']); ?>" required></label>
  <label>Company<input name="company" value="<?php echo htmlspecialchars($client['company']); ?>"></label>
  <label>Email<input name="email" type="email" value="<?php echo htmlspecialchars($client['email']); ?>"></label>
  <label>Phone<input name="phone" value="<?php echo htmlspecialchars($client['phone']); ?>"></label>
  <label>Country<input name="country" value="<?php echo htmlspecialchars($client['country']); ?>"></label>
  <label>Address<textarea name="address" rows="3"><?php echo htmlspecialchars($client['address']); ?></textarea></label>
  <button class="btn primary" type="submit">Save</button>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>
