<?php include __DIR__ . '/../includes/header.php'; ?>
<?php
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
        $stmt = $conn->prepare("INSERT INTO clients (name, company, email, phone, country, address) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("ssssss", $name, $company, $email, $phone, $country, $address);
        if ($stmt->execute()) { set_flash('Client added'); header('Location: ' . BASE_URL . '/clients/list.php'); exit; }
        else { set_flash('Failed to add client', 'error'); }
    }
}
?>
<h2>Add Client</h2>
<form method="post" class="form">
  <label>Name*<input name="name" required></label>
  <label>Company<input name="company"></label>
  <label>Email<input name="email" type="email"></label>
  <label>Phone<input name="phone"></label>
  <label>Country<input name="country"></label>
  <label>Address<textarea name="address" rows="3"></textarea></label>
  <button class="btn primary" type="submit">Save</button>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>
