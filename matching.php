<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$currentUser = getCurrentUser();
$pdo = getDB();

$stmt = $pdo->prepare("SELECT * FROM travel_plans WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$currentUser['id']]);
$myPlans = $stmt->fetchAll();

$matchedUser = null;
$matchedPlan = null;
$myMatchingPlan = null;
$noMatch = false;
$combinedPlan = null;
$matchScore = 0;
$matchPercentage = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['find_match'])) {
    $myPlanId = (int)$_POST['my_plan_id'];
    
    // フィルター条件
    $genderFilter = $_POST['gender_filter'] ?? 'any';
    $ageFilter = $_POST['age_filter'] ?? 'any';
    $dateFilter = $_POST['date_filter'] ?? 'any';
    
    // 重視設定
    $priorityLocation = isset($_POST['priority_location']) ? 1 : 0;
    $priorityPurpose = isset($_POST['priority_purpose']) ? 1 : 0;
    $priorityActivity = isset($_POST['priority_activity']) ? 1 : 0;
    
    $stmt = $pdo->prepare("SELECT * FROM travel_plans WHERE id = ? AND user_id = ?");
    $stmt->execute([$myPlanId, $currentUser['id']]);
    $myMatchingPlan = $stmt->fetch();
    
    if ($myMatchingPlan) {
        $myActivities = $myMatchingPlan['activities'] ? explode(',', $myMatchingPlan['activities']) : [];
        $myAge = $currentUser['age'];
        
        // 日程フィルターの日数を決定
        $dateDays = 365; // デフォルト（気にしない）
        if ($dateFilter === '7') $dateDays = 7;
        elseif ($dateFilter === '30') $dateDays = 30;
        
        // 基本クエリ
        $sql = "
            SELECT tp.*, u.id as user_id, u.display_name, u.username, u.profile_image, u.gender, u.age, u.birthplace
            FROM travel_plans tp
            JOIN users u ON tp.user_id = u.id
            WHERE tp.user_id != ?
            AND (tp.prefecture = ? OR tp.purpose = ? OR ABS(DATEDIFF(tp.travel_date_start, ?)) <= ?)
        ";
        $params = [
            $currentUser['id'],
            $myMatchingPlan['prefecture'],
            $myMatchingPlan['purpose'],
            $myMatchingPlan['travel_date_start'],
            $dateDays
        ];
        
        // 性別フィルター
        if ($genderFilter === 'male') {
            $sql .= " AND u.gender = 'male'";
        } elseif ($genderFilter === 'female') {
            $sql .= " AND u.gender = 'female'";
        }
        
        // 年齢フィルター
        if ($ageFilter !== 'any' && $myAge) {
            $ageRange = (int)$ageFilter;
            $sql .= " AND u.age IS NOT NULL AND u.age BETWEEN ? AND ?";
            $params[] = $myAge - $ageRange;
            $params[] = $myAge + $ageRange;
        }
        
        // 日程フィルター（厳密に）
        if ($dateFilter !== 'any') {
            $sql .= " AND ABS(DATEDIFF(tp.travel_date_start, ?)) <= ?";
            $params[] = $myMatchingPlan['travel_date_start'];
            $params[] = $dateDays;
        }
        
        $sql .= " ORDER BY tp.created_at DESC LIMIT 50";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $candidates = $stmt->fetchAll();
        
        $bestMatch = null;
        $bestScore = -1;
        
        // 重視設定によるボーナス倍率
        $locationBonus = $priorityLocation ? 2 : 1;
        $purposeBonus = $priorityPurpose ? 2 : 1;
        $activityBonus = $priorityActivity ? 2 : 1;
        
        foreach ($candidates as $candidate) {
            $score = 0;
            
            // 都道府県一致: 基本+3、重視時+6
            if ($candidate['prefecture'] === $myMatchingPlan['prefecture']) {
                $score += 3 * $locationBonus;
            }
            
            // 目的一致: 基本+2、重視時+4
            if ($candidate['purpose'] === $myMatchingPlan['purpose']) {
                $score += 2 * $purposeBonus;
            }
            
            // 日程の近さ: +1〜3
            $daysDiff = abs(strtotime($candidate['travel_date_start']) - strtotime($myMatchingPlan['travel_date_start'])) / 86400;
            if ($daysDiff <= 7) $score += 3;
            elseif ($daysDiff <= 14) $score += 2;
            elseif ($daysDiff <= 30) $score += 1;
            
            // やりたいこと一致: 基本各+1、重視時各+2
            $theirActivities = $candidate['activities'] ? explode(',', $candidate['activities']) : [];
            $commonActivities = array_intersect($myActivities, $theirActivities);
            $score += count($commonActivities) * $activityBonus;
            
            // 年齢が近いとボーナス: +1〜2
            if ($myAge && $candidate['age']) {
                $ageDiff = abs($myAge - $candidate['age']);
                if ($ageDiff <= 3) $score += 2;
                elseif ($ageDiff <= 5) $score += 1;
            }
            
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $candidate;
            }
        }
        
        if ($bestMatch && $bestScore >= 2) {
            $matchedUser = $bestMatch;
            $matchedPlan = $bestMatch;
            $matchScore = $bestScore;
            
            // 相性パーセンテージ計算（最大スコアを考慮）
            $maxPossibleScore = (3 * $locationBonus) + (2 * $purposeBonus) + 3 + (5 * $activityBonus) + 2;
            $matchPercentage = min(100, round(($bestScore / $maxPossibleScore) * 100));
            
            // 2つの計画を混ぜた提案を生成
            $theirActivities = $matchedPlan['activities'] ? explode(',', $matchedPlan['activities']) : [];
            $commonActivities = array_intersect($myActivities, $theirActivities);
            $allActivities = array_unique(array_merge($myActivities, $theirActivities));
            
            $myDate = strtotime($myMatchingPlan['travel_date_start']);
            $theirDate = strtotime($matchedPlan['travel_date_start']);
            $suggestedDate = date('Y-m-d', ($myDate + $theirDate) / 2);
            
            $myEnd = $myMatchingPlan['travel_date_end'] ? strtotime($myMatchingPlan['travel_date_end']) : $myDate;
            $theirEnd = $matchedPlan['travel_date_end'] ? strtotime($matchedPlan['travel_date_end']) : $theirDate;
            $myDuration = ($myEnd - $myDate) / 86400;
            $theirDuration = ($theirEnd - $theirDate) / 86400;
            $suggestedDuration = max(ceil(($myDuration + $theirDuration) / 2), 1);
            $suggestedEndDate = date('Y-m-d', strtotime($suggestedDate) + ($suggestedDuration * 86400));
            
            $combinedPlan = [
                'destination' => $myMatchingPlan['prefecture'] === $matchedPlan['prefecture'] 
                    ? $myMatchingPlan['destination'] . ' & ' . $matchedPlan['destination']
                    : $myMatchingPlan['destination'],
                'prefecture' => $myMatchingPlan['prefecture'],
                'date_start' => $suggestedDate,
                'date_end' => $suggestedEndDate,
                'purpose' => $myMatchingPlan['purpose'] === $matchedPlan['purpose'] 
                    ? $myMatchingPlan['purpose'] 
                    : $myMatchingPlan['purpose'] . ' × ' . $matchedPlan['purpose'],
                'common_activities' => $commonActivities,
                'all_activities' => $allActivities,
                'my_unique' => array_diff($myActivities, $theirActivities),
                'their_unique' => array_diff($theirActivities, $myActivities),
            ];
        } else {
            $noMatch = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>マッチング - CONNECT</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .match-search-card { text-align: center; padding: var(--space-2xl); }
        .match-icon { font-size: 4rem; margin-bottom: var(--space-lg); }
        .filter-section { background: var(--gray-50); border-radius: var(--radius-lg); padding: var(--space-lg); margin: var(--space-xl) 0; text-align: left; }
        .filter-section h4 { margin-bottom: var(--space-md); color: var(--gray-700); }
        .filter-grid { display: grid; gap: var(--space-lg); }
        .filter-group label:first-child { display: block; font-weight: 600; margin-bottom: var(--space-sm); color: var(--gray-600); font-size: 0.9rem; }
        .filter-options { display: flex; flex-wrap: wrap; gap: var(--space-sm); }
        .filter-option { padding: var(--space-sm) var(--space-md); border: 2px solid var(--gray-200); border-radius: var(--radius-full); cursor: pointer; transition: all var(--transition-fast); font-size: 0.9rem; }
        .filter-option:hover { border-color: var(--primary-300); }
        .filter-option.selected { border-color: var(--primary-500); background: var(--primary-50); color: var(--primary-700); }
        .filter-option input { display: none; }
        
        .priority-section { background: var(--accent-50); border-radius: var(--radius-lg); padding: var(--space-lg); margin: var(--space-lg) 0; text-align: left; border: 2px solid var(--accent-200); }
        .priority-section h4 { margin-bottom: var(--space-md); color: var(--accent-700); }
        .priority-options { display: flex; flex-wrap: wrap; gap: var(--space-md); }
        .priority-option { display: flex; align-items: center; gap: var(--space-sm); padding: var(--space-sm) var(--space-md); border: 2px solid var(--gray-200); border-radius: var(--radius-lg); cursor: pointer; transition: all var(--transition-fast); background: white; }
        .priority-option:hover { border-color: var(--accent-300); }
        .priority-option.checked { border-color: var(--accent-500); background: var(--accent-100); }
        .priority-option input { display: none; }
        .priority-icon { font-size: 1.2rem; }
        .priority-check { width: 20px; height: 20px; border: 2px solid var(--gray-300); border-radius: 4px; display: flex; align-items: center; justify-content: center; }
        .priority-option.checked .priority-check { background: var(--accent-500); border-color: var(--accent-500); color: white; }
        
        .match-result-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.8); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: var(--space-lg); overflow-y: auto; }
        .match-result-card { background: white; border-radius: var(--radius-2xl); padding: var(--space-2xl); max-width: 500px; width: 100%; text-align: center; animation: popIn 0.5s ease; max-height: 90vh; overflow-y: auto; }
        @keyframes popIn { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        .match-avatars { display: flex; justify-content: center; align-items: center; gap: var(--space-lg); margin: var(--space-xl) 0; }
        .match-avatar { width: 100px; height: 100px; border-radius: 50%; border: 4px solid var(--primary-200); overflow: hidden; }
        .match-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .match-heart { font-size: 2rem; animation: pulse 1s infinite; }
        @keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.2); } }
        .match-percentage { font-size: 3rem; font-weight: 700; color: var(--primary-500); margin: var(--space-md) 0; }
        .match-percentage-bar { width: 80%; height: 10px; background: var(--gray-200); border-radius: var(--radius-full); margin: 0 auto var(--space-lg); overflow: hidden; }
        .match-percentage-fill { height: 100%; background: linear-gradient(90deg, var(--primary-400), var(--accent-500)); border-radius: var(--radius-full); }
        .no-match { text-align: center; padding: var(--space-2xl); }
        .combined-plan { background: var(--gray-50); border-radius: var(--radius-xl); padding: var(--space-lg); margin: var(--space-lg) 0; text-align: left; }
        .combined-plan h4 { text-align: center; margin-bottom: var(--space-lg); color: var(--primary-600); }
        .combined-item { display: flex; align-items: flex-start; gap: var(--space-md); margin-bottom: var(--space-md); }
        .combined-icon { font-size: 1.5rem; }
        .combined-label { font-size: 0.8rem; color: var(--gray-500); }
        .activity-tags { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 4px; }
        .activity-tag-small { font-size: 0.75rem; padding: 2px 8px; border-radius: var(--radius-full); }
        .activity-tag-common { background: var(--success); color: white; }
        .activity-tag-mine { background: var(--accent-100); color: var(--accent-700); }
        .activity-tag-theirs { background: var(--primary-100); color: var(--primary-700); }
        .confetti { position: fixed; width: 10px; height: 10px; top: -10px; animation: confetti-fall 3s ease-out forwards; pointer-events: none; z-index: 1001; }
        @keyframes confetti-fall { 0% { transform: translateY(0) rotate(0deg); opacity: 1; } 100% { transform: translateY(100vh) rotate(720deg); opacity: 0; } }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <?php include 'includes/header.php'; ?>
        
        <main class="main-content">
            <div class="container">
                <?php if ($matchedUser): ?>
                <div class="match-result-overlay" id="matchResult">
                    <div class="match-result-card">
                        <h2>🎉 マッチしました！</h2>
                        <div class="match-avatars">
                            <div class="match-avatar">
                                <?php if ($currentUser['profile_image'] !== 'default.png'): ?>
                                    <img src="uploads/profiles/<?= htmlspecialchars($currentUser['profile_image']) ?>" alt="">
                                <?php else: ?>
                                    <div style="width:100%;height:100%;background:var(--primary-300);display:flex;align-items:center;justify-content:center;font-size:2.5rem;">👤</div>
                                <?php endif; ?>
                            </div>
                            <div class="match-heart">💕</div>
                            <div class="match-avatar">
                                <?php if ($matchedUser['profile_image'] !== 'default.png'): ?>
                                    <img src="uploads/profiles/<?= htmlspecialchars($matchedUser['profile_image']) ?>" alt="">
                                <?php else: ?>
                                    <div style="width:100%;height:100%;background:var(--primary-300);display:flex;align-items:center;justify-content:center;font-size:2.5rem;">👤</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <h3><?= htmlspecialchars($matchedUser['display_name'] ?: $matchedUser['username']) ?>さん</h3>
                        <p style="color: var(--gray-500);">
                            <?= htmlspecialchars($matchedPlan['destination']) ?>への旅行を計画中
                        </p>
                        
                        <div class="match-percentage"><?= $matchPercentage ?>%</div>
                        <div class="match-percentage-bar">
                            <div class="match-percentage-fill" style="width: <?= $matchPercentage ?>%;"></div>
                        </div>
                        <p style="color: var(--gray-500); font-size: 0.9rem;">相性スコア</p>
                        
                        <?php if ($combinedPlan): ?>
                        <div class="combined-plan">
                            <h4>✨ 2人の旅プラン提案</h4>
                            <div class="combined-item">
                                <span class="combined-icon">📍</span>
                                <div>
                                    <div class="combined-label">行き先</div>
                                    <div><?= htmlspecialchars($combinedPlan['destination']) ?></div>
                                </div>
                            </div>
                            <div class="combined-item">
                                <span class="combined-icon">📅</span>
                                <div>
                                    <div class="combined-label">おすすめ日程</div>
                                    <div><?= date('Y/m/d', strtotime($combinedPlan['date_start'])) ?> 〜 <?= date('Y/m/d', strtotime($combinedPlan['date_end'])) ?></div>
                                </div>
                            </div>
                            <div class="combined-item">
                                <span class="combined-icon">🎯</span>
                                <div>
                                    <div class="combined-label">旅の目的</div>
                                    <div><?= htmlspecialchars($combinedPlan['purpose']) ?></div>
                                </div>
                            </div>
                            
                            <?php if (!empty($combinedPlan['common_activities'])): ?>
                            <div class="combined-item">
                                <span class="combined-icon">🤝</span>
                                <div>
                                    <div class="combined-label">共通のやりたいこと</div>
                                    <div class="activity-tags">
                                        <?php foreach ($combinedPlan['common_activities'] as $act): ?>
                                            <span class="activity-tag-small activity-tag-common"><?= htmlspecialchars($act) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($combinedPlan['my_unique'])): ?>
                            <div class="combined-item">
                                <span class="combined-icon">🙋</span>
                                <div>
                                    <div class="combined-label">あなたのやりたいこと</div>
                                    <div class="activity-tags">
                                        <?php foreach ($combinedPlan['my_unique'] as $act): ?>
                                            <span class="activity-tag-small activity-tag-mine"><?= htmlspecialchars($act) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($combinedPlan['their_unique'])): ?>
                            <div class="combined-item">
                                <span class="combined-icon">👥</span>
                                <div>
                                    <div class="combined-label"><?= htmlspecialchars($matchedUser['display_name'] ?: $matchedUser['username']) ?>さんのやりたいこと</div>
                                    <div class="activity-tags">
                                        <?php foreach ($combinedPlan['their_unique'] as $act): ?>
                                            <span class="activity-tag-small activity-tag-theirs"><?= htmlspecialchars($act) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div style="display: flex; flex-direction: column; gap: var(--space-md);">
                            <a href="chat.php?user=<?= $matchedUser['user_id'] ?>" class="btn btn-accent btn-lg">💬 メッセージを送る！</a>
                            <a href="plan_detail.php?id=<?= $matchedPlan['id'] ?>" class="btn btn-secondary">旅行計画を見る</a>
                            <button onclick="document.getElementById('matchResult').style.display='none'" class="btn btn-secondary">閉じる</button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-xl);">
                    <h1>🔍 マッチング</h1>
                    <a href="index.php" class="btn btn-secondary">← タイトルに戻る</a>
                </div>
                
                <?php if (empty($myPlans)): ?>
                    <div class="card match-search-card">
                        <div class="match-icon">📝</div>
                        <h2>まずは旅行計画を作りましょう！</h2>
                        <p style="color: var(--gray-500); margin: var(--space-lg) 0;">マッチングには旅行計画が必要です。</p>
                        <a href="plan_create.php" class="btn btn-primary btn-lg">旅行計画を作る</a>
                    </div>
                <?php else: ?>
                    <div class="card match-search-card">
                        <div class="match-icon">🌍</div>
                        <h2>旅仲間を探そう！</h2>
                        <p style="color: var(--gray-500); margin: var(--space-lg) 0;">あなたの旅行計画に合った仲間を見つけ、<br>2つの計画を混ぜたおすすめプランを提案します。</p>
                        
                        <form method="POST">
                            <div class="form-group" style="text-align: left;">
                                <label class="form-label">マッチングに使う旅行計画</label>
                                <select name="my_plan_id" class="form-select" required>
                                    <?php foreach ($myPlans as $plan): ?>
                                        <option value="<?= $plan['id'] ?>">
                                            <?= htmlspecialchars($plan['destination']) ?>（<?= htmlspecialchars($plan['prefecture']) ?>）- <?= date('Y/m/d', strtotime($plan['travel_date_start'])) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- フィルター条件 -->
                            <div class="filter-section">
                                <h4>🎯 マッチング条件（フィルター）</h4>
                                <div class="filter-grid">
                                    <div class="filter-group">
                                        <label>相手の性別</label>
                                        <div class="filter-options">
                                            <label class="filter-option selected" data-group="gender">
                                                <input type="radio" name="gender_filter" value="any" checked>
                                                どちらでも
                                            </label>
                                            <label class="filter-option" data-group="gender">
                                                <input type="radio" name="gender_filter" value="male">
                                                👨 男性のみ
                                            </label>
                                            <label class="filter-option" data-group="gender">
                                                <input type="radio" name="gender_filter" value="female">
                                                👩 女性のみ
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="filter-group">
                                        <label>相手の年齢</label>
                                        <div class="filter-options">
                                            <label class="filter-option selected" data-group="age">
                                                <input type="radio" name="age_filter" value="any" checked>
                                                気にしない
                                            </label>
                                            <label class="filter-option" data-group="age">
                                                <input type="radio" name="age_filter" value="5">
                                                ±5歳
                                            </label>
                                            <label class="filter-option" data-group="age">
                                                <input type="radio" name="age_filter" value="10">
                                                ±10歳
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="filter-group">
                                        <label>日程の近さ</label>
                                        <div class="filter-options">
                                            <label class="filter-option selected" data-group="date">
                                                <input type="radio" name="date_filter" value="any" checked>
                                                気にしない
                                            </label>
                                            <label class="filter-option" data-group="date">
                                                <input type="radio" name="date_filter" value="7">
                                                1週間以内
                                            </label>
                                            <label class="filter-option" data-group="date">
                                                <input type="radio" name="date_filter" value="30">
                                                1ヶ月以内
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if (!$currentUser['age']): ?>
                                    <p style="color: var(--warning); font-size: 0.85rem; margin-top: var(--space-md);">
                                        ⚠️ 年齢フィルターを使うには、<a href="profile_edit.php">プロフィール</a>で年齢を設定してください
                                    </p>
                                <?php endif; ?>
                            </div>
                            
                            <!-- 重視設定 -->
                            <div class="priority-section">
                                <h4>⭐ 重視する項目（複数選択可）</h4>
                                <p style="font-size: 0.85rem; color: var(--gray-500); margin-bottom: var(--space-md);">選択した項目はマッチングスコアが2倍になります</p>
                                <div class="priority-options">
                                    <label class="priority-option" onclick="this.classList.toggle('checked')">
                                        <input type="checkbox" name="priority_location" value="1">
                                        <div class="priority-check">✓</div>
                                        <span class="priority-icon">📍</span>
                                        <span>場所を重視</span>
                                    </label>
                                    <label class="priority-option" onclick="this.classList.toggle('checked')">
                                        <input type="checkbox" name="priority_purpose" value="1">
                                        <div class="priority-check">✓</div>
                                        <span class="priority-icon">🎯</span>
                                        <span>目的を重視</span>
                                    </label>
                                    <label class="priority-option" onclick="this.classList.toggle('checked')">
                                        <input type="checkbox" name="priority_activity" value="1">
                                        <div class="priority-check">✓</div>
                                        <span class="priority-icon">🎮</span>
                                        <span>やりたいことを重視</span>
                                    </label>
                                </div>
                            </div>
                            
                            <button type="submit" name="find_match" class="btn btn-accent btn-lg" style="width: 100%;">🔍 マッチングを探す！</button>
                        </form>
                    </div>
                    
                    <?php if ($noMatch): ?>
                        <div class="no-match mt-lg">
                            <p style="font-size: 3rem;">😢</p>
                            <h3>マッチする人が見つかりませんでした</h3>
                            <p style="color: var(--gray-500); margin-top: var(--space-md);">条件を変えて、また試してみてください。</p>
                            <a href="plans.php?filter=others" class="btn btn-primary mt-lg">旅行計画を見る</a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <div class="card mt-lg">
                    <h3 style="margin-bottom: var(--space-md);">📊 マッチングの仕組み</h3>
                    <ul style="color: var(--gray-600); line-height: 2;">
                        <li>📍 行きたい場所が同じエリア → <strong>+3点</strong>（重視時: +6点）</li>
                        <li>🎯 旅行目的が同じ → <strong>+2点</strong>（重視時: +4点）</li>
                        <li>📅 旅行時期が近い → <strong>+1〜3点</strong></li>
                        <li>🎮 やりたいことが共通 → <strong>各+1点</strong>（重視時: 各+2点）</li>
                        <li>👥 年齢が近い → <strong>+1〜2点</strong></li>
                    </ul>
                </div>
            </div>
        </main>
    </div>
    
    <script>
    // フィルターオプションの選択UI
    document.querySelectorAll('.filter-option').forEach(option => {
        option.addEventListener('click', function() {
            const group = this.dataset.group;
            document.querySelectorAll(`.filter-option[data-group="${group}"]`).forEach(o => o.classList.remove('selected'));
            this.classList.add('selected');
        });
    });
    </script>
    
    <?php if ($matchedUser): ?>
    <script>
        const colors = ['#FF6B35', '#0967D2', '#10B981', '#F59E0B', '#EF4444'];
        for (let i = 0; i < 50; i++) {
            const confetti = document.createElement('div');
            confetti.className = 'confetti';
            confetti.style.left = Math.random() * 100 + 'vw';
            confetti.style.background = colors[Math.floor(Math.random() * colors.length)];
            confetti.style.animationDelay = Math.random() * 2 + 's';
            document.body.appendChild(confetti);
        }
    </script>
    <?php endif; ?>
</body>
</html>
