<?php

require_once __DIR__ . '/../../config/headers.php';
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(false, null, "Method not allowed. Use POST.", 405);
}

$input = json_decode(file_get_contents("php://input"), true);

$computer_id = $input['computer_id'] ?? null;
$customer_id = $input['customer_id'] ?? null;   // nullable - table allows it
$user_id     = $input['user_id'] ?? null;

// --- Validation ---
if (!$computer_id || !$user_id) {
    send_json(false, null, "computer_id and user_id are required.", 400);
}

// --- Check the computer exists and is available ---
$stmt = $pdo->prepare("SELECT id, hourly_rate, status FROM computers WHERE id = :id");
$stmt->execute(['id' => $computer_id]);
$computer = $stmt->fetch();

if (!$computer) {
    send_json(false, null, "Computer not found.", 404);
}
if ($computer['status'] !== 'Available') {
    send_json(false, null, "This computer is currently {$computer['status']} and cannot be rented right now.", 409);
}

// --- Check customer exists, if one was given ---
if ($customer_id) {
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE id = :id");
    $stmt->execute(['id' => $customer_id]);
    if (!$stmt->fetch()) {
        send_json(false, null, "Customer not found.", 404);
    }
}

// --- Check the staff user exists ---
$stmt = $pdo->prepare("SELECT id FROM users WHERE id = :id");
$stmt->execute(['id' => $user_id]);
if (!$stmt->fetch()) {
    send_json(false, null, "Staff user not found.", 404);
}

// Simple unique rental code, e.g. RNT-20260813-4821
$rental_code = 'RNT-' . date('Ymd') . '-' . random_int(1000, 9999);

try {
    // Start the transaction - nothing is permanently saved until commit()
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "INSERT INTO rentals (rental_code, computer_id, customer_id, user_id, time_in, rate_per_hour, status)
         VALUES (:rental_code, :computer_id, :customer_id, :user_id, LOCALTIMESTAMP(0), :rate_per_hour, 'Ongoing')"
    );
    $stmt->execute([
        'rental_code'   => $rental_code,
        'computer_id'   => $computer_id,
        'customer_id'   => $customer_id,
        'user_id'       => $user_id,
        'rate_per_hour' => $computer['hourly_rate'],
    ]);
    $rentalId = $pdo->lastInsertId('rentals_id_seq');

    $stmt = $pdo->prepare("UPDATE computers SET status = 'Occupied' WHERE id = :id");
    $stmt->execute(['id' => $computer_id]);

    // Both writes succeeded - make them permanent
    $pdo->commit();

    send_json(true, [
        "id"          => $rentalId,
        "rental_code" => $rental_code,
    ], "Rental started successfully.", 201);

} catch (PDOException $e) {
    // Something failed partway through - undo everything in this transaction
    $pdo->rollBack();
    send_json(false, null, "Error starting rental: " . $e->getMessage(), 500);
}
