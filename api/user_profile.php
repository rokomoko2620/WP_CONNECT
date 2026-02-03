<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$userId = (int)($_GET['id'] ?? 0);

if (!$userId) {
    echo json_encode(['error' => 'Invalid user']);
    exit;
}

$pdo = getDB();
$stmt = $pdo->prepare("SELECT id, username, display_name, birthplace, gender, age, interests, comment, profile_image FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['error' => 'User not found']);
    exit;
}

$user['is_self'] = ($userId === getCurrentUserId());

echo json_encode($user);
