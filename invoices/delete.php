<?php
include __DIR__ . '/../includes/header.php';
$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $stmt = $conn->prepare("DELETE FROM invoices WHERE id=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) { set_flash('Invoice deleted'); }
    else { set_flash('Failed to delete invoice', 'error'); }
}
header('Location: ' . BASE_URL . '/invoices/list.php'); exit;
