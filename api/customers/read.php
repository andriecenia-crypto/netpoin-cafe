<?php

require_once __DIR__ . '/../../config/headers.php';
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_json(false, null, "Method not allowed. Use GET.", 405);
}

if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = :id");
    $stmt->execute(['id' => $_GET['id']]);
    $customer = $stmt->fetch();

    if (!$customer) {
        send_json(false, null, "Customer not found.", 404);
    }
    send_json(true, $customer);
} else {
    $stmt = $pdo->query("SELECT * FROM customers ORDER BY customer_name ASC");
    $customers = $stmt->fetchAll();
    send_json(true, $customers);
}
