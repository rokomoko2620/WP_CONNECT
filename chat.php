<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$currentUser = getCurrentUser();
$otherUserId = (int)($_GET['user'] ?? 0);

if (!$otherUserId || $otherUserId === $currentUser['id']) {
    header('Location: chat_list.php');
    exit;
}

$pdo = getDB();

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$otherUserId]);
$otherUser = $stmt->fetch();

if (!$otherUser) {
    header('Location: chat_list.php');
    exit;
}

// 仮想旅の招待処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['invite_virtual_trip'])) {
    // 既存の未完了の仮想旅がないかチェック
    $stmt = $pdo->prepare("
        SELECT id FROM virtual_trips 
        WHERE ((inviter_id = ? AND invitee_id = ?) OR (inviter_id = ? AND invitee_id = ?))
        AND status != 'completed'
    ");
    $stmt->execute([$currentUser['id'], $otherUserId, $otherUserId, $currentUser['id']]);
    $existingTrip = $stmt->fetch();
    
    if (!$existingTrip) {
        $stmt = $pdo->prepare("INSERT INTO virtual_trips (inviter_id, invitee_id) VALUES (?, ?)");
        $stmt->execute([$currentUser['id'], $otherUserId]);
        $newTripId = $pdo->lastInsertId();
        header("Location: virtual_trip.php?id=$newTripId");
        exit;
    } else {
        header("Location: virtual_trip.php?id=" . $existingTrip['id']);
        exit;
    }
}

// メッセージ送信処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $content = trim($_POST['message']);
    
    if ($content) {
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$currentUser['id'], $otherUserId, $content]);
        
        $user1 = min($currentUser['id'], $otherUserId);
        $user2 = max($currentUser['id'], $otherUserId);
        
        $stmt = $pdo->prepare("
            INSERT INTO conversations (user1_id, user2_id, last_message_at) 
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE last_message_at = NOW()
        ");
        $stmt->execute([$user1, $user2]);
        
        header("Location: chat.php?user=$otherUserId");
        exit;
    }
}

$pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?")->execute([$otherUserId, $currentUser['id']]);

$stmt = $pdo->prepare("
    SELECT * FROM messages 
    WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
    ORDER BY created_at ASC
");
$stmt->execute([$currentUser['id'], $otherUserId, $otherUserId, $currentUser['id']]);
$messages = $stmt->fetchAll();

// 仮想旅の状態を確認
$stmt = $pdo->prepare("
    SELECT * FROM virtual_trips 
    WHERE ((inviter_id = ? AND invitee_id = ?) OR (inviter_id = ? AND invitee_id = ?))
    ORDER BY created_at DESC LIMIT 1
");
$stmt->execute([$currentUser['id'], $otherUserId, $otherUserId, $currentUser['id']]);
$virtualTrip = $stmt->fetch();

$hasActiveTrip = $virtualTrip && $virtualTrip['status'] !== 'completed';
$hasPendingInvite = $virtualTrip && $virtualTrip['status'] === 'pending' && $virtualTrip['invitee_id'] === $currentUser['id'];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($otherUser['display_name'] ?: $otherUser['username']) ?>とのチャット</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { background: var(--gray-100); }
        .chat-page { display: flex; flex-direction: column; height: 100vh; }
        .chat-main { flex: 1; display: flex; flex-direction: column; max-width: 800px; margin: 0 auto; width: 100%; padding: var(--space-md); overflow: hidden; }
        .chat-box { flex: 1; display: flex; flex-direction: column; background: white; border-radius: var(--radius-xl); box-shadow: var(--shadow-lg); overflow: hidden; }
        .chat-header { display: flex; align-items: center; gap: var(--space-md); padding: var(--space-lg); background: var(--primary-500); color: white; }
        .chat-header-back { color: white; font-size: 1.5rem; text-decoration: none; }
        .chat-header-avatar { width: 48px; height: 48px; border-radius: 50%; border: 2px solid rgba(255,255,255,0.5); overflow: hidden; }
        .chat-header-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .chat-header-info { flex: 1; }
        .chat-header-actions { display: flex; gap: var(--space-sm); }
        .chat-messages { flex: 1; padding: var(--space-lg); overflow-y: auto; display: flex; flex-direction: column; gap: var(--space-md); background: var(--gray-50); }
        .message { max-width: 75%; padding: var(--space-md) var(--space-lg); border-radius: var(--radius-lg); }
        .message-sent { background: linear-gradient(135deg, var(--primary-500), var(--primary-600)); color: white; align-self: flex-end; border-bottom-right-radius: 4px; }
        .message-received { background: white; color: var(--gray-800); align-self: flex-start; border-bottom-left-radius: 4px; box-shadow: var(--shadow-sm); }
        .message-time { font-size: 0.75rem; opacity: 0.7; margin-top: var(--space-xs); }
        .chat-input-area { display: flex; gap: var(--space-md); padding: var(--space-lg); border-top: 1px solid var(--gray-200); background: white; flex-wrap: wrap; }
        .chat-input { flex: 1; min-width: 200px; padding: var(--space-md); border: 2px solid var(--gray-200); border-radius: var(--radius-full); font-size: 1rem; }
        .chat-input:focus { outline: none; border-color: var(--primary-400); }
        .empty-chat { text-align: center; padding: var(--space-2xl); color: var(--gray-500); }
        
        .vt-invite-banner { background: linear-gradient(135deg, var(--accent-500), #FF8F6B); color: white; padding: var(--space-md) var(--space-lg); display: flex; align-items: center; justify-content: space-between; gap: var(--space-md); }
        .vt-invite-banner p { margin: 0; font-size: 0.9rem; }
        .vt-invite-banner .btn { background: white; color: var(--accent-500); border: none; }
        .vt-invite-banner .btn:hover { background: var(--gray-100); }
        
        .vt-btn { background: linear-gradient(135deg, var(--accent-500), #FF8F6B); border: none; color: white; padding: var(--space-sm) var(--space-md); border-radius: var(--radius-full); font-size: 0.85rem; cursor: pointer; display: flex; align-items: center; gap: 4px; }
        .vt-btn:hover { opacity: 0.9; }
        
        .message-system { background: var(--gray-200); color: var(--gray-600); align-self: center; font-size: 0.85rem; padding: var(--space-sm) var(--space-lg); border-radius: var(--radius-full); }
    </style>
</head>
<body>
    <div class="chat-page">
        <div class="chat-main">
            <div class="chat-box">
                <div class="chat-header">
                    <a href="chat_list.php" class="chat-header-back">←</a>
                    <div class="chat-header-avatar">
                        <?php if ($otherUser['profile_image'] !== 'default.png'): ?>
                            <img src="uploads/profiles/<?= htmlspecialchars($otherUser['profile_image']) ?>" alt="">
                        <?php else: ?>
                            <div style="width:100%;height:100%;background:var(--primary-300);display:flex;align-items:center;justify-content:center;font-size:1.5rem;">👤</div>
                        <?php endif; ?>
                    </div>
                    <div class="chat-header-info">
                        <div style="font-weight: 600;"><?= htmlspecialchars($otherUser['display_name'] ?: $otherUser['username']) ?></div>
                    </div>
                    <div class="chat-header-actions">
                        <?php if ($hasActiveTrip): ?>
                            <a href="virtual_trip.php?id=<?= $virtualTrip['id'] ?>" class="vt-btn">🎮 仮想旅を続ける</a>
                        <?php else: ?>
                            <form method="POST" style="margin: 0;">
                                <button type="submit" name="invite_virtual_trip" value="1" class="vt-btn">🎮 仮想旅に誘う</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($hasPendingInvite): ?>
                    <div class="vt-invite-banner">
                        <p>🎮 <?= htmlspecialchars($otherUser['display_name'] ?: $otherUser['username']) ?>さんから仮想旅の招待が届いています！</p>
                        <a href="virtual_trip.php?id=<?= $virtualTrip['id'] ?>" class="btn btn-sm">参加する</a>
                    </div>
                <?php endif; ?>
                
                <?php if ($virtualTrip && $virtualTrip['status'] === 'completed'): ?>
                    <div style="background: var(--success); color: white; padding: var(--space-sm) var(--space-lg); text-align: center; font-size: 0.9rem;">
                        🎉 仮想旅完了！相性 <?= $virtualTrip['match_rate'] ?>% 
                        <a href="virtual_trip_result.php?id=<?= $virtualTrip['id'] ?>" style="color: white; margin-left: var(--space-md);">結果を見る →</a>
                    </div>
                <?php endif; ?>
                
                <div class="chat-messages" id="chatMessages">
                    <?php if (empty($messages)): ?>
                        <div class="empty-chat">
                            <p style="font-size: 2rem; margin-bottom: var(--space-md);">👋</p>
                            <p>まだメッセージはありません</p>
                            <p style="font-size: 0.9rem;">最初のメッセージを送ってみましょう！</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($messages as $msg): ?>
                            <div class="message <?= $msg['sender_id'] === $currentUser['id'] ? 'message-sent' : 'message-received' ?>">
                                <div><?= nl2br(htmlspecialchars($msg['content'])) ?></div>
                                <div class="message-time"><?= date('H:i', strtotime($msg['created_at'])) ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <form method="POST" class="chat-input-area">
                    <input type="text" name="message" class="chat-input" placeholder="メッセージを入力..." autocomplete="off" required>
                    <button type="submit" class="btn btn-primary">送信</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        const chatMessages = document.getElementById('chatMessages');
        chatMessages.scrollTop = chatMessages.scrollHeight;
    </script>
</body>
</html>
