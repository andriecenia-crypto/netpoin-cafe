<?php

require_once __DIR__ . '/../../config/headers.php';
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(false, null, "Method not allowed. Use POST.", 405);
}

// Signup is protected by a "staff signup code" set by the owner as the
// SIGNUP_CODE environment variable. If it isn't set, signup is switched off.
$requiredCode = getenv('SIGNUP_CODE');
if (!$requiredCode) {
    send_json(false, null, "Signup is currently disabled. Ask the administrator for an account.", 403);
}

$input = json_decode(file_get_contents("php://input"), true) ?: [];

$name       = trim($input['name'] ?? '');
$username   = trim($input['username'] ?? '');
$password   = (string)($input['password'] ?? '');
$signupCode = (string)($input['signup_code'] ?? '');

if ($name === '' || $username === '' || $password === '' || $signupCode === '') {
    send_json(false, null, "Name, username, password and staff signup code are required.", 400);
}
if (!hash_equals($requiredCode, $signupCode)) {
    send_json(false, null, "Invalid staff signup code.", 403);
}
if (strlen($name) > 100) {
    send_json(false, null, "Name is too long (max 100 characters).", 400);
}
if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
    send_json(false, null, "Username must be 3-50 characters: letters, numbers, dot, dash or underscore.", 400);
}
if (strlen($password) < 8) {
    send_json(false, null, "Password must be at least 8 characters.", 400);
}

try {
    // New accounts are always 'Staff'. Administrators can only be created in the database.
    $stmt = $pdo->prepare(
        "INSERT INTO users (name, username, password, role)
         VALUES (:name, :username, :password, 'Staff')"
    );
    $stmt->execute([
        'name'     => $name,
        'username' => $username,
        'password' => password_hash($password, PASSWORD_DEFAULT),
    ]);

    send_json(true, ["id" => $pdo->lastInsertId('users_id_seq'), "username" => $username],
              "Account created. You can now log in.", 201);

} catch (PDOException $e) {
    if ($e->getCode() == '23505') {
        send_json(false, null, "That username is already taken.", 409);
    }
    error_log("Signup error: " . $e->getMessage());
    send_json(false, null, "Could not create the account.", 500);
}
