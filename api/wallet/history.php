<?php
/**
 * GET /api/wallet/history.php                    -> all wallet transactions
 * GET /api/wallet/history.php?customer_id=1       -> one customer's history
 */

require_once __DIR__ . '/../../config/headers.php';
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_json(false, null, "Method not allowed. Use GET.", 405);
}

if (isset($_GET['customer_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM wallet_transactions WHERE customer_id = :id ORDER BY created_at DESC");
    $stmt->execute(['id' => $_GET['customer_id']]);
} else {
    $stmt = $pdo->query("SELECT * FROM wallet_transactions ORDER BY created_at DESC");
}

send_json(true, $stmt->fetchAll());
