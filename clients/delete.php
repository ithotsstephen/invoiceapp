<?php
include __DIR__ . '/../includes/header.php';
$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $stmt = $conn->prepare("DELETE FROM clients WHERE id=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) { set_flash('Client deleted'); }
    else { set_flash('Failed to delete client', 'error'); }
}
header('Location: ' . BASE_URL . '/clients/list.php'); exit;
