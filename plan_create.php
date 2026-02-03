<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$currentUser = getCurrentUser();
$error = '';
$editMode = false;
$plan = null;

if (isset($_GET['edit'])) {
    $editMode = true;
    $planId = (int)$_GET['edit'];
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM travel_plans WHERE id = ? AND user_id = ?");
    $stmt->execute([$planId, $currentUser['id']]);
    $plan = $stmt->fetch();
    
    if (!$plan) {
        header('Location: plans.php');
        exit;
    }
}

$prefectures = [
    '北海道' => ['lat' => 43.0646, 'lng' => 141.3468],
    '青森県' => ['lat' => 40.8246, 'lng' => 140.7406],
    '岩手県' => ['lat' => 39.7036, 'lng' => 141.1527],
    '宮城県' => ['lat' => 38.2688, 'lng' => 140.8721],
    '秋田県' => ['lat' => 39.7186, 'lng' => 140.1024],
    '山形県' => ['lat' => 38.2404, 'lng' => 140.3633],
    '福島県' => ['lat' => 37.7500, 'lng' => 140.4678],
    '茨城県' => ['lat' => 36.3418, 'lng' => 140.4468],
    '栃木県' => ['lat' => 36.5657, 'lng' => 139.8836],
    '群馬県' => ['lat' => 36.3912, 'lng' => 139.0608],
    '埼玉県' => ['lat' => 35.8569, 'lng' => 139.6489],
    '千葉県' => ['lat' => 35.6047, 'lng' => 140.1233],
    '東京都' => ['lat' => 35.6894, 'lng' => 139.6917],
    '神奈川県' => ['lat' => 35.4478, 'lng' => 139.6425],
    '新潟県' => ['lat' => 37.9026, 'lng' => 139.0236],
    '富山県' => ['lat' => 36.6953, 'lng' => 137.2113],
    '石川県' => ['lat' => 36.5946, 'lng' => 136.6256],
    '福井県' => ['lat' => 36.0652, 'lng' => 136.2216],
    '山梨県' => ['lat' => 35.6642, 'lng' => 138.5684],
    '長野県' => ['lat' => 36.6513, 'lng' => 138.1810],
    '岐阜県' => ['lat' => 35.3912, 'lng' => 136.7223],
    '静岡県' => ['lat' => 34.9769, 'lng' => 138.3831],
    '愛知県' => ['lat' => 35.1802, 'lng' => 136.9066],
    '三重県' => ['lat' => 34.7303, 'lng' => 136.5086],
    '滋賀県' => ['lat' => 35.0045, 'lng' => 135.8686],
    '京都府' => ['lat' => 35.0116, 'lng' => 135.7681],
    '大阪府' => ['lat' => 34.6863, 'lng' => 135.5200],
    '兵庫県' => ['lat' => 34.6913, 'lng' => 135.1830],
    '奈良県' => ['lat' => 34.6851, 'lng' => 135.8329],
    '和歌山県' => ['lat' => 34.2260, 'lng' => 135.1675],
    '鳥取県' => ['lat' => 35.5039, 'lng' => 134.2381],
    '島根県' => ['lat' => 35.4723, 'lng' => 133.0505],
    '岡山県' => ['lat' => 34.6618, 'lng' => 133.9344],
    '広島県' => ['lat' => 34.3966, 'lng' => 132.4596],
    '山口県' => ['lat' => 34.1859, 'lng' => 131.4714],
    '徳島県' => ['lat' => 34.0658, 'lng' => 134.5593],
    '香川県' => ['lat' => 34.3401, 'lng' => 134.0434],
    '愛媛県' => ['lat' => 33.8416, 'lng' => 132.7657],
    '高知県' => ['lat' => 33.5597, 'lng' => 133.5311],
    '福岡県' => ['lat' => 33.6064, 'lng' => 130.4183],
    '佐賀県' => ['lat' => 33.2494, 'lng' => 130.2988],
    '長崎県' => ['lat' => 32.7448, 'lng' => 129.8737],
    '熊本県' => ['lat' => 32.7898, 'lng' => 130.7417],
    '大分県' => ['lat' => 33.2382, 'lng' => 131.6126],
    '宮崎県' => ['lat' => 31.9111, 'lng' => 131.4239],
    '鹿児島県' => ['lat' => 31.5602, 'lng' => 130.5581],
    '沖縄県' => ['lat' => 26.2124, 'lng' => 127.6809],
    '海外' => ['lat' => 35.6894, 'lng' => 139.6917]
];

$purposes = [
    'リフレッシュ・癒し', '観光・名所巡り', 'グルメ・食べ歩き', 
    'アクティビティ・体験', '友人・家族との思い出作り', '一人旅・自分探し',
    '記念日・お祝い', 'ワーケーション', '写真撮影', 'その他'
];

$activities = [
    '温泉', '神社・寺院巡り', '自然散策', '海・ビーチ', '山・ハイキング',
    '美術館・博物館', 'ショッピング', 'カフェ巡り', '地元グルメ', 
    'テーマパーク', 'スポーツ', '祭り・イベント', '写真撮影', 
    'キャンプ', 'ドライブ', '歴史探訪', '工場見学', '農業体験'
];

// 写真アップロード設定
$maxFileSize = 5 * 1024 * 1024; // 5MB
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $destination = trim($_POST['destination'] ?? '');
    $prefecture = $_POST['prefecture'] ?? '';
    $dateStart = $_POST['date_start'] ?? '';
    $dateEnd = $_POST['date_end'] ?? '';
    $purpose = $_POST['purpose'] ?? '';
    $selectedActivities = $_POST['activities'] ?? [];
    $description = trim($_POST['description'] ?? '');
    $latitude = !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null;
    $longitude = !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null;
    
    if (empty($destination)) {
        $error = '行きたい場所を入力してください';
    } elseif (empty($prefecture)) {
        $error = '都道府県を選択してください';
    } elseif (empty($dateStart)) {
        $error = '旅行開始日を選択してください';
    } else {
        $pdo = getDB();
        $activitiesStr = implode(',', $selectedActivities);
        
        if (!$latitude && !$longitude && isset($prefectures[$prefecture])) {
            $latitude = $prefectures[$prefecture]['lat'];
            $longitude = $prefectures[$prefecture]['lng'];
        }
        
        // 写真アップロード処理
        $photoFilename = $plan['photo'] ?? null; // 編集時は既存の写真を保持
        
        // 写真削除フラグ
        if (isset($_POST['delete_photo']) && $_POST['delete_photo'] === '1') {
            if ($photoFilename) {
                $oldPath = 'uploads/plans/' . $photoFilename;
                if (file_exists($oldPath)) unlink($oldPath);
            }
            $photoFilename = null;
        }
        
        // 新しい写真アップロード
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['photo'];
            
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
                    // uploadsディレクトリ確認・作成
                    if (!is_dir('uploads/plans')) {
                        mkdir('uploads/plans', 0777, true);
                    }
                    
                    $newFilename = 'plan_' . $currentUser['id'] . '_' . time() . '.' . $ext;
                    $uploadPath = 'uploads/plans/' . $newFilename;
                    
                    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                        // 古い写真を削除
                        if ($photoFilename) {
                            $oldPath = 'uploads/plans/' . $photoFilename;
                            if (file_exists($oldPath)) unlink($oldPath);
                        }
                        $photoFilename = $newFilename;
                    } else {
                        $error = 'ファイルの保存に失敗しました';
                    }
                }
            }
        }
        
        if (empty($error)) {
            if ($editMode) {
                $stmt = $pdo->prepare("UPDATE travel_plans SET destination=?, prefecture=?, travel_date_start=?, travel_date_end=?, purpose=?, activities=?, description=?, latitude=?, longitude=?, photo=? WHERE id=? AND user_id=?");
                $stmt->execute([$destination, $prefecture, $dateStart, $dateEnd ?: null, $purpose, $activitiesStr, $description, $latitude, $longitude, $photoFilename, $plan['id'], $currentUser['id']]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO travel_plans (user_id, destination, prefecture, travel_date_start, travel_date_end, purpose, activities, description, latitude, longitude, photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$currentUser['id'], $destination, $prefecture, $dateStart, $dateEnd ?: null, $purpose, $activitiesStr, $description, $latitude, $longitude, $photoFilename]);
            }
            
            header('Location: plans.php?success=1');
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
    <title><?= $editMode ? '旅行計画を編集' : '旅行計画をつくる' ?> - CONNECT</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        .activities-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: var(--space-sm); }
        .activity-checkbox { display: none; }
        .activity-label { display: flex; align-items: center; justify-content: center; padding: var(--space-sm) var(--space-md); background: var(--gray-100); border: 2px solid var(--gray-200); border-radius: var(--radius-lg); cursor: pointer; transition: all var(--transition-fast); font-size: 0.9rem; }
        .activity-label:hover { border-color: var(--primary-300); background: var(--primary-50); }
        .activity-checkbox:checked + .activity-label { background: var(--primary-100); border-color: var(--primary-500); color: var(--primary-700); font-weight: 500; }
        #map { height: 300px; border-radius: var(--radius-lg); margin-top: var(--space-sm); border: 2px solid var(--gray-200); }
        .map-hint { font-size: 0.85rem; color: var(--gray-500); margin-top: var(--space-xs); }
        .location-selected { display: inline-flex; align-items: center; gap: var(--space-xs); background: var(--success); color: white; padding: 4px 12px; border-radius: var(--radius-full); font-size: 0.85rem; margin-top: var(--space-sm); }
        
        .photo-upload-area { border: 2px dashed var(--gray-300); border-radius: var(--radius-lg); padding: var(--space-xl); text-align: center; cursor: pointer; transition: all var(--transition-fast); background: var(--gray-50); }
        .photo-upload-area:hover { border-color: var(--primary-400); background: var(--primary-50); }
        .photo-upload-area.has-photo { border-style: solid; border-color: var(--primary-400); }
        .photo-preview { max-width: 100%; max-height: 300px; border-radius: var(--radius-lg); margin-top: var(--space-md); }
        .photo-info { font-size: 0.85rem; color: var(--gray-500); margin-top: var(--space-sm); }
        .photo-actions { display: flex; gap: var(--space-sm); justify-content: center; margin-top: var(--space-md); }
        .photo-delete-btn { background: var(--error); color: white; border: none; padding: var(--space-sm) var(--space-md); border-radius: var(--radius-md); cursor: pointer; font-size: 0.85rem; }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <?php include 'includes/header.php'; ?>
        <main class="main-content">
            <div class="container" style="max-width: 700px;">
                <div class="card">
                    <h1 style="text-align: center; margin-bottom: var(--space-xl);"><?= $editMode ? '✏️ 旅行計画を編集' : '✈️ 旅行計画をつくる' ?></h1>
                    <?php if ($error): ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="latitude" id="latitude" value="<?= htmlspecialchars($plan['latitude'] ?? '') ?>">
                        <input type="hidden" name="longitude" id="longitude" value="<?= htmlspecialchars($plan['longitude'] ?? '') ?>">
                        <input type="hidden" name="delete_photo" id="deletePhoto" value="0">
                        
                        <div class="form-group">
                            <label class="form-label">行きたい場所 *</label>
                            <input type="text" name="destination" id="destination" class="form-input" placeholder="例: 京都の嵐山、箱根温泉" value="<?= htmlspecialchars($_POST['destination'] ?? $plan['destination'] ?? '') ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">都道府県 *</label>
                            <select name="prefecture" id="prefecture" class="form-select" required onchange="updateMapFromPrefecture()">
                                <option value="">選択してください</option>
                                <?php $selectedPref = $_POST['prefecture'] ?? $plan['prefecture'] ?? '';
                                foreach ($prefectures as $pref => $coords): ?>
                                    <option value="<?= $pref ?>" data-lat="<?= $coords['lat'] ?>" data-lng="<?= $coords['lng'] ?>" <?= $selectedPref === $pref ? 'selected' : '' ?>><?= $pref ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">📍 地図で場所を選択（任意）</label>
                            <div id="map"></div>
                            <p class="map-hint">地図をクリックして正確な場所を指定できます</p>
                            <div id="locationStatus" style="display: none;" class="location-selected">✓ 場所を選択しました</div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-md);">
                            <div class="form-group">
                                <label class="form-label">旅行開始日 *</label>
                                <input type="date" name="date_start" class="form-input" value="<?= htmlspecialchars($_POST['date_start'] ?? $plan['travel_date_start'] ?? '') ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">旅行終了日</label>
                                <input type="date" name="date_end" class="form-input" value="<?= htmlspecialchars($_POST['date_end'] ?? $plan['travel_date_end'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">旅行する目的</label>
                            <select name="purpose" class="form-select">
                                <option value="">選択してください</option>
                                <?php $selectedPurpose = $_POST['purpose'] ?? $plan['purpose'] ?? '';
                                foreach ($purposes as $p): ?>
                                    <option value="<?= $p ?>" <?= $selectedPurpose === $p ? 'selected' : '' ?>><?= $p ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">旅行でしたいこと（複数選択可）</label>
                            <div class="activities-grid">
                                <?php $selectedActivities = $_POST['activities'] ?? ($plan ? explode(',', $plan['activities']) : []);
                                foreach ($activities as $activity): $checked = in_array($activity, $selectedActivities) ? 'checked' : ''; ?>
                                    <div>
                                        <input type="checkbox" name="activities[]" value="<?= $activity ?>" id="act_<?= $activity ?>" class="activity-checkbox" <?= $checked ?>>
                                        <label for="act_<?= $activity ?>" class="activity-label"><?= $activity ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- 写真アップロード -->
                        <div class="form-group">
                            <label class="form-label">📷 写真を追加（任意）</label>
                            <div class="photo-upload-area <?= ($plan['photo'] ?? '') ? 'has-photo' : '' ?>" id="photoUploadArea" onclick="document.getElementById('photoInput').click()">
                                <div id="photoPlaceholder" style="<?= ($plan['photo'] ?? '') ? 'display:none;' : '' ?>">
                                    <p style="font-size: 2rem; margin-bottom: var(--space-sm);">📷</p>
                                    <p>クリックして写真を選択</p>
                                    <p class="photo-info">JPEG, PNG, GIF, WebP / 最大5MB</p>
                                </div>
                                <div id="photoPreviewContainer" style="<?= ($plan['photo'] ?? '') ? '' : 'display:none;' ?>">
                                    <?php if ($plan['photo'] ?? ''): ?>
                                        <img src="uploads/plans/<?= htmlspecialchars($plan['photo']) ?>" class="photo-preview" id="photoPreview">
                                    <?php else: ?>
                                        <img src="" class="photo-preview" id="photoPreview" style="display:none;">
                                    <?php endif; ?>
                                </div>
                            </div>
                            <input type="file" id="photoInput" name="photo" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none;" onchange="previewPhoto(this)">
                            <div class="photo-actions" id="photoActions" style="<?= ($plan['photo'] ?? '') ? '' : 'display:none;' ?>">
                                <button type="button" class="photo-delete-btn" onclick="deletePhoto(event)">🗑️ 写真を削除</button>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">詳細・メモ</label>
                            <textarea name="description" class="form-textarea" rows="4" placeholder="旅行の詳細や、一緒に行きたい人へのメッセージなど"><?= htmlspecialchars($_POST['description'] ?? $plan['description'] ?? '') ?></textarea>
                        </div>
                        
                        <div style="display: flex; gap: var(--space-md);">
                            <a href="index.php" class="btn btn-secondary" style="flex: 1;">← タイトルに戻る</a>
                            <button type="submit" class="btn btn-primary" style="flex: 1;"><?= $editMode ? '更新する' : '計画を作成！' ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const initialLat = <?= $plan['latitude'] ?? 35.6894 ?>;
        const initialLng = <?= $plan['longitude'] ?? 139.6917 ?>;
        const hasInitialLocation = <?= ($plan['latitude'] ?? false) ? 'true' : 'false' ?>;
        
        const map = L.map('map').setView([initialLat, initialLng], hasInitialLocation ? 12 : 5);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);
        
        let marker = null;
        if (hasInitialLocation) {
            marker = L.marker([initialLat, initialLng]).addTo(map);
            document.getElementById('locationStatus').style.display = 'inline-flex';
        }
        
        map.on('click', function(e) {
            if (marker) map.removeLayer(marker);
            marker = L.marker([e.latlng.lat, e.latlng.lng]).addTo(map);
            document.getElementById('latitude').value = e.latlng.lat.toFixed(8);
            document.getElementById('longitude').value = e.latlng.lng.toFixed(8);
            document.getElementById('locationStatus').style.display = 'inline-flex';
        });
        
        function updateMapFromPrefecture() {
            const select = document.getElementById('prefecture');
            const option = select.options[select.selectedIndex];
            if (option.dataset.lat && option.dataset.lng) {
                map.setView([parseFloat(option.dataset.lat), parseFloat(option.dataset.lng)], 10);
            }
        }
        
        if (document.getElementById('prefecture').value && !hasInitialLocation) updateMapFromPrefecture();
        
        // 写真プレビュー
        function previewPhoto(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                if (file.size > 5 * 1024 * 1024) {
                    alert('ファイルサイズが大きすぎます（最大5MB）');
                    input.value = '';
                    return;
                }
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('photoPreview').src = e.target.result;
                    document.getElementById('photoPreview').style.display = 'block';
                    document.getElementById('photoPlaceholder').style.display = 'none';
                    document.getElementById('photoPreviewContainer').style.display = 'block';
                    document.getElementById('photoActions').style.display = 'flex';
                    document.getElementById('photoUploadArea').classList.add('has-photo');
                    document.getElementById('deletePhoto').value = '0';
                };
                reader.readAsDataURL(file);
            }
        }
        
        // 写真削除
        function deletePhoto(event) {
            event.stopPropagation();
            document.getElementById('photoInput').value = '';
            document.getElementById('photoPreview').src = '';
            document.getElementById('photoPreview').style.display = 'none';
            document.getElementById('photoPlaceholder').style.display = 'block';
            document.getElementById('photoPreviewContainer').style.display = 'none';
            document.getElementById('photoActions').style.display = 'none';
            document.getElementById('photoUploadArea').classList.remove('has-photo');
            document.getElementById('deletePhoto').value = '1';
        }
    </script>
</body>
</html>
