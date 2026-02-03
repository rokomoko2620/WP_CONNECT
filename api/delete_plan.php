<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$planId = (int)($data['plan_id'] ?? 0);
$userId = getCurrentUserId();

if (!$planId) {
    echo json_encode(['error' => 'Invalid plan']);
    exit;
}

$pdo = getDB();

// 自分の計画かチェック
$stmt = $pdo->prepare("DELETE FROM travel_plans WHERE id = ? AND user_id = ?");
$stmt->execute([$planId, $userId]);

echo json_encode(['success' => $stmt->rowCount() > 0]);
