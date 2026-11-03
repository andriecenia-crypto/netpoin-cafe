<?php

require_once __DIR__ . '/../../config/headers.php';
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    send_json(false, null, "Method not allowed. Use PUT.", 405);
}

$id = $_GET['id'] ?? null;
if (!$id) {
    send_json(false, null, "id query parameter is required, e.g. update.php?id=2", 400);
}

// Check it exists first
$check = $pdo->prepare("SELECT id FROM customers WHERE id = :id");
$check->execute(['id' => $id]);
if (!$check->fetch()) {
    send_json(false, null, "Customer not found.", 404);
}

$input = json_decode(file_get_contents("php://input"), true);

$allowedFields = ['customer_name', 'contact_number', 'email'];
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

if (isset($params['email']) && $params['email'] !== '' && !filter_var($params['email'], FILTER_VALIDATE_EMAIL)) {
    send_json(false, null, "email is not a valid email address.", 400);
}
if (isset($params['customer_name']) && trim($params['customer_name']) === '') {
    send_json(false, null, "customer_name cannot be empty.", 400);
}

try {
    $sql = "UPDATE customers SET " . implode(', ', $fieldsToUpdate) . " WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    send_json(true, null, "Customer updated successfully.");

} catch (PDOException $e) {
    send_json(false, null, "Error updating customer: " . $e->getMessage(), 500);
}
