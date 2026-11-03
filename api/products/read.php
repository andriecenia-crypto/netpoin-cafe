<?php

require_once __DIR__ . '/../../config/headers.php';
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_json(false, null, "Method not allowed. Use GET.", 405);
}

if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
    $stmt->execute(['id' => $_GET['id']]);
    $product = $stmt->fetch();

    if (!$product) {
        send_json(false, null, "Product not found.", 404);
    }
    send_json(true, $product);
} else {
    $stmt = $pdo->query("SELECT * FROM products ORDER BY product_name ASC");
    send_json(true, $stmt->fetchAll());
}
