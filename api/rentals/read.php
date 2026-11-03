<?php

require_once __DIR__ . '/../../config/headers.php';
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_json(false, null, "Method not allowed. Use GET.", 405);
}

if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM rentals WHERE id = :id");
    $stmt->execute(['id' => $_GET['id']]);
    $rental = $stmt->fetch();

    if (!$rental) {
        send_json(false, null, "Rental not found.", 404);
    }
    send_json(true, $rental);

} elseif (isset($_GET['status'])) {
    $allowedStatus = ['Ongoing', 'Completed', 'Cancelled'];
    if (!in_array($_GET['status'], $allowedStatus)) {
        send_json(false, null, "status must be one of: " . implode(', ', $allowedStatus), 400);
    }
    $stmt = $pdo->prepare("SELECT * FROM rentals WHERE status = :status ORDER BY time_in DESC");
    $stmt->execute(['status' => $_GET['status']]);
    send_json(true, $stmt->fetchAll());

} else {
    $stmt = $pdo->query("SELECT * FROM rentals ORDER BY time_in DESC");
    send_json(true, $stmt->fetchAll());
}
