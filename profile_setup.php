<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$currentUser = getCurrentUser();
$error = '';

$prefectures = ['北海道','青森県','岩手県','宮城県','秋田県','山形県','福島県','茨城県','栃木県','群馬県','埼玉県','千葉県','東京都','神奈川県','新潟県','富山県','石川県','福井県','山梨県','長野県','岐阜県','静岡県','愛知県','三重県','滋賀県','京都府','大阪府','兵庫県','奈良県','和歌山県','鳥取県','島根県','岡山県','広島県','山口県','徳島県','香川県','愛媛県','高知県','福岡県','佐賀県','長崎県','熊本県','大分県','宮崎県','鹿児島県','沖縄県'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $displayName = trim($_POST['display_name'] ?? '');
    $birthplace = $_POST['birthplace'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $age = !empty($_POST['age']) ? (int)$_POST['age'] : null;
    $interests = trim($_POST['interests'] ?? '');
    $comment = trim($_POST['comment'] ?? '');
    
    if (empty($displayName)) {
        $error = '表示名を入力してください';
    } else {
        $pdo = getDB();
        $stmt = $pdo->prepare("UPDATE users SET display_name=?, birthplace=?, gender=?, age=?, interests=?, comment=? WHERE id=?");
        $stmt->execute([$displayName, $birthplace, $gender, $age, $interests, $comment, $currentUser['id']]);
        
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>プロフィール設定 - CONNECT</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="page-wrapper">
        <header class="header">
            <div class="container">
                <nav class="nav">
                    <a href="index.php" class="logo">
                        <span class="logo-icon">✈</span>
                        <span>CONNECT</span>
                    </a>
                </nav>
            </div>
        </header>
        
        <main class="main-content">
            <div class="container" style="max-width: 600px;">
                <div class="card">
                    <h1 style="text-align: center; margin-bottom: var(--space-md);">👋 ようこそ！</h1>
                    <p style="text-align: center; color: var(--gray-500); margin-bottom: var(--space-xl);">
                        プロフィールを設定して、旅仲間を見つけましょう！
                    </p>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label">表示名 *</label>
                            <input type="text" name="display_name" class="form-input" placeholder="ニックネームを入力" value="<?= htmlspecialchars($_POST['display_name'] ?? '') ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">出身地</label>
                            <select name="birthplace" class="form-select">
                                <option value="">選択してください</option>
                                <?php foreach ($prefectures as $pref): ?>
                                    <option value="<?= $pref ?>" <?= ($_POST['birthplace'] ?? '') === $pref ? 'selected' : '' ?>><?= $pref ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-md);">
                            <div class="form-group">
                                <label class="form-label">性別</label>
                                <select name="gender" class="form-select">
                                    <option value="">選択してください</option>
                                    <option value="male" <?= ($_POST['gender'] ?? '') === 'male' ? 'selected' : '' ?>>男性</option>
                                    <option value="female" <?= ($_POST['gender'] ?? '') === 'female' ? 'selected' : '' ?>>女性</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">年齢</label>
                                <input type="number" name="age" class="form-input" min="1" max="120" placeholder="例: 25" value="<?= htmlspecialchars($_POST['age'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">好きなもの</label>
                            <input type="text" name="interests" class="form-input" placeholder="例: 旅行、写真、グルメ" value="<?= htmlspecialchars($_POST['interests'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">コメント</label>
                            <textarea name="comment" class="form-textarea" rows="3" placeholder="自己紹介を書いてください"><?= htmlspecialchars($_POST['comment'] ?? '') ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                            プロフィールを設定する
                        </button>
                    </form>
                    
                    <p style="text-align: center; margin-top: var(--space-lg); font-size: 0.875rem; color: var(--gray-500);">
                        ※ プロフィールは後から変更できます
                    </p>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
