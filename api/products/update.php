<?php

require_once __DIR__ . '/../../config/headers.php';
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    send_json(false, null, "Method not allowed. Use PUT.", 405);
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

$input = json_decode(file_get_contents("php://input"), true);

$allowedFields = ['product_name', 'category', 'price', 'stock_quantity'];
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
if (isset($params['price']) && (!is_numeric($params['price']) || $params['price'] < 0)) {
    send_json(false, null, "price must be a positive number.", 400);
}
if (isset($params['stock_quantity']) && (!is_numeric($params['stock_quantity']) || $params['stock_quantity'] < 0)) {
    send_json(false, null, "stock_quantity must be zero or more.", 400);
}

try {
    $sql = "UPDATE products SET " . implode(', ', $fieldsToUpdate) . " WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    send_json(true, null, "Product updated successfully.");
} catch (PDOException $e) {
    send_json(false, null, "Error updating product: " . $e->getMessage(), 500);
}
