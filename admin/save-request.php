<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

$json = file_get_contents('php://input');
$data = json_decode($json, true);

$db = new SQLite3('analytics.db');

$stmt = $db->prepare("INSERT INTO access_requests (name, email, project, interest, message) VALUES (:name, :email, :project, :interest, :message)");
$stmt->bindValue(':name', $data['name'], SQLITE3_TEXT);
$stmt->bindValue(':email', $data['email'], SQLITE3_TEXT);
$stmt->bindValue(':project', $data['project'], SQLITE3_TEXT);
$stmt->bindValue(':interest', $data['interest'], SQLITE3_TEXT);
$stmt->bindValue(':message', $data['message'] ?? '', SQLITE3_TEXT);
$stmt->execute();

echo json_encode(['status' => 'ok']);