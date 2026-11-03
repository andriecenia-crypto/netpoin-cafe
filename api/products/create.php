<?php

require_once __DIR__ . '/../../config/headers.php';
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(false, null, "Method not allowed. Use POST.", 405);
}

$input = json_decode(file_get_contents("php://input"), true);

$product_name   = trim($input['product_name'] ?? '');
$category       = trim($input['category'] ?? '');
$price          = $input['price'] ?? null;
$stock_quantity = $input['stock_quantity'] ?? 0;

if ($product_name === '' || $price === null) {
    send_json(false, null, "product_name and price are required.", 400);
}
if (!is_numeric($price) || $price < 0) {
    send_json(false, null, "price must be a positive number.", 400);
}
if (!is_numeric($stock_quantity) || $stock_quantity < 0) {
    send_json(false, null, "stock_quantity must be zero or more.", 400);
}

try {
    $stmt = $pdo->prepare(
        "INSERT INTO products (product_name, category, price, stock_quantity)
         VALUES (:product_name, :category, :price, :stock_quantity)"
    );
    $stmt->execute([
        'product_name'   => $product_name,
        'category'       => $category ?: null,
        'price'          => $price,
        'stock_quantity' => $stock_quantity,
    ]);

    send_json(true, ["id" => $pdo->lastInsertId('products_id_seq')], "Product created successfully.", 201);

} catch (PDOException $e) {
    send_json(false, null, "Error creating product: " . $e->getMessage(), 500);
}
