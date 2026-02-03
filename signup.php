<?php
require_once 'includes/config.php';

// 既にログインしている場合はトップへ
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'ユーザー名とパスワードを入力してください';
    } elseif (strlen($username) < 3) {
        $error = 'ユーザー名は3文字以上にしてください';
    } elseif (strlen($password) < 6) {
        $error = 'パスワードは6文字以上にしてください';
    } elseif ($password !== $password_confirm) {
        $error = 'パスワードが一致しません';
    } else {
        $pdo = getDB();
        
        // ユーザー名の重複チェック
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        
        if ($stmt->fetch()) {
            $error = 'このユーザー名は既に使用されています';
        } else {
            // ユーザー作成
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt->execute([$username, $hashedPassword]);
            
            // 自動ログイン
            $_SESSION['user_id'] = $pdo->lastInsertId();
            
            // プロフィール設定画面へ
            header('Location: profile_setup.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新規登録 - CONNECT</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>はじめまして！</h1>
                <p>アカウントを作成して旅仲間を見つけよう</p>
            </div>
            
            <div class="auth-body">
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        ⚠️ <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label" for="username">ユーザー名</label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            class="form-input" 
                            placeholder="3文字以上で入力"
                            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                            required
                            minlength="3"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="password">パスワード</label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-input" 
                            placeholder="6文字以上で入力"
                            required
                            minlength="6"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="password_confirm">パスワード（確認）</label>
                        <input 
                            type="password" 
                            id="password_confirm" 
                            name="password_confirm" 
                            class="form-input" 
                            placeholder="もう一度パスワードを入力"
                            required
                        >
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                        アカウント作成
                    </button>
                </form>
            </div>
            
            <div class="auth-footer">
                <p>既にアカウントをお持ちの方は <a href="login.php">ログイン</a></p>
                <a href="index.php" style="display: inline-block; margin-top: 10px; color: var(--gray-500);">← トップに戻る</a>
            </div>
        </div>
    </div>
</body>
</html>
