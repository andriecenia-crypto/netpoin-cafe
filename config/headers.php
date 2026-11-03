<?php

//control how php api communicate to frontend

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}


function send_json($success, $data = null, $message = null, $statusCode = 200) {
    http_response_code($statusCode);
    $response = ["success" => $success];
    if ($message !== null) $response["message"] = $message;
    if ($data !== null) $response["data"] = $data;
    echo json_encode($response);
    exit;
}
