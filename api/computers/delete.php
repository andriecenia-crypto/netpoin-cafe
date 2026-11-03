<?php

require_once __DIR__ . '/../../config/headers.php';
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    send_json(false, null, "Method not allowed. Use DELETE.", 405);
}

$id = $_GET['id'] ?? null;
if (!$id) {
    send_json(false, null, "id query parameter is required, e.g. delete.php?id=3", 400);
}

$check = $pdo->prepare("SELECT id FROM computers WHERE id = :id");
$check->execute(['id' => $id]);
if (!$check->fetch()) {
    send_json(false, null, "Computer not found.", 404);
}

try {
    $stmt = $pdo->prepare("DELETE FROM computers WHERE id = :id");
    $stmt->execute(['id' => $id]);
    send_json(true, null, "Computer deleted successfully.");

} catch (PDOException $e) {
    // This fires if the computer has rental history (foreign key constraint)
    if ($e->getCode() == '23503') {
        send_json(false, null, "Cannot delete this computer - it has existing rental records. Consider setting its status to 'Maintenance' instead.", 409);
    }
    send_json(false, null, "Error deleting computer: " . $e->getMessage(), 500);
}
