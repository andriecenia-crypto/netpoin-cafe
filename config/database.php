<?php

// Connection settings come from environment variables so no passwords live in the code.
// Local testing and Render both set these (see README.md).
$host    = getenv('DB_HOST')     ?: 'localhost';
$port    = getenv('DB_PORT')     ?: '5432';
$dbname  = getenv('DB_NAME')     ?: 'postgres';
$user    = getenv('DB_USER')     ?: 'postgres';
$pass    = getenv('DB_PASSWORD') ?: '';
$sslmode = getenv('DB_SSLMODE')  ?: 'require';   // Supabase requires SSL

try {
    $pdo = new PDO(
        "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=$sslmode",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    // The app shows times exactly as stored, so keep the session in Philippine time.
    $pdo->exec("SET TIME ZONE 'Asia/Manila'");
} catch (PDOException $e) {
    error_log("DB connection failed: " . $e->getMessage());   // visible in Render logs only
    http_response_code(500);
    header("Content-Type: application/json");
    echo json_encode([
        "success" => false,
        "message" => "Database connection failed. Check the server's DB settings."
    ]);
    exit;
}
