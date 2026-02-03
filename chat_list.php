<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$currentUser = getCurrentUser();
$pdo = getDB();

// メッセージ通知を既読に（一覧を開いた時点で）
// 個別チャットを開いた時に既読にするため、ここでは既読にしない

// 会話一覧を取得
$stmt = $pdo->prepare("
    SELECT 
        c.*,
        CASE 
            WHEN c.user1_id = ? THEN c.user2_id 
            ELSE c.user1_id 
        END as other_user_id,
        u.display_name, u.username, u.profile_image,
        (
            SELECT content FROM messages 
            WHERE (sender_id = c.user1_id AND receiver_id = c.user2_id)
               OR (sender_id = c.user2_id AND receiver_id = c.user1_id)
            ORDER BY created_at DESC LIMIT 1
        ) as last_message,
        (
            SELECT COUNT(*) FROM messages 
            WHERE receiver_id = ? AND sender_id = CASE WHEN c.user1_id = ? THEN c.user2_id ELSE c.user1_id END AND is_read = 0
        ) as unread_count
    FROM conversations c
    JOIN users u ON u.id = CASE WHEN c.user1_id = ? THEN c.user2_id ELSE c.user1_id END
    WHERE c.user1_id = ? OR c.user2_id = ?
    ORDER BY c.last_message_at DESC
");
$stmt->execute([
    $currentUser['id'], 
    $currentUser['id'], 
    $currentUser['id'], 
    $currentUser['id'],
    $currentUser['id'], 
    $currentUser['id']
]);
$conversations = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>チャット - CONNECT</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="page-wrapper">
        <?php include 'includes/header.php'; ?>
        
        <main class="main-content">
            <div class="container" style="max-width: 700px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-xl);">
                    <h1>💬 チャット</h1>
                    <a href="index.php" class="btn btn-secondary">← タイトルに戻る</a>
                </div>
                
                <div class="card" style="padding: 0; overflow: hidden;">
                    <?php if (empty($conversations)): ?>
                        <div style="text-align: center; padding: 60px;">
                            <p style="font-size: 3rem; margin-bottom: var(--space-md);">💬</p>
                            <p style="color: var(--gray-500);">まだメッセージはありません</p>
                            <p style="color: var(--gray-400); font-size: 0.9rem; margin-top: var(--space-sm);">
                                旅行計画を見て、気になる人にメッセージを送ってみましょう！
                            </p>
                            <a href="plans.php?filter=others" class="btn btn-primary mt-lg">旅行計画を見る</a>
                        </div>
                    <?php else: ?>
                        <div class="chat-list">
                            <?php foreach ($conversations as $conv): ?>
                                <a href="chat.php?user=<?= $conv['other_user_id'] ?>" class="chat-list-item <?= $conv['unread_count'] > 0 ? 'unread' : '' ?>">
                                    <div class="chat-list-avatar">
                                        <?php if ($conv['profile_image'] !== 'default.png'): ?>
                                            <img src="uploads/profiles/<?= htmlspecialchars($conv['profile_image']) ?>" alt="">
                                        <?php else: ?>
                                            <div style="width: 100%; height: 100%; background: var(--primary-300); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem;">👤</div>
                                        <?php endif; ?>
                                        <?php if ($conv['unread_count'] > 0): ?>
                                            <span class="notification-badge" style="position: absolute; top: -4px; right: -4px;"><?= $conv['unread_count'] ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="chat-list-content">
                                        <div class="chat-list-name"><?= htmlspecialchars($conv['display_name'] ?: $conv['username']) ?></div>
                                        <div class="chat-list-preview"><?= htmlspecialchars(mb_substr($conv['last_message'] ?? '', 0, 30)) ?><?= mb_strlen($conv['last_message'] ?? '') > 30 ? '...' : '' ?></div>
                                    </div>
                                    <div class="chat-list-time">
                                        <?= date('m/d H:i', strtotime($conv['last_message_at'])) ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
