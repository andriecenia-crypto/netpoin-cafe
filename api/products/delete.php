<?php

require_once __DIR__ . '/../../config/headers.php';
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    send_json(false, null, "Method not allowed. Use DELETE.", 405);
}

$id = $_GET['id'] ?? null;
if (!$id) {
    send_json(false, null, "id query parameter is required.", 400);
}

$check = $pdo->prepare("SELECT id FROM products WHERE id = :id");
$check->execute(['id' => $id]);
if (!$check->fetch()) {
    send_json(false, null, "Product not found.", 404);
}

try {
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = :id");
    $stmt->execute(['id' => $id]);
    send_json(true, null, "Product deleted successfully.");
} catch (PDOException $e) {
    if ($e->getCode() == '23503') {
        send_json(false, null, "Cannot delete this product - it has existing sales records.", 409);
    }
    send_json(false, null, "Error deleting product: " . $e->getMessage(), 500);
}
