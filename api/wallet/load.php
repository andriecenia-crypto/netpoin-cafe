<?php

require_once __DIR__ . '/../../config/headers.php';
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(false, null, "Method not allowed. Use POST.", 405);
}

$input = json_decode(file_get_contents("php://input"), true);

$customer_id = $input['customer_id'] ?? null;
$amount      = $input['amount'] ?? null;
$user_id     = $input['user_id'] ?? null;
$note        = trim($input['note'] ?? '') ?: 'Counter top-up';

if (!$customer_id || !$amount || !$user_id) {
    send_json(false, null, "customer_id, amount, and user_id are required.", 400);
}
if (!is_numeric($amount) || $amount <= 0) {
    send_json(false, null, "amount must be a positive number.", 400);
}

$stmt = $pdo->prepare("SELECT id, wallet_balance FROM customers WHERE id = :id");
$stmt->execute(['id' => $customer_id]);
$customer = $stmt->fetch();

if (!$customer) {
    send_json(false, null, "Customer not found.", 404);
}

try {
    $pdo->beginTransaction();

    $newBalance = round($customer['wallet_balance'] + $amount, 2);

    $stmt = $pdo->prepare("UPDATE customers SET wallet_balance = :bal WHERE id = :id");
    $stmt->execute(['bal' => $newBalance, 'id' => $customer_id]);

    $stmt = $pdo->prepare(
        "INSERT INTO wallet_transactions (customer_id, type, amount, reference, balance_after, user_id)
         VALUES (:customer_id, 'Load', :amount, :reference, :balance_after, :user_id)"
    );
    $stmt->execute([
        'customer_id'   => $customer_id,
        'amount'        => $amount,
        'reference'     => $note,
        'balance_after' => $newBalance,
        'user_id'       => $user_id,
    ]);

    $pdo->commit();

    send_json(true, ["new_balance" => $newBalance], "Wallet loaded successfully.", 201);

} catch (PDOException $e) {
    $pdo->rollBack();
    send_json(false, null, "Error loading wallet: " . $e->getMessage(), 500);
}
