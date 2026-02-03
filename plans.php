<?php
require_once 'includes/config.php';
if (!isLoggedIn()) { header('Location: login.php'); exit; }

$currentUser = getCurrentUser();
$pdo = getDB();
$pdo->prepare("UPDATE likes SET is_read = 1 WHERE plan_user_id = ?")->execute([$currentUser['id']]);

$filter = $_GET['filter'] ?? 'all';
$view = $_GET['view'] ?? 'list';

// 検索パラメータ
$searchPrefecture = $_GET['prefecture'] ?? '';
$searchPurpose = $_GET['purpose'] ?? '';
$searchActivity = $_GET['activity'] ?? '';
$searchBirthplace = $_GET['birthplace'] ?? '';
$searchGender = $_GET['gender'] ?? '';
$searchAgeMin = $_GET['age_min'] ?? '';
$searchAgeMax = $_GET['age_max'] ?? '';

$sql = "SELECT tp.*, u.display_name, u.profile_image, u.username, u.birthplace, u.gender, u.age,
    (SELECT COUNT(*) FROM likes WHERE plan_id = tp.id) as like_count,
    (SELECT COUNT(*) FROM comments WHERE plan_id = tp.id) as comment_count,
    (SELECT COUNT(*) FROM likes WHERE plan_id = tp.id AND user_id = ?) as user_liked
    FROM travel_plans tp JOIN users u ON tp.user_id = u.id WHERE 1=1";
$params = [$currentUser['id']];

if ($filter === 'mine') { $sql .= " AND tp.user_id = ?"; $params[] = $currentUser['id']; }
elseif ($filter === 'others') { $sql .= " AND tp.user_id != ?"; $params[] = $currentUser['id']; }

// 検索条件
if ($searchPrefecture) { $sql .= " AND tp.prefecture = ?"; $params[] = $searchPrefecture; }
if ($searchPurpose) { $sql .= " AND tp.purpose = ?"; $params[] = $searchPurpose; }
if ($searchActivity) { $sql .= " AND tp.activities LIKE ?"; $params[] = "%$searchActivity%"; }
if ($searchBirthplace) { $sql .= " AND u.birthplace = ?"; $params[] = $searchBirthplace; }
if ($searchGender) { $sql .= " AND u.gender = ?"; $params[] = $searchGender; }
if ($searchAgeMin) { $sql .= " AND u.age >= ?"; $params[] = (int)$searchAgeMin; }
if ($searchAgeMax) { $sql .= " AND u.age <= ?"; $params[] = (int)$searchAgeMax; }

$sql .= " ORDER BY tp.created_at DESC";
$stmt = $pdo->prepare($sql); $stmt->execute($params); $plans = $stmt->fetchAll();

$prefectures = ['北海道','青森県','岩手県','宮城県','秋田県','山形県','福島県','茨城県','栃木県','群馬県','埼玉県','千葉県','東京都','神奈川県','新潟県','富山県','石川県','福井県','山梨県','長野県','岐阜県','静岡県','愛知県','三重県','滋賀県','京都府','大阪府','兵庫県','奈良県','和歌山県','鳥取県','島根県','岡山県','広島県','山口県','徳島県','香川県','愛媛県','高知県','福岡県','佐賀県','長崎県','熊本県','大分県','宮崎県','鹿児島県','沖縄県','海外'];

$purposes = ['リフレッシュ・癒し','観光・名所巡り','グルメ・食べ歩き','アクティビティ・体験','友人・家族との思い出作り','一人旅・自分探し','記念日・お祝い','ワーケーション','写真撮影','その他'];

$activities = ['温泉','神社・寺院巡り','自然散策','海・ビーチ','山・ハイキング','美術館・博物館','ショッピング','カフェ巡り','地元グルメ','テーマパーク','スポーツ','祭り・イベント','写真撮影','キャンプ','ドライブ','歴史探訪','工場見学','農業体験'];

$hasSearch = $searchPrefecture || $searchPurpose || $searchActivity || $searchBirthplace || $searchGender || $searchAgeMin || $searchAgeMax;
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>旅行計画を見る - CONNECT</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Zen+Maru+Gothic:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        .filters { display: flex; flex-wrap: wrap; gap: var(--space-md); margin-bottom: var(--space-lg); align-items: center; }
        .filter-tabs { display: flex; gap: var(--space-sm); }
        .filter-tab { padding: var(--space-sm) var(--space-lg); border-radius: var(--radius-full); border: 2px solid var(--gray-200); background: white; color: var(--gray-600); text-decoration: none; }
        .filter-tab:hover, .filter-tab.active { border-color: var(--primary-500); background: var(--primary-50); color: var(--primary-600); }
        .view-toggle { display: flex; gap: var(--space-xs); margin-left: auto; }
        .view-btn { padding: var(--space-sm); border-radius: var(--radius-md); border: 2px solid var(--gray-200); background: white; font-size: 1.2rem; text-decoration: none; }
        .view-btn.active { border-color: var(--primary-500); background: var(--primary-50); }
        
        #planMap { height: 500px; border-radius: var(--radius-xl); margin-bottom: var(--space-xl); box-shadow: var(--shadow-lg); }
        
        .search-panel { background: white; border-radius: var(--radius-xl); padding: var(--space-lg); margin-bottom: var(--space-xl); box-shadow: var(--shadow-md); }
        .search-toggle { cursor: pointer; display: flex; align-items: center; justify-content: space-between; }
        .search-toggle h3 { margin: 0; }
        .search-content { display: none; margin-top: var(--space-lg); }
        .search-content.active { display: block; }
        .search-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--space-md); }
        .search-group label { font-size: 0.85rem; color: var(--gray-600); margin-bottom: 4px; display: block; }
        .age-range { display: flex; gap: var(--space-sm); align-items: center; }
        .age-range input { width: 80px; }
        .search-actions { margin-top: var(--space-lg); display: flex; gap: var(--space-md); }
        .active-filters { display: flex; flex-wrap: wrap; gap: var(--space-sm); margin-bottom: var(--space-lg); }
        .active-filter { background: var(--primary-100); color: var(--primary-700); padding: 4px 12px; border-radius: var(--radius-full); font-size: 0.85rem; display: flex; align-items: center; gap: 6px; }
        .active-filter a { color: var(--primary-700); text-decoration: none; font-weight: bold; }
        
        /* 写真メインのカードデザイン */
        .photo-plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 24px;
        }
        
        .photo-plan-card {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
        }
        .photo-plan-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        }
        .photo-plan-card.is-owner {
            border: 3px solid var(--accent-400);
        }
        
        /* 写真部分 */
        .photo-plan-image {
            width: 100%;
            height: 180px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
            overflow: hidden;
        }
        .photo-plan-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .photo-plan-image.no-photo {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: rgba(255,255,255,0.7);
        }
        /* 都道府県別のデフォルト背景色 */
        .photo-plan-image.pref-hokkaido { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); }
        .photo-plan-image.pref-tohoku { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .photo-plan-image.pref-kanto { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .photo-plan-image.pref-chubu { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .photo-plan-image.pref-kinki { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
        .photo-plan-image.pref-chugoku { background: linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%); }
        .photo-plan-image.pref-shikoku { background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%); }
        .photo-plan-image.pref-kyushu { background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%); }
        .photo-plan-image.pref-okinawa { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        .photo-plan-image.pref-overseas { background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%); }
        
        /* ユーザーアバター（写真の上に重ねる） */
        .photo-plan-avatar {
            position: absolute;
            bottom: -20px;
            left: 16px;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            border: 3px solid #fff;
            overflow: hidden;
            background: var(--primary-300);
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        .photo-plan-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        /* いいねボタン（写真の右上） */
        .photo-plan-like {
            position: absolute;
            top: 12px;
            right: 12px;
            background: rgba(255,255,255,0.9);
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
            transition: transform 0.2s;
        }
        .photo-plan-like:hover {
            transform: scale(1.1);
        }
        
        /* コンテンツ部分 */
        .photo-plan-content {
            padding: 28px 16px 16px;
        }
        .photo-plan-destination {
            font-family: 'Zen Maru Gothic', sans-serif;
            font-size: 1.1rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 4px;
        }
        .photo-plan-meta {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 12px;
        }
        .photo-plan-purpose {
            display: inline-block;
            background: var(--primary-50);
            color: var(--primary-700);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            margin-bottom: 8px;
        }
        .photo-plan-description {
            font-size: 0.85rem;
            color: #555;
            line-height: 1.5;
            margin-bottom: 12px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        /* フッター */
        .photo-plan-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px;
            border-top: 1px solid #eee;
            background: #fafafa;
        }
        .photo-plan-stats {
            display: flex;
            gap: 12px;
            font-size: 0.85rem;
            color: #666;
        }
        .photo-plan-actions {
            display: flex;
            gap: 8px;
        }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <?php include 'includes/header.php'; ?>
        <main class="main-content">
            <div class="container">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-xl);">
                    <h1>旅行計画を見る</h1>
                    <a href="index.php" class="btn btn-secondary">← タイトルに戻る</a>
                </div>
                
                <?php if (isset($_GET['success'])): ?><div class="alert alert-success">✅ 旅行計画を保存しました！</div><?php endif; ?>
                
                <div class="filters">
                    <div class="filter-tabs">
                        <a href="?filter=all&view=<?= $view ?>" class="filter-tab <?= $filter === 'all' ? 'active' : '' ?>">すべて</a>
                        <a href="?filter=mine&view=<?= $view ?>" class="filter-tab <?= $filter === 'mine' ? 'active' : '' ?>">自分の計画</a>
                        <a href="?filter=others&view=<?= $view ?>" class="filter-tab <?= $filter === 'others' ? 'active' : '' ?>">他の人の計画</a>
                    </div>
                    <div class="view-toggle">
                        <a href="?filter=<?= $filter ?>&view=list" class="view-btn <?= $view === 'list' ? 'active' : '' ?>" title="リスト表示">📋</a>
                        <a href="?filter=<?= $filter ?>&view=map" class="view-btn <?= $view === 'map' ? 'active' : '' ?>" title="地図表示">🗺️</a>
                    </div>
                </div>
                
                <!-- 検索パネル -->
                <div class="search-panel">
                    <div class="search-toggle" onclick="this.nextElementSibling.classList.toggle('active'); this.querySelector('.toggle-icon').textContent = this.nextElementSibling.classList.contains('active') ? '▲' : '▼';">
                        <h3>🔍 詳細検索</h3>
                        <span class="toggle-icon">▼</span>
                    </div>
                    <div class="search-content <?= $hasSearch ? 'active' : '' ?>">
                        <form method="GET">
                            <input type="hidden" name="filter" value="<?= $filter ?>">
                            <input type="hidden" name="view" value="<?= $view ?>">
                            <div class="search-grid">
                                <div class="search-group">
                                    <label>都道府県</label>
                                    <select name="prefecture" class="form-select">
                                        <option value="">すべて</option>
                                        <?php foreach ($prefectures as $p): ?><option value="<?= $p ?>" <?= $searchPrefecture === $p ? 'selected' : '' ?>><?= $p ?></option><?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="search-group">
                                    <label>旅行目的</label>
                                    <select name="purpose" class="form-select">
                                        <option value="">すべて</option>
                                        <?php foreach ($purposes as $p): ?><option value="<?= $p ?>" <?= $searchPurpose === $p ? 'selected' : '' ?>><?= $p ?></option><?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="search-group">
                                    <label>やりたいこと</label>
                                    <select name="activity" class="form-select">
                                        <option value="">すべて</option>
                                        <?php foreach ($activities as $a): ?><option value="<?= $a ?>" <?= $searchActivity === $a ? 'selected' : '' ?>><?= $a ?></option><?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="search-group">
                                    <label>投稿者の出身地</label>
                                    <select name="birthplace" class="form-select">
                                        <option value="">すべて</option>
                                        <?php foreach ($prefectures as $p): ?><option value="<?= $p ?>" <?= $searchBirthplace === $p ? 'selected' : '' ?>><?= $p ?></option><?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="search-group">
                                    <label>投稿者の性別</label>
                                    <select name="gender" class="form-select">
                                        <option value="">すべて</option>
                                        <option value="male" <?= $searchGender === 'male' ? 'selected' : '' ?>>男性</option>
                                        <option value="female" <?= $searchGender === 'female' ? 'selected' : '' ?>>女性</option>
                                    </select>
                                </div>
                                <div class="search-group">
                                    <label>投稿者の年齢</label>
                                    <div class="age-range">
                                        <input type="number" name="age_min" class="form-input" placeholder="下限" value="<?= htmlspecialchars($searchAgeMin) ?>">
                                        <span>〜</span>
                                        <input type="number" name="age_max" class="form-input" placeholder="上限" value="<?= htmlspecialchars($searchAgeMax) ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="search-actions">
                                <button type="submit" class="btn btn-primary">検索</button>
                                <a href="?filter=<?= $filter ?>&view=<?= $view ?>" class="btn btn-secondary">クリア</a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <?php if ($hasSearch): ?>
                <div class="active-filters">
                    <?php if ($searchPrefecture): ?><span class="active-filter">都道府県: <?= htmlspecialchars($searchPrefecture) ?></span><?php endif; ?>
                    <?php if ($searchPurpose): ?><span class="active-filter">目的: <?= htmlspecialchars($searchPurpose) ?></span><?php endif; ?>
                    <?php if ($searchActivity): ?><span class="active-filter">やりたいこと: <?= htmlspecialchars($searchActivity) ?></span><?php endif; ?>
                    <?php if ($searchBirthplace): ?><span class="active-filter">出身地: <?= htmlspecialchars($searchBirthplace) ?></span><?php endif; ?>
                    <?php if ($searchGender): ?><span class="active-filter">性別: <?= $searchGender === 'male' ? '男性' : '女性' ?></span><?php endif; ?>
                    <?php if ($searchAgeMin || $searchAgeMax): ?><span class="active-filter">年齢: <?= $searchAgeMin ?: '?' ?>〜<?= $searchAgeMax ?: '?' ?>歳</span><?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($view === 'map'): ?>
                <div id="planMap"></div>
                <?php endif; ?>
                
                <?php if (empty($plans)): ?>
                    <div class="card" style="text-align: center; padding: 60px;">
                        <p style="font-size: 3rem;">📝</p>
                        <p style="color: var(--gray-500);"><?= $hasSearch ? '条件に一致する計画がありません' : 'まだ旅行計画がありません' ?></p>
                        <?php if (!$hasSearch): ?><a href="plan_create.php" class="btn btn-primary mt-lg">最初の計画を作る</a><?php endif; ?>
                    </div>
                <?php elseif ($view === 'list'): ?>
                    <div class="photo-plans-grid">
                        <?php foreach ($plans as $p): 
                            $isOwner = $p['user_id'] === $currentUser['id']; 
                            $acts = $p['activities'] ? explode(',', $p['activities']) : [];
                            
                            // 都道府県から地域を判定
                            $prefClass = 'pref-kanto';
                            $pref = $p['prefecture'];
                            if ($pref === '北海道') $prefClass = 'pref-hokkaido';
                            elseif (in_array($pref, ['青森県','岩手県','宮城県','秋田県','山形県','福島県'])) $prefClass = 'pref-tohoku';
                            elseif (in_array($pref, ['茨城県','栃木県','群馬県','埼玉県','千葉県','東京都','神奈川県'])) $prefClass = 'pref-kanto';
                            elseif (in_array($pref, ['新潟県','富山県','石川県','福井県','山梨県','長野県','岐阜県','静岡県','愛知県'])) $prefClass = 'pref-chubu';
                            elseif (in_array($pref, ['三重県','滋賀県','京都府','大阪府','兵庫県','奈良県','和歌山県'])) $prefClass = 'pref-kinki';
                            elseif (in_array($pref, ['鳥取県','島根県','岡山県','広島県','山口県'])) $prefClass = 'pref-chugoku';
                            elseif (in_array($pref, ['徳島県','香川県','愛媛県','高知県'])) $prefClass = 'pref-shikoku';
                            elseif (in_array($pref, ['福岡県','佐賀県','長崎県','熊本県','大分県','宮崎県','鹿児島県'])) $prefClass = 'pref-kyushu';
                            elseif ($pref === '沖縄県') $prefClass = 'pref-okinawa';
                            elseif ($pref === '海外') $prefClass = 'pref-overseas';
                        ?>
                            <div class="photo-plan-card <?= $isOwner ? 'is-owner' : '' ?>" onclick="location.href='plan_detail.php?id=<?= $p['id'] ?>'">
                                <!-- 写真部分 -->
                                <div class="photo-plan-image <?= $p['photo'] ? '' : 'no-photo ' . $prefClass ?>">
                                    <?php if ($p['photo']): ?>
                                        <img src="uploads/plans/<?= htmlspecialchars($p['photo']) ?>" alt="<?= htmlspecialchars($p['destination']) ?>">
                                    <?php else: ?>
                                        ✈️
                                    <?php endif; ?>
                                    
                                    <!-- ユーザーアバター -->
                                    <div class="photo-plan-avatar" onclick="event.stopPropagation(); showUserProfile(<?= $p['user_id'] ?>)">
                                        <?php if ($p['profile_image'] !== 'default.png'): ?>
                                            <img src="uploads/profiles/<?= htmlspecialchars($p['profile_image']) ?>" alt="">
                                        <?php else: ?>
                                            <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:white;font-size:1.2rem;">👤</div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- いいねボタン（他人の計画のみ） -->
                                    <?php if (!$isOwner): ?>
                                        <button class="photo-plan-like" onclick="event.stopPropagation(); toggleLike(<?= $p['id'] ?>, this)" data-liked="<?= $p['user_liked'] ?>">
                                            <?= $p['user_liked'] ? '❤️' : '🤍' ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- コンテンツ部分 -->
                                <div class="photo-plan-content">
                                    <div class="photo-plan-destination"><?= htmlspecialchars($p['destination']) ?></div>
                                    <div class="photo-plan-meta">
                                        📍 <?= htmlspecialchars($p['prefecture']) ?> ・ 
                                        📅 <?= date('Y/m/d', strtotime($p['travel_date_start'])) ?>
                                        <?php if ($p['travel_date_end']): ?> 〜 <?= date('m/d', strtotime($p['travel_date_end'])) ?><?php endif; ?>
                                    </div>
                                    <?php if ($p['purpose']): ?>
                                        <span class="photo-plan-purpose"><?= htmlspecialchars($p['purpose']) ?></span>
                                    <?php endif; ?>
                                    <?php if ($p['description']): ?>
                                        <p class="photo-plan-description"><?= htmlspecialchars($p['description']) ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- フッター -->
                                <div class="photo-plan-footer">
                                    <div class="photo-plan-stats">
                                        <span>❤️ <?= $p['like_count'] ?></span>
                                        <span>💬 <?= $p['comment_count'] ?></span>
                                    </div>
                                    <div class="photo-plan-actions" onclick="event.stopPropagation();">
                                        <?php if ($isOwner): ?>
                                            <button onclick="deletePlan(<?= $p['id'] ?>)" class="btn btn-sm btn-secondary" style="color:var(--error);">削除</button>
                                            <a href="plan_create.php?edit=<?= $p['id'] ?>" class="btn btn-sm btn-secondary">編集</a>
                                        <?php else: ?>
                                            <a href="chat.php?user=<?= $p['user_id'] ?>" class="btn btn-sm btn-primary">💬</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <div class="profile-overlay" id="userProfileOverlay" onclick="if(event.target===this)this.classList.remove('active')">
        <div class="profile-tab" onclick="event.stopPropagation()">
            <div class="profile-tab-header">
                <button class="profile-tab-close" onclick="document.getElementById('userProfileOverlay').classList.remove('active')">&times;</button>
                <div class="profile-tab-avatar" id="userAvatar"></div>
                <h2 class="profile-tab-name" id="userName"></h2>
            </div>
            <div class="profile-tab-content" id="userProfileContent"></div>
            <div class="profile-tab-actions" id="userProfileActions"></div>
        </div>
    </div>
    
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
    <?php if ($view === 'map'): ?>
    const map = L.map('planMap').setView([36.5, 138], 5);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {attribution: '© OpenStreetMap'}).addTo(map);
    const plans = <?= json_encode(array_map(function($p) use ($currentUser) {
        return ['id'=>$p['id'], 'dest'=>$p['destination'], 'pref'=>$p['prefecture'], 'date'=>date('Y/m/d',strtotime($p['travel_date_start'])), 'lat'=>$p['latitude']??null, 'lng'=>$p['longitude']??null, 'mine'=>$p['user_id']===$currentUser['id'], 'user'=>$p['display_name']?:$p['username'], 'purpose'=>$p['purpose'], 'photo'=>$p['photo']];
    }, $plans)) ?>;
    const bounds = [];
    plans.forEach(p => {
        if (p.lat && p.lng) {
            const color = p.mine ? '#FF6B35' : '#0967D2';
            const marker = L.circleMarker([p.lat, p.lng], {radius: 10, fillColor: color, color: '#fff', weight: 2, fillOpacity: 0.8}).addTo(map);
            marker.bindPopup(`<div style="min-width:180px"><h4 style="margin:0 0 8px;color:${color}">📍 ${p.dest}</h4><p style="margin:4px 0;font-size:0.9rem">${p.pref}</p><p style="margin:4px 0;font-size:0.9rem">📅 ${p.date}</p>${p.purpose?`<p style="margin:4px 0;font-size:0.9rem">🎯 ${p.purpose}</p>`:''}<p style="margin:4px 0;font-size:0.9rem">👤 ${p.user}${p.mine?' (自分)':''}</p><a href="plan_detail.php?id=${p.id}" style="display:inline-block;margin-top:8px;padding:6px 12px;background:${color};color:#fff;border-radius:6px;text-decoration:none;font-size:0.85rem">詳細を見る</a></div>`);
            bounds.push([p.lat, p.lng]);
        }
    });
    if (bounds.length > 0) map.fitBounds(bounds, {padding: [50, 50]});
    <?php endif; ?>
    
    function toggleLike(id, btn) {
        fetch('api/like.php', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({plan_id:id})})
        .then(r=>r.json()).then(d=>{
            if(d.success){
                btn.innerHTML = d.liked ? '❤️' : '🤍';
                btn.dataset.liked = d.liked ? '1' : '0';
                // カード内の統計も更新
                const card = btn.closest('.photo-plan-card');
                const stats = card.querySelector('.photo-plan-stats span');
                stats.innerHTML = '❤️ ' + d.count;
            }
        });
    }
    function deletePlan(id) {
        if(confirm('この旅行計画を削除しますか？')){
            fetch('api/delete_plan.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({plan_id:id})})
            .then(r=>r.json()).then(d=>{if(d.success)location.reload();});
        }
    }
    function showUserProfile(id) {
        fetch('api/user_profile.php?id='+id).then(r=>r.json()).then(u=>{if(u.error)return;
        document.getElementById('userAvatar').innerHTML=u.profile_image!=='default.png'?'<img src="uploads/profiles/'+u.profile_image+'" alt="">':'<span style="display:flex;align-items:center;justify-content:center;width:100%;height:100%;font-size:3rem;">👤</span>';
        document.getElementById('userName').textContent=u.display_name||u.username;
        const g={male:'男性',female:'女性'};
        document.getElementById('userProfileContent').innerHTML='<div class="profile-info-item"><div class="profile-info-icon">📍</div><div><div class="profile-info-label">出身地</div><div class="profile-info-value">'+(u.birthplace||'未設定')+'</div></div></div><div class="profile-info-item"><div class="profile-info-icon">👤</div><div><div class="profile-info-label">性別</div><div class="profile-info-value">'+(g[u.gender]||'未設定')+'</div></div></div><div class="profile-info-item"><div class="profile-info-icon">🎂</div><div><div class="profile-info-label">年齢</div><div class="profile-info-value">'+(u.age?u.age+'歳':'未設定')+'</div></div></div><div class="profile-info-item"><div class="profile-info-icon">❤️</div><div><div class="profile-info-label">好きなもの</div><div class="profile-info-value">'+(u.interests||'未設定')+'</div></div></div><div class="profile-info-item"><div class="profile-info-icon">💬</div><div><div class="profile-info-label">コメント</div><div class="profile-info-value">'+(u.comment||'未設定')+'</div></div></div>';
        document.getElementById('userProfileActions').innerHTML=u.is_self?'':'<a href="chat.php?user='+id+'" class="btn btn-primary">💬 メッセージを送る</a>';
        document.getElementById('userProfileOverlay').classList.add('active');});
    }
    </script>
</body>
</html>
