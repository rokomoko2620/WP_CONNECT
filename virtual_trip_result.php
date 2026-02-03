<?php
require_once 'includes/config.php';
require_once 'includes/virtual_trip_data.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$currentUser = getCurrentUser();
$tripId = (int)($_GET['id'] ?? 0);

if (!$tripId) {
    header('Location: chat_list.php');
    exit;
}

$pdo = getDB();

// 仮想旅の情報を取得
$stmt = $pdo->prepare("
    SELECT vt.*, 
           u1.id as inviter_uid, u1.display_name as inviter_name, u1.profile_image as inviter_image,
           u2.id as invitee_uid, u2.display_name as invitee_name, u2.profile_image as invitee_image
    FROM virtual_trips vt
    JOIN users u1 ON vt.inviter_id = u1.id
    JOIN users u2 ON vt.invitee_id = u2.id
    WHERE vt.id = ? AND (vt.inviter_id = ? OR vt.invitee_id = ?)
");
$stmt->execute([$tripId, $currentUser['id'], $currentUser['id']]);
$trip = $stmt->fetch();

if (!$trip) {
    header('Location: chat_list.php');
    exit;
}

$isInviter = ($trip['inviter_id'] === $currentUser['id']);
$partnerId = $isInviter ? $trip['invitee_id'] : $trip['inviter_id'];
$partnerName = $isInviter ? $trip['invitee_name'] : $trip['inviter_name'];

// 両者の回答を取得
$stmt = $pdo->prepare("SELECT user_id, question_id, answer FROM virtual_trip_answers WHERE trip_id = ? ORDER BY question_id");
$stmt->execute([$tripId]);
$allAnswers = $stmt->fetchAll();

$inviterAnswers = [];
$inviteeAnswers = [];
foreach ($allAnswers as $a) {
    if ($a['user_id'] == $trip['inviter_id']) {
        $inviterAnswers[$a['question_id']] = $a['answer'];
    } else {
        $inviteeAnswers[$a['question_id']] = $a['answer'];
    }
}

// 質問を取得
$questions = selectQuestions($virtualTripScenarios, $tripId);

// 完了チェック
$bothCompleted = $trip['inviter_completed'] && $trip['invitee_completed'];

if (!$bothCompleted) {
    // まだ両者完了していない場合
    header("Location: virtual_trip.php?id=$tripId");
    exit;
}

$matchRate = $trip['match_rate'];
$matchComment = getMatchComment($matchRate);

// 一致/不一致のカウント
$matchCount = 0;
$diffCount = 0;
foreach ($questions as $qId => $q) {
    if (isset($inviterAnswers[$qId]) && isset($inviteeAnswers[$qId])) {
        if ($inviterAnswers[$qId] === $inviteeAnswers[$qId]) {
            $matchCount++;
        } else {
            $diffCount++;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>仮想旅の結果 - CONNECT</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .result-container { max-width: 600px; margin: 0 auto; }
        .result-header { text-align: center; padding: var(--space-2xl); background: linear-gradient(135deg, var(--primary-500), var(--primary-700)); color: white; border-radius: var(--radius-xl); margin-bottom: var(--space-xl); position: relative; overflow: hidden; }
        .result-avatars { display: flex; justify-content: center; align-items: center; gap: var(--space-lg); margin: var(--space-lg) 0; }
        .result-avatar { width: 80px; height: 80px; border-radius: 50%; border: 3px solid white; overflow: hidden; background: var(--primary-300); }
        .result-avatar img { width: 100%; height: 100%; object-fit: cover; }
        
        .result-rate { font-size: 4rem; font-weight: 700; margin: var(--space-lg) 0; }
        .result-rate-bar { width: 80%; max-width: 300px; height: 12px; background: rgba(255,255,255,0.3); border-radius: var(--radius-full); margin: 0 auto var(--space-lg); overflow: hidden; }
        .result-rate-fill { height: 100%; background: white; border-radius: var(--radius-full); transition: width 1s ease; }
        .result-comment { background: rgba(255,255,255,0.2); padding: var(--space-md) var(--space-lg); border-radius: var(--radius-lg); display: inline-block; }
        .result-emoji { font-size: 2rem; display: block; margin-bottom: var(--space-sm); }
        
        .result-summary { display: flex; justify-content: center; gap: var(--space-xl); margin: var(--space-xl) 0; }
        .summary-item { text-align: center; }
        .summary-icon { font-size: 1.5rem; margin-bottom: var(--space-xs); }
        .summary-count { font-size: 1.5rem; font-weight: 700; }
        .summary-label { font-size: 0.85rem; color: var(--gray-500); }
        
        .detail-toggle { text-align: center; margin: var(--space-xl) 0; }
        .detail-section { display: none; }
        .detail-section.active { display: block; }
        
        .answer-card { background: white; border-radius: var(--radius-xl); padding: var(--space-lg); margin-bottom: var(--space-md); box-shadow: var(--shadow-sm); }
        .answer-stage { display: inline-flex; align-items: center; gap: var(--space-xs); background: var(--gray-100); color: var(--gray-600); padding: 4px 10px; border-radius: var(--radius-full); font-size: 0.8rem; margin-bottom: var(--space-sm); }
        .answer-question { font-weight: 600; margin-bottom: var(--space-md); color: var(--gray-800); }
        .answer-compare { display: flex; gap: var(--space-md); }
        .answer-user { flex: 1; padding: var(--space-md); border-radius: var(--radius-lg); }
        .answer-user.me { background: var(--primary-50); border: 2px solid var(--primary-200); }
        .answer-user.partner { background: var(--gray-50); border: 2px solid var(--gray-200); }
        .answer-user-name { font-size: 0.8rem; color: var(--gray-500); margin-bottom: var(--space-xs); }
        .answer-user-choice { display: flex; align-items: center; gap: var(--space-sm); }
        .answer-letter { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.9rem; }
        .answer-user.me .answer-letter { background: var(--primary-500); color: white; }
        .answer-user.partner .answer-letter { background: var(--gray-400); color: white; }
        .answer-text { font-size: 0.9rem; }
        
        .match-badge { position: absolute; top: var(--space-sm); right: var(--space-sm); background: var(--success); color: white; padding: 4px 10px; border-radius: var(--radius-full); font-size: 0.8rem; font-weight: 600; }
        .diff-badge { position: absolute; top: var(--space-sm); right: var(--space-sm); background: var(--warning); color: white; padding: 4px 10px; border-radius: var(--radius-full); font-size: 0.8rem; font-weight: 600; }
        
        .confetti { position: absolute; width: 10px; height: 10px; animation: confetti-fall 3s ease-out forwards; pointer-events: none; }
        @keyframes confetti-fall { 0% { transform: translateY(-50px) rotate(0deg); opacity: 1; } 100% { transform: translateY(300px) rotate(720deg); opacity: 0; } }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <?php include 'includes/header.php'; ?>
        
        <main class="main-content">
            <div class="container result-container">
                <a href="chat.php?user=<?= $partnerId ?>" class="btn btn-secondary mb-lg">← チャットに戻る</a>
                
                <div class="result-header" id="resultHeader">
                    <h1>🎮 仮想旅の結果</h1>
                    
                    <div class="result-avatars">
                        <div class="result-avatar">
                            <?php if ($currentUser['profile_image'] !== 'default.png'): ?>
                                <img src="uploads/profiles/<?= htmlspecialchars($currentUser['profile_image']) ?>" alt="">
                            <?php else: ?>
                                <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:2rem;">👤</div>
                            <?php endif; ?>
                        </div>
                        <div style="font-size: 1.5rem;">×</div>
                        <div class="result-avatar">
                            <?php $pi = $isInviter ? $trip['invitee_image'] : $trip['inviter_image']; ?>
                            <?php if ($pi !== 'default.png'): ?>
                                <img src="uploads/profiles/<?= htmlspecialchars($pi) ?>" alt="">
                            <?php else: ?>
                                <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:2rem;">👤</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <p><?= htmlspecialchars($partnerName) ?>さんとの相性は...</p>
                    
                    <div class="result-rate"><?= $matchRate ?>%</div>
                    
                    <div class="result-rate-bar">
                        <div class="result-rate-fill" style="width: <?= $matchRate ?>%;"></div>
                    </div>
                    
                    <div class="result-comment">
                        <span class="result-emoji"><?= $matchComment['emoji'] ?></span>
                        <strong><?= $matchComment['title'] ?></strong><br>
                        <span style="font-size: 0.9rem;"><?= $matchComment['message'] ?></span>
                    </div>
                </div>
                
                <div class="result-summary">
                    <div class="summary-item">
                        <div class="summary-icon">🟢</div>
                        <div class="summary-count"><?= $matchCount ?></div>
                        <div class="summary-label">一致</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-icon">🟡</div>
                        <div class="summary-count"><?= $diffCount ?></div>
                        <div class="summary-label">違い</div>
                    </div>
                </div>
                
                <div class="detail-toggle">
                    <button class="btn btn-primary" onclick="toggleDetail()">📊 詳細を見る</button>
                </div>
                
                <div class="detail-section" id="detailSection">
                    <h3 style="margin-bottom: var(--space-lg);">回答の比較</h3>
                    
                    <?php 
                    $myAnswers = $isInviter ? $inviterAnswers : $inviteeAnswers;
                    $theirAnswers = $isInviter ? $inviteeAnswers : $inviterAnswers;
                    
                    foreach ($questions as $qId => $q): 
                        $myAns = $myAnswers[$qId] ?? '?';
                        $theirAns = $theirAnswers[$qId] ?? '?';
                        $isMatch = ($myAns === $theirAns);
                    ?>
                        <div class="answer-card" style="position: relative;">
                            <?php if ($isMatch): ?>
                                <span class="match-badge">🎯 一致</span>
                            <?php else: ?>
                                <span class="diff-badge">💭 違い</span>
                            <?php endif; ?>
                            
                            <div class="answer-stage"><?= $q['icon'] ?> <?= $q['stage'] ?></div>
                            <div class="answer-question"><?= htmlspecialchars($q['question']) ?></div>
                            
                            <div class="answer-compare">
                                <div class="answer-user me">
                                    <div class="answer-user-name">あなた</div>
                                    <div class="answer-user-choice">
                                        <div class="answer-letter"><?= $myAns ?></div>
                                        <div class="answer-text"><?= htmlspecialchars($q['answers'][$myAns] ?? '未回答') ?></div>
                                    </div>
                                </div>
                                <div class="answer-user partner">
                                    <div class="answer-user-name"><?= htmlspecialchars($partnerName) ?>さん</div>
                                    <div class="answer-user-choice">
                                        <div class="answer-letter"><?= $theirAns ?></div>
                                        <div class="answer-text"><?= htmlspecialchars($q['answers'][$theirAns] ?? '未回答') ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div style="text-align: center; margin-top: var(--space-xl);">
                    <a href="chat.php?user=<?= $partnerId ?>" class="btn btn-accent btn-lg">💬 チャットで話そう</a>
                </div>
            </div>
        </main>
    </div>
    
    <script>
    function toggleDetail() {
        const section = document.getElementById('detailSection');
        section.classList.toggle('active');
    }
    
    // 紙吹雪エフェクト
    <?php if ($matchRate >= 50): ?>
    const colors = ['#FF6B35', '#0967D2', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'];
    const header = document.getElementById('resultHeader');
    for (let i = 0; i < 30; i++) {
        const confetti = document.createElement('div');
        confetti.className = 'confetti';
        confetti.style.left = Math.random() * 100 + '%';
        confetti.style.background = colors[Math.floor(Math.random() * colors.length)];
        confetti.style.animationDelay = Math.random() * 2 + 's';
        header.appendChild(confetti);
    }
    <?php endif; ?>
    </script>
</body>
</html>
