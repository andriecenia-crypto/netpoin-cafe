<?php

require_once __DIR__ . '/../../config/headers.php';
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(false, null, "Method not allowed. Use POST.", 405);
}

$input = json_decode(file_get_contents("php://input"), true);

$customer_name  = trim($input['customer_name'] ?? '');
$contact_number = trim($input['contact_number'] ?? '');
$email          = trim($input['email'] ?? '');

// --- Validation ---
if ($customer_name === '') {
    send_json(false, null, "customer_name is required.", 400);
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    send_json(false, null, "email is not a valid email address.", 400);
}

// Store empty optional fields as NULL rather than empty strings,
// it's cleaner in the database and makes "no email on file" unambiguous.
$contact_number = $contact_number === '' ? null : $contact_number;
$email          = $email === '' ? null : $email;

try {
    $stmt = $pdo->prepare(
        "INSERT INTO customers (customer_name, contact_number, email)
         VALUES (:customer_name, :contact_number, :email)"
    );
    $stmt->execute([
        'customer_name'  => $customer_name,
        'contact_number' => $contact_number,
        'email'          => $email,
    ]);

    $newId = $pdo->lastInsertId('customers_id_seq');
    send_json(true, ["id" => $newId], "Customer created successfully.", 201);

} catch (PDOException $e) {
    send_json(false, null, "Error creating customer: " . $e->getMessage(), 500);
}
