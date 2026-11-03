<?php

require_once __DIR__ . '/../../config/headers.php';
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    send_json(false, null, "Method not allowed. Use DELETE.", 405);
}

$id = $_GET['id'] ?? null;
if (!$id) {
    send_json(false, null, "id query parameter is required, e.g. delete.php?id=2", 400);
}

$check = $pdo->prepare("SELECT id FROM customers WHERE id = :id");
$check->execute(['id' => $id]);
if (!$check->fetch()) {
    send_json(false, null, "Customer not found.", 404);
}

try {
    $stmt = $pdo->prepare("DELETE FROM customers WHERE id = :id");
    $stmt->execute(['id' => $id]);
    send_json(true, null, "Customer deleted successfully.");

} catch (PDOException $e) {
    // Fires if this customer has existing rental records (foreign key constraint)
    if ($e->getCode() == '23503') {
        send_json(false, null, "Cannot delete this customer - they have existing rental records.", 409);
    }
    send_json(false, null, "Error deleting customer: " . $e->getMessage(), 500);
}
