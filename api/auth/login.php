<?php

require_once __DIR__ . '/../../config/headers.php';
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(false, null, "Method not allowed. Use POST.", 405);
}

$input = json_decode(file_get_contents("php://input"), true);

$inputUsername = trim($input['username'] ?? '');
$inputPassword = trim($input['password'] ?? '');

if ($inputUsername === '' || $inputPassword === '') {
    send_json(false, null, "Username and password are required.", 400);
}

$stmt = $pdo->prepare("SELECT id, name, username, password, role FROM users WHERE username = :username LIMIT 1");
$stmt->execute(['username' => $inputUsername]);
$user = $stmt->fetch();

if (!$user || !password_verify($inputPassword, $user['password'])) {
    // Same generic message for "no such user" and "wrong password" -
    // don't reveal which one it was, that's a login security basic.
    send_json(false, null, "Invalid username or password.", 401);
}

// Don't send the password back to the client, ever.
unset($user['password']);

send_json(true, $user, "Login successful.");
