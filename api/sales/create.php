<?php


require_once __DIR__ . '/../../config/headers.php';
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(false, null, "Method not allowed. Use POST.", 405);
}

$input = json_decode(file_get_contents("php://input"), true);

$product_id     = $input['product_id'] ?? null;
$quantity       = $input['quantity'] ?? null;
$customer_id    = $input['customer_id'] ?? null;
$user_id        = $input['user_id'] ?? null;
$payment_method = $input['payment_method'] ?? 'Cash';

if (!$product_id || !$quantity || !$user_id) {
    send_json(false, null, "product_id, quantity, and user_id are required.", 400);
}
if (!is_numeric($quantity) || $quantity < 1) {
    send_json(false, null, "quantity must be at least 1.", 400);
}
if (!in_array($payment_method, ['Cash', 'Wallet'])) {
    send_json(false, null, "payment_method must be Cash or Wallet.", 400);
}
if ($payment_method === 'Wallet' && !$customer_id) {
    send_json(false, null, "A customer is required to pay by Wallet.", 400);
}

// --- Check product and stock ---
$stmt = $pdo->prepare("SELECT id, product_name, price, stock_quantity FROM products WHERE id = :id");
$stmt->execute(['id' => $product_id]);
$product = $stmt->fetch();

if (!$product) {
    send_json(false, null, "Product not found.", 404);
}
if ($product['stock_quantity'] < $quantity) {
    send_json(false, null, "Not enough stock. Only {$product['stock_quantity']} left of {$product['product_name']}.", 409);
}

$subtotal = round($product['price'] * $quantity, 2);

// --- If paying by wallet, check the customer and their balance ---
$customer = null;
if ($payment_method === 'Wallet') {
    $stmt = $pdo->prepare("SELECT id, wallet_balance FROM customers WHERE id = :id");
    $stmt->execute(['id' => $customer_id]);
    $customer = $stmt->fetch();

    if (!$customer) {
        send_json(false, null, "Customer not found.", 404);
    }
    if ($customer['wallet_balance'] < $subtotal) {
        send_json(false, null, "Insufficient wallet balance. Current balance: " . number_format($customer['wallet_balance'], 2) . ", needed: " . number_format($subtotal, 2), 402);
    }
} elseif ($customer_id) {
    // Cash sale but a customer was still tagged on it - just confirm they exist
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE id = :id");
    $stmt->execute(['id' => $customer_id]);
    if (!$stmt->fetch()) {
        send_json(false, null, "Customer not found.", 404);
    }
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "INSERT INTO sales (product_id, quantity, unit_price, subtotal, customer_id, user_id, payment_method)
         VALUES (:product_id, :quantity, :unit_price, :subtotal, :customer_id, :user_id, :payment_method)"
    );
    $stmt->execute([
        'product_id'     => $product_id,
        'quantity'       => $quantity,
        'unit_price'     => $product['price'],
        'subtotal'       => $subtotal,
        'customer_id'    => $customer_id,
        'user_id'        => $user_id,
        'payment_method' => $payment_method,
    ]);
    $saleId = $pdo->lastInsertId('sales_id_seq');

    $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - :qty WHERE id = :id");
    $stmt->execute(['qty' => $quantity, 'id' => $product_id]);

    if ($payment_method === 'Wallet') {
        $newBalance = round($customer['wallet_balance'] - $subtotal, 2);

        $stmt = $pdo->prepare("UPDATE customers SET wallet_balance = :bal WHERE id = :id");
        $stmt->execute(['bal' => $newBalance, 'id' => $customer_id]);

        $stmt = $pdo->prepare(
            "INSERT INTO wallet_transactions (customer_id, type, amount, reference, balance_after, user_id)
             VALUES (:customer_id, 'Payment', :amount, :reference, :balance_after, :user_id)"
        );
        $stmt->execute([
            'customer_id'   => $customer_id,
            'amount'        => $subtotal,
            'reference'     => "{$quantity}x {$product['product_name']}",
            'balance_after' => $newBalance,
            'user_id'       => $user_id,
        ]);
    }

    $pdo->commit();

    send_json(true, [
        "id"       => $saleId,
        "subtotal" => $subtotal,
    ], "Sale recorded successfully.", 201);

} catch (PDOException $e) {
    $pdo->rollBack();
    send_json(false, null, "Error recording sale: " . $e->getMessage(), 500);
}
