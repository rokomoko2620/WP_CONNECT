<?php
/**
 * ╔═══════════════════════════════════════════════════════════════╗
 * ║           CONNECT - 旅の出会いを、もっと特別に                  ║
 * ║                   初期セットアップ                             ║
 * ╚═══════════════════════════════════════════════════════════════╝
 * 
 * このファイルにブラウザでアクセスすると、
 * 必要なデータベースとフォルダが自動で作成されます。
 * 
 * セットアップ後は index.php に移動してアプリを使い始められます。
 */

// ════════════════════════════════════════════════════════════════
// 【設定】お使いの環境に合わせて変更してください
// ════════════════════════════════════════════════════════════════

$host = 'localhost';
$user = 'root';

// パスワード設定（下記からお使いの環境を選んでください）
$pass = 'root';    // ← MAMPの場合（デフォルト）
// $pass = '';     // ← XAMPPの場合（空文字）

// ════════════════════════════════════════════════════════════════

// リセットモードの確認
$resetMode = isset($_GET['reset']) && $_GET['reset'] === 'true';
$confirmReset = isset($_POST['confirm_reset']) && $_POST['confirm_reset'] === 'yes';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CONNECT - 初期セットアップ</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Zen+Maru+Gothic:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Zen Maru Gothic', 'Helvetica Neue', Arial, sans-serif; 
            background: #d4a574;
            background-image: url('images/cork_board_texture.jpg');
            background-size: cover;
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            padding: 20px; 
        }
        .container { 
            background: white; 
            border-radius: 4px; 
            padding: 40px; 
            max-width: 600px; 
            width: 100%; 
            box-shadow: 
                0 4px 8px rgba(0,0,0,0.2),
                0 8px 20px rgba(0,0,0,0.15);
            position: relative;
        }
        /* マグネット風の装飾 */
        .container::before {
            content: '';
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            width: 30px;
            height: 30px;
            background: linear-gradient(145deg, #E991DC, #C471B7);
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        h1 { text-align: center; color: #333; margin-bottom: 8px; font-size: 1.8rem; }
        .subtitle { text-align: center; color: #666; margin-bottom: 30px; font-size: 0.95rem; }
        
        .progress-section { margin-bottom: 25px; }
        .progress-title { 
            font-weight: 600; 
            color: #333; 
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }
        
        .log { 
            background: #fafafa; 
            border-radius: 8px; 
            padding: 15px; 
            margin-bottom: 15px;
        }
        .log-item { 
            padding: 10px 12px; 
            margin: 5px 0;
            border-radius: 6px;
            display: flex; 
            align-items: center; 
            gap: 10px; 
            font-size: 0.95rem;
        }
        .log-item.success { background: #ecfdf5; color: #059669; }
        .log-item.error { background: #fef2f2; color: #dc2626; }
        .log-item.info { background: #eff6ff; color: #2563eb; }
        .log-item.complete { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
            font-weight: 600;
            font-size: 1.1rem;
        }
        .icon { font-size: 1.2em; }
        
        .btn { 
            display: block;
            width: 100%;
            background: #333;
            color: white; 
            padding: 16px 40px; 
            border-radius: 50px; 
            text-decoration: none; 
            font-weight: 600; 
            text-align: center; 
            margin-top: 25px;
            font-size: 1.1rem;
            transition: all 0.3s;
        }
        .btn:hover { 
            background: #555;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        .warning { 
            background: #fffbeb; 
            border: 2px solid #f59e0b; 
            color: #92400e; 
            padding: 20px; 
            border-radius: 8px; 
            margin-top: 20px; 
        }
        .warning h3 { margin-bottom: 10px; }
        .warning ul { margin-top: 10px; padding-left: 20px; line-height: 1.8; }
        
        .info-box {
            background: #f0f9ff;
            border: 2px solid #0ea5e9;
            color: #0369a1;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 0.9rem;
            line-height: 1.6;
        }
        
        .reset-warning {
            background: #fef2f2;
            border: 2px solid #dc2626;
            color: #991b1b;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .reset-warning h3 { color: #dc2626; margin-bottom: 10px; }
        
        .btn-danger {
            background: #dc2626;
        }
        .btn-danger:hover {
            background: #b91c1c;
        }
        
        .btn-secondary {
            background: #6b7280;
        }
        .btn-secondary:hover {
            background: #4b5563;
        }
        
        .mode-buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        .mode-buttons .btn {
            flex: 1;
            margin-top: 0;
        }
        
        .reset-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #dc2626;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>✈️ CONNECT</h1>
        <p class="subtitle">〜旅の出会いを、もっと特別に〜</p>

<?php
// ════════════════════════════════════════════════════════════════
// リセット確認画面
// ════════════════════════════════════════════════════════════════
if ($resetMode && !$confirmReset):
?>
        <div class="reset-warning">
            <h3>⚠️ データベースをリセットしますか？</h3>
            <p>この操作を行うと、<strong>すべてのデータが削除</strong>されます：</p>
            <ul>
                <li>ユーザーアカウント</li>
                <li>旅行計画</li>
                <li>メッセージ</li>
                <li>いいね・コメント</li>
                <li>仮想旅の履歴</li>
            </ul>
            <p style="margin-top: 15px;"><strong>この操作は取り消せません！</strong></p>
        </div>
        
        <form method="POST" action="?reset=true">
            <div class="mode-buttons">
                <a href="setup.php" class="btn btn-secondary">← キャンセル</a>
                <button type="submit" name="confirm_reset" value="yes" class="btn btn-danger">🗑️ リセットして再セットアップ</button>
            </div>
        </form>
<?php
else:
// ════════════════════════════════════════════════════════════════
// 通常のセットアップ処理
// ════════════════════════════════════════════════════════════════
?>
$success = false;
$errors = [];

try {
    // ════════════════════════════════════════════════════════════
    // STEP 1: データベース接続
    // ════════════════════════════════════════════════════════════
    echo '<div class="progress-section">';
    echo '<div class="progress-title">📦 データベースのセットアップ</div>';
    echo '<div class="log">';
    
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    // データベース作成
    $pdo->exec("CREATE DATABASE IF NOT EXISTS travel_match_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE travel_match_db");
    
    // リセットモードの場合、既存テーブルを削除
    if ($confirmReset) {
        echo '<div class="log-item info"><span class="icon">🔄</span> リセットモード：既存データを削除中...</div>';
        
        // 外部キー制約を一時的に無効化
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        // すべてのテーブルを削除
        $tables = ['virtual_trip_answers', 'virtual_trips', 'conversations', 'messages', 'comments', 'likes', 'travel_plans', 'users'];
        foreach ($tables as $table) {
            $pdo->exec("DROP TABLE IF EXISTS $table");
        }
        
        // 外部キー制約を再度有効化
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        
        echo '<div class="log-item success"><span class="icon">✅</span> 既存データの削除完了</div>';
        
        // アップロードフォルダ内のファイルも削除
        $uploadDirs = ['uploads/profiles', 'uploads/plans'];
        foreach ($uploadDirs as $dir) {
            if (is_dir($dir)) {
                $files = glob($dir . '/*');
                foreach ($files as $file) {
                    if (is_file($file) && basename($file) !== '.gitkeep') {
                        unlink($file);
                    }
                }
            }
        }
        echo '<div class="log-item success"><span class="icon">✅</span> アップロードファイルの削除完了</div>';
    }
    
    echo '<div class="log-item success"><span class="icon">✅</span> データベース「travel_match_db」を作成</div>';
    
    // ════════════════════════════════════════════════════════════
    // STEP 2: テーブル作成
    // ════════════════════════════════════════════════════════════
    
    // 1. ユーザーテーブル
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            display_name VARCHAR(100),
            birthplace VARCHAR(100),
            gender ENUM('male', 'female') DEFAULT NULL,
            age INT,
            interests TEXT,
            profile_image VARCHAR(255) DEFAULT 'default.png',
            comment TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo '<div class="log-item success"><span class="icon">✅</span> usersテーブル（ユーザー情報）</div>';
    
    // 2. 旅行計画テーブル
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS travel_plans (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            destination VARCHAR(255) NOT NULL,
            prefecture VARCHAR(50),
            travel_date_start DATE,
            travel_date_end DATE,
            purpose TEXT,
            activities TEXT,
            description TEXT,
            latitude DECIMAL(10, 8) NULL,
            longitude DECIMAL(11, 8) NULL,
            photo VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo '<div class="log-item success"><span class="icon">✅</span> travel_plansテーブル（旅行計画）</div>';
    
    // 3. いいねテーブル
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS likes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            plan_id INT NOT NULL,
            plan_user_id INT NOT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_like (user_id, plan_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (plan_id) REFERENCES travel_plans(id) ON DELETE CASCADE,
            FOREIGN KEY (plan_user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo '<div class="log-item success"><span class="icon">✅</span> likesテーブル（いいね）</div>';
    
    // 4. コメントテーブル
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            plan_id INT NOT NULL,
            content TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (plan_id) REFERENCES travel_plans(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo '<div class="log-item success"><span class="icon">✅</span> commentsテーブル（コメント）</div>';
    
    // 5. メッセージテーブル
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sender_id INT NOT NULL,
            receiver_id INT NOT NULL,
            content TEXT NOT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo '<div class="log-item success"><span class="icon">✅</span> messagesテーブル（メッセージ）</div>';
    
    // 6. 会話テーブル
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS conversations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user1_id INT NOT NULL,
            user2_id INT NOT NULL,
            last_message_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_conversation (user1_id, user2_id),
            FOREIGN KEY (user1_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (user2_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo '<div class="log-item success"><span class="icon">✅</span> conversationsテーブル（会話管理）</div>';
    
    // 7. 仮想旅テーブル
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS virtual_trips (
            id INT AUTO_INCREMENT PRIMARY KEY,
            inviter_id INT NOT NULL,
            invitee_id INT NOT NULL,
            status ENUM('pending', 'accepted', 'completed') DEFAULT 'pending',
            inviter_completed TINYINT(1) DEFAULT 0,
            invitee_completed TINYINT(1) DEFAULT 0,
            match_rate INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            completed_at TIMESTAMP NULL,
            FOREIGN KEY (inviter_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (invitee_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo '<div class="log-item success"><span class="icon">✅</span> virtual_tripsテーブル（仮想旅）</div>';
    
    // 8. 仮想旅回答テーブル
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS virtual_trip_answers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            trip_id INT NOT NULL,
            user_id INT NOT NULL,
            question_id INT NOT NULL,
            answer CHAR(1) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (trip_id) REFERENCES virtual_trips(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_answer (trip_id, user_id, question_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo '<div class="log-item success"><span class="icon">✅</span> virtual_trip_answersテーブル（仮想旅回答）</div>';
    
    echo '</div></div>';
    
    // ════════════════════════════════════════════════════════════
    // STEP 3: フォルダ作成
    // ════════════════════════════════════════════════════════════
    echo '<div class="progress-section">';
    echo '<div class="progress-title">📁 フォルダのセットアップ</div>';
    echo '<div class="log">';
    
    // アップロードフォルダ
    $folders = [
        'uploads/profiles' => 'プロフィール画像用',
        'uploads/plans' => '旅行計画の写真用'
    ];
    
    foreach ($folders as $folder => $desc) {
        if (!is_dir($folder)) {
            if (mkdir($folder, 0777, true)) {
                echo '<div class="log-item success"><span class="icon">✅</span> ' . $folder . '（' . $desc . '）</div>';
            } else {
                echo '<div class="log-item error"><span class="icon">⚠️</span> ' . $folder . ' の作成に失敗</div>';
            }
        } else {
            echo '<div class="log-item info"><span class="icon">ℹ️</span> ' . $folder . ' は既に存在します</div>';
        }
    }
    
    echo '</div></div>';
    
    // ════════════════════════════════════════════════════════════
    // 完了
    // ════════════════════════════════════════════════════════════
    echo '<div class="log-item complete"><span class="icon">🎉</span> セットアップが完了しました！</div>';
    $success = true;
    
} catch (PDOException $e) {
    echo '<div class="log-item error"><span class="icon">❌</span> データベースエラー: ' . htmlspecialchars($e->getMessage()) . '</div>';
    echo '</div></div>';
    $success = false;
}
?>
        
        <?php if ($success): ?>
            <a href="index.php" class="btn">🚀 CONNECTを始める</a>
            
            <div class="info-box">
                <strong>💡 次のステップ</strong><br>
                1. 新規登録でアカウントを作成<br>
                2. プロフィールを設定<br>
                3. 旅行計画を作成してみましょう！
            </div>
            
            <a href="setup.php?reset=true" class="reset-link">🔄 うまくいかない場合：データベースをリセット</a>
        <?php else: ?>
            <div class="warning">
                <h3>⚠️ セットアップに失敗しました</h3>
                <p>以下を確認してください：</p>
                <ul>
                    <li><strong>MAMP/XAMPPが起動していますか？</strong><br>
                        → Apache と MySQL の両方を起動してください</li>
                    <li><strong>パスワードは正しいですか？</strong><br>
                        → MAMPのデフォルト: <code>root</code><br>
                        → XAMPPのデフォルト: 空文字（<code>''</code>）</li>
                    <li><strong>setup.phpの設定を確認</strong><br>
                        → ファイル上部の <code>$pass</code> を環境に合わせて変更</li>
                </ul>
            </div>
            
            <a href="setup.php?reset=true" class="reset-link">🔄 うまくいかない場合：データベースをリセット</a>
        <?php endif; ?>
<?php endif; ?>
    </div>
</body>
</html>
