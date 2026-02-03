<?php
// データベース設定
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'root'); // MAMPのデフォルトパスワード
define('DB_NAME', 'travel_match_db');

// データベース接続
function getDB() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        die("データベース接続エラー: " . $e->getMessage());
    }
}

// セッション開始
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ログインチェック関数
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// 現在のユーザーID取得
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// 現在のユーザー情報取得
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([getCurrentUserId()]);
    return $stmt->fetch();
}

// 未読通知数を取得
function getUnreadCount($userId) {
    $pdo = getDB();
    
    // 未読いいね数
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM likes 
        WHERE plan_user_id = ? AND is_read = 0
    ");
    $stmt->execute([$userId]);
    $likes = $stmt->fetchColumn();
    
    // 未読メッセージ数
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM messages 
        WHERE receiver_id = ? AND is_read = 0
    ");
    $stmt->execute([$userId]);
    $messages = $stmt->fetchColumn();
    
    return ['likes' => $likes, 'messages' => $messages, 'total' => $likes + $messages];
}
?>
