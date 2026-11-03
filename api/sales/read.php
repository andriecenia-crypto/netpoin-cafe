<?php


require_once __DIR__ . '/../../config/headers.php';
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_json(false, null, "Method not allowed. Use GET.", 405);
}

if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM sales WHERE id = :id");
    $stmt->execute(['id' => $_GET['id']]);
    $sale = $stmt->fetch();

    if (!$sale) {
        send_json(false, null, "Sale not found.", 404);
    }
    send_json(true, $sale);
} else {
    $stmt = $pdo->query("SELECT * FROM sales ORDER BY created_at DESC");
    send_json(true, $stmt->fetchAll());
}
