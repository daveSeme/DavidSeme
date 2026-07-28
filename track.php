<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

// Get JSON data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    http_response_code(400);
    exit;
}

// Connect to SQLite
$db = new SQLite3('admin/analytics.db');

// Insert visitor
$stmt = $db->prepare("INSERT INTO visitors (ip, page, referrer, user_agent) VALUES (:ip, :page, :referrer, :ua)");
$stmt->bindValue(':ip', $_SERVER['REMOTE_ADDR'] ?? 'unknown', SQLITE3_TEXT);
$stmt->bindValue(':page', $data['page'] ?? '/', SQLITE3_TEXT);
$stmt->bindValue(':referrer', $data['referrer'] ?? '', SQLITE3_TEXT);
$stmt->bindValue(':ua', $_SERVER['HTTP_USER_AGENT'] ?? '', SQLITE3_TEXT);
$stmt->execute();

echo json_encode(['status' => 'ok']);