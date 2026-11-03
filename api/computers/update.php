<?php

require_once __DIR__ . '/../../config/headers.php';
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    send_json(false, null, "Method not allowed. Use PUT.", 405);
}

$id = $_GET['id'] ?? null;
if (!$id) {
    send_json(false, null, "id query parameter is required, e.g. update.php?id=3", 400);
}

// Check it exists first
$check = $pdo->prepare("SELECT id FROM computers WHERE id = :id");
$check->execute(['id' => $id]);
if (!$check->fetch()) {
    send_json(false, null, "Computer not found.", 404);
}

$input = json_decode(file_get_contents("php://input"), true);

// Build the SET clause dynamically from whichever fields were sent,
// so the client doesn't have to resend every field every time.
$allowedFields = ['computer_number', 'computer_name', 'specifications', 'hourly_rate', 'status'];
$fieldsToUpdate = [];
$params = ['id' => $id];

foreach ($allowedFields as $field) {
    if (array_key_exists($field, $input)) {
        $fieldsToUpdate[] = "$field = :$field";
        $params[$field] = $input[$field];
    }
}

if (empty($fieldsToUpdate)) {
    send_json(false, null, "No valid fields provided to update.", 400);
}

if (isset($params['status']) && !in_array($params['status'], ['Available', 'Occupied', 'Maintenance'])) {
    send_json(false, null, "status must be one of: Available, Occupied, Maintenance", 400);
}

try {
    $sql = "UPDATE computers SET " . implode(', ', $fieldsToUpdate) . " WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    send_json(true, null, "Computer updated successfully.");

} catch (PDOException $e) {
    if ($e->getCode() == '23505') {
        send_json(false, null, "A computer with that computer_number already exists.", 409);
    }
    send_json(false, null, "Error updating computer: " . $e->getMessage(), 500);
}
