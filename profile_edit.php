<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$currentUser = getCurrentUser();
$success = false;
$error = '';

$prefectures = ['北海道','青森県','岩手県','宮城県','秋田県','山形県','福島県','茨城県','栃木県','群馬県','埼玉県','千葉県','東京都','神奈川県','新潟県','富山県','石川県','福井県','山梨県','長野県','岐阜県','静岡県','愛知県','三重県','滋賀県','京都府','大阪府','兵庫県','奈良県','和歌山県','鳥取県','島根県','岡山県','広島県','山口県','徳島県','香川県','愛媛県','高知県','福岡県','佐賀県','長崎県','熊本県','大分県','宮崎県','鹿児島県','沖縄県'];

$maxFileSize = 5 * 1024 * 1024;
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

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
        $profileImage = $currentUser['profile_image'];
        
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['profile_image'];
            
            if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
                $error = 'ファイルサイズが大きすぎます（最大5MB）';
            } elseif ($file['error'] !== UPLOAD_ERR_OK) {
                $error = 'ファイルのアップロードに失敗しました';
            } elseif ($file['size'] > $maxFileSize) {
                $error = 'ファイルサイズが大きすぎます（最大5MB）';
            } elseif (!in_array($file['type'], $allowedTypes)) {
                $error = '対応していないファイル形式です（JPEG, PNG, GIF, WebPのみ）';
            } else {
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, $allowedExtensions)) {
                    $error = '対応していないファイル形式です';
                } else {
                    $newFilename = 'user_' . $currentUser['id'] . '_' . time() . '.' . $ext;
                    $uploadPath = 'uploads/profiles/' . $newFilename;
                    
                    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                        if ($profileImage !== 'default.png') {
                            $oldPath = 'uploads/profiles/' . $profileImage;
                            if (file_exists($oldPath)) unlink($oldPath);
                        }
                        $profileImage = $newFilename;
                    } else {
                        $error = 'ファイルの保存に失敗しました';
                    }
                }
            }
        }
        
        if (empty($error)) {
            $stmt = $pdo->prepare("UPDATE users SET display_name=?, birthplace=?, gender=?, age=?, interests=?, comment=?, profile_image=? WHERE id=?");
            $stmt->execute([$displayName, $birthplace, $gender, $age, $interests, $comment, $profileImage, $currentUser['id']]);
            $success = true;
            $currentUser = getCurrentUser();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>プロフィール編集 - CONNECT</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .profile-image-preview { width: 120px; height: 120px; border-radius: 50%; background: var(--gray-100); display: flex; align-items: center; justify-content: center; margin: 0 auto var(--space-md); overflow: hidden; border: 4px solid var(--primary-200); cursor: pointer; transition: all var(--transition-fast); }
        .profile-image-preview:hover { border-color: var(--primary-400); transform: scale(1.05); }
        .profile-image-preview img { width: 100%; height: 100%; object-fit: cover; }
        .upload-info { text-align: center; font-size: 0.8rem; color: var(--gray-500); margin-bottom: var(--space-lg); padding: var(--space-sm); background: var(--gray-50); border-radius: var(--radius-md); }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <?php include 'includes/header.php'; ?>
        <main class="main-content">
            <div class="container" style="max-width: 600px;">
                <div class="card">
                    <h1 style="text-align: center; margin-bottom: var(--space-xl);">プロフィール編集</h1>
                    
                    <?php if ($success): ?><div class="alert alert-success">✅ プロフィールを更新しました</div><?php endif; ?>
                    <?php if ($error): ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label class="profile-image-preview" for="profile_image" id="imagePreview">
                                <?php if ($currentUser['profile_image'] !== 'default.png'): ?>
                                    <img src="uploads/profiles/<?= htmlspecialchars($currentUser['profile_image']) ?>" alt="">
                                <?php else: ?>
                                    <span style="font-size: 3rem; color: var(--gray-400);">📷</span>
                                <?php endif; ?>
                            </label>
                            <p style="text-align: center; font-size: 0.875rem; color: var(--gray-500);">クリックして写真を変更</p>
                            <div class="upload-info">📁 JPEG, PNG, GIF, WebP / 最大 5MB</div>
                            <input type="file" id="profile_image" name="profile_image" accept="image/jpeg,image/png,image/gif,image/webp" style="display: none;" onchange="previewImage(this)">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">表示名 *</label>
                            <input type="text" name="display_name" class="form-input" value="<?= htmlspecialchars($currentUser['display_name'] ?? '') ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">出身地</label>
                            <select name="birthplace" class="form-select">
                                <option value="">選択してください</option>
                                <?php foreach ($prefectures as $pref): ?>
                                    <option value="<?= $pref ?>" <?= ($currentUser['birthplace'] ?? '') === $pref ? 'selected' : '' ?>><?= $pref ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-md);">
                            <div class="form-group">
                                <label class="form-label">性別</label>
                                <select name="gender" class="form-select">
                                    <option value="">選択してください</option>
                                    <option value="male" <?= ($currentUser['gender'] ?? '') === 'male' ? 'selected' : '' ?>>男性</option>
                                    <option value="female" <?= ($currentUser['gender'] ?? '') === 'female' ? 'selected' : '' ?>>女性</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">年齢</label>
                                <input type="number" name="age" class="form-input" min="1" max="120" value="<?= htmlspecialchars($currentUser['age'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">好きなもの</label>
                            <input type="text" name="interests" class="form-input" value="<?= htmlspecialchars($currentUser['interests'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">コメント</label>
                            <textarea name="comment" class="form-textarea" rows="3"><?= htmlspecialchars($currentUser['comment'] ?? '') ?></textarea>
                        </div>
                        
                        <div style="display: flex; gap: var(--space-md);">
                            <a href="index.php" class="btn btn-secondary" style="flex: 1;">← タイトルに戻る</a>
                            <button type="submit" class="btn btn-primary" style="flex: 1;">保存する</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    <script>
    function previewImage(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            if (file.size > 5 * 1024 * 1024) { alert('ファイルサイズが大きすぎます（最大5MB）'); input.value = ''; return; }
            const reader = new FileReader();
            reader.onload = function(e) { document.getElementById('imagePreview').innerHTML = '<img src="' + e.target.result + '" alt="">'; };
            reader.readAsDataURL(file);
        }
    }
    </script>
</body>
</html>
