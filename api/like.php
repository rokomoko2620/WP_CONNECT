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

// プランの所有者を取得
$stmt = $pdo->prepare("SELECT user_id FROM travel_plans WHERE id = ?");
$stmt->execute([$planId]);
$plan = $stmt->fetch();

if (!$plan) {
    echo json_encode(['error' => 'Plan not found']);
    exit;
}

// 既にいいねしているかチェック
$stmt = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND plan_id = ?");
$stmt->execute([$userId, $planId]);
$existing = $stmt->fetch();

if ($existing) {
    // いいね解除
    $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND plan_id = ?")->execute([$userId, $planId]);
    $liked = false;
} else {
    // いいね追加
    $stmt = $pdo->prepare("INSERT INTO likes (user_id, plan_id, plan_user_id) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $planId, $plan['user_id']]);
    $liked = true;
}

// いいね数を取得
$stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE plan_id = ?");
$stmt->execute([$planId]);
$count = $stmt->fetchColumn();

echo json_encode(['success' => true, 'liked' => $liked, 'count' => $count]);
