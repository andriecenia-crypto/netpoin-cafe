<?php


require_once __DIR__ . '/../../config/headers.php';
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(false, null, "Method not allowed. Use POST.", 405);
}

$input = json_decode(file_get_contents("php://input"), true);

$computer_number = trim($input['computer_number'] ?? '');
$computer_name   = trim($input['computer_name'] ?? '');
$specifications  = trim($input['specifications'] ?? '');
$hourly_rate     = $input['hourly_rate'] ?? null;
$status          = $input['status'] ?? 'Available';

// --- Validation ---
if ($computer_number === '' || $hourly_rate === null) {
    send_json(false, null, "computer_number and hourly_rate are required.", 400);
}
if (!is_numeric($hourly_rate) || $hourly_rate < 0) {
    send_json(false, null, "hourly_rate must be a positive number.", 400);
}
$allowedStatus = ['Available', 'Occupied', 'Maintenance'];
if (!in_array($status, $allowedStatus)) {
    send_json(false, null, "status must be one of: " . implode(', ', $allowedStatus), 400);
}

try {
    $stmt = $pdo->prepare(
        "INSERT INTO computers (computer_number, computer_name, specifications, hourly_rate, status)
         VALUES (:computer_number, :computer_name, :specifications, :hourly_rate, :status)"
    );
    $stmt->execute([
        'computer_number' => $computer_number,
        'computer_name'   => $computer_name,
        'specifications'  => $specifications,
        'hourly_rate'     => $hourly_rate,
        'status'          => $status,
    ]);

    $newId = $pdo->lastInsertId('computers_id_seq');
    send_json(true, ["id" => $newId], "Computer created successfully.", 201);

} catch (PDOException $e) {
    // Duplicate computer_number will land here (UNIQUE constraint)
    if ($e->getCode() == '23505') {
        send_json(false, null, "A computer with that computer_number already exists.", 409);
    }
    send_json(false, null, "Error creating computer: " . $e->getMessage(), 500);
}
