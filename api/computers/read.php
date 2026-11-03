<?php


require_once __DIR__ . '/../../config/headers.php';
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_json(false, null, "Method not allowed. Use GET.", 405);
}

if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM computers WHERE id = :id");
    $stmt->execute(['id' => $_GET['id']]);
    $computer = $stmt->fetch();

    if (!$computer) {
        send_json(false, null, "Computer not found.", 404);
    }
    send_json(true, $computer);
} else {
    $stmt = $pdo->query("SELECT * FROM computers ORDER BY computer_number ASC");
    $computers = $stmt->fetchAll();
    send_json(true, $computers);
}
