<?php

require_once __DIR__ . '/../../config/headers.php';
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    send_json(false, null, "Method not allowed. Use PUT.", 405);
}

$id = $_GET['id'] ?? null;
if (!$id) {
    send_json(false, null, "id query parameter is required, e.g. checkout.php?id=5", 400);
}

$stmt = $pdo->prepare("SELECT * FROM rentals WHERE id = :id");
$stmt->execute(['id' => $id]);
$rental = $stmt->fetch();

if (!$rental) {
    send_json(false, null, "Rental not found.", 404);
}
if ($rental['status'] !== 'Ongoing') {
    send_json(false, null, "This rental is already {$rental['status']} and cannot be checked out again.", 409);
}

try {
    $pdo->beginTransaction();

    // Let the database do the time math - it's the source of truth for "now".
    // Minutes are rounded down (same as the old MySQL TIMESTAMPDIFF behaviour).
    $stmt = $pdo->prepare(
        "UPDATE rentals
         SET time_out = LOCALTIMESTAMP(0),
             duration_minutes = CAST(FLOOR(EXTRACT(EPOCH FROM (LOCALTIMESTAMP(0) - time_in)) / 60) AS integer),
             total_amount = ROUND(CAST(FLOOR(EXTRACT(EPOCH FROM (LOCALTIMESTAMP(0) - time_in)) / 60) / 60.0 AS numeric) * rate_per_hour, 2),
             status = 'Completed'
         WHERE id = :id"
    );
    $stmt->execute(['id' => $id]);

    $stmt = $pdo->prepare("UPDATE computers SET status = 'Available' WHERE id = :id");
    $stmt->execute(['id' => $rental['computer_id']]);

    $pdo->commit();

    // Fetch the now-completed rental so we can show the final bill in the response
    $stmt = $pdo->prepare("SELECT rental_code, duration_minutes, total_amount FROM rentals WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $result = $stmt->fetch();

    send_json(true, $result, "Rental checked out successfully.");

} catch (PDOException $e) {
    $pdo->rollBack();
    send_json(false, null, "Error checking out rental: " . $e->getMessage(), 500);
}
