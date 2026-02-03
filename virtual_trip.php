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
           u1.display_name as inviter_name, u1.profile_image as inviter_image,
           u2.display_name as invitee_name, u2.profile_image as invitee_image
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
$partnerImage = $isInviter ? $trip['invitee_image'] : $trip['inviter_image'];

// 既に回答済みかチェック
$stmt = $pdo->prepare("SELECT question_id, answer FROM virtual_trip_answers WHERE trip_id = ? AND user_id = ?");
$stmt->execute([$tripId, $currentUser['id']]);
$myAnswers = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$myCompleted = $isInviter ? $trip['inviter_completed'] : $trip['invitee_completed'];
$partnerCompleted = $isInviter ? $trip['invitee_completed'] : $trip['inviter_completed'];

// 質問を取得（trip_idをシードにして同じ質問セットを生成）
$questions = selectQuestions($virtualTripScenarios, $tripId);

// 回答送信処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['answers'])) {
    $answers = $_POST['answers'];
    
    foreach ($answers as $qId => $answer) {
        if (in_array($answer, ['A', 'B', 'C'])) {
            $stmt = $pdo->prepare("
                INSERT INTO virtual_trip_answers (trip_id, user_id, question_id, answer) 
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE answer = ?
            ");
            $stmt->execute([$tripId, $currentUser['id'], $qId, $answer, $answer]);
        }
    }
    
    // 完了フラグを更新
    $completedField = $isInviter ? 'inviter_completed' : 'invitee_completed';
    $pdo->prepare("UPDATE virtual_trips SET $completedField = 1 WHERE id = ?")->execute([$tripId]);
    
    // 両者完了したか確認
    $stmt = $pdo->prepare("SELECT inviter_completed, invitee_completed FROM virtual_trips WHERE id = ?");
    $stmt->execute([$tripId]);
    $tripStatus = $stmt->fetch();
    
    if ($tripStatus['inviter_completed'] && $tripStatus['invitee_completed']) {
        // 両者の回答を取得して一致度を計算
        $stmt = $pdo->prepare("SELECT user_id, question_id, answer FROM virtual_trip_answers WHERE trip_id = ?");
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
        
        $matchRate = calculateMatchRate($inviterAnswers, $inviteeAnswers);
        
        $pdo->prepare("UPDATE virtual_trips SET status = 'completed', match_rate = ?, completed_at = NOW() WHERE id = ?")
            ->execute([$matchRate, $tripId]);
    }
    
    header("Location: virtual_trip_result.php?id=$tripId");
    exit;
}

// 招待を承諾する処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accept'])) {
    $pdo->prepare("UPDATE virtual_trips SET status = 'accepted' WHERE id = ?")->execute([$tripId]);
    $trip['status'] = 'accepted';
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>仮想旅 - CONNECT</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .vt-container { max-width: 600px; margin: 0 auto; }
        .vt-header { text-align: center; padding: var(--space-xl); background: linear-gradient(135deg, var(--primary-500), var(--primary-700)); color: white; border-radius: var(--radius-xl); margin-bottom: var(--space-xl); }
        .vt-avatars { display: flex; justify-content: center; align-items: center; gap: var(--space-lg); margin: var(--space-lg) 0; }
        .vt-avatar { width: 70px; height: 70px; border-radius: 50%; border: 3px solid white; overflow: hidden; background: var(--primary-300); }
        .vt-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .vt-connector { font-size: 1.5rem; }
        .vt-status { background: rgba(255,255,255,0.2); padding: var(--space-sm) var(--space-lg); border-radius: var(--radius-full); display: inline-block; margin-top: var(--space-md); }
        
        .vt-pending { text-align: center; padding: var(--space-2xl); }
        .vt-pending-icon { font-size: 4rem; margin-bottom: var(--space-lg); }
        
        .vt-question-card { background: white; border-radius: var(--radius-xl); padding: var(--space-xl); margin-bottom: var(--space-lg); box-shadow: var(--shadow-md); }
        .vt-stage { display: inline-flex; align-items: center; gap: var(--space-xs); background: var(--primary-100); color: var(--primary-700); padding: 4px 12px; border-radius: var(--radius-full); font-size: 0.85rem; margin-bottom: var(--space-md); }
        .vt-question { font-size: 1.1rem; font-weight: 600; margin-bottom: var(--space-lg); color: var(--gray-800); }
        .vt-answers { display: flex; flex-direction: column; gap: var(--space-sm); }
        .vt-answer { display: flex; align-items: center; gap: var(--space-md); padding: var(--space-md); border: 2px solid var(--gray-200); border-radius: var(--radius-lg); cursor: pointer; transition: all var(--transition-fast); }
        .vt-answer:hover { border-color: var(--primary-300); background: var(--primary-50); }
        .vt-answer.selected { border-color: var(--primary-500); background: var(--primary-100); }
        .vt-answer input { display: none; }
        .vt-answer-letter { width: 32px; height: 32px; border-radius: 50%; background: var(--gray-200); display: flex; align-items: center; justify-content: center; font-weight: 600; color: var(--gray-600); }
        .vt-answer.selected .vt-answer-letter { background: var(--primary-500); color: white; }
        .vt-answer-text { flex: 1; }
        
        .vt-progress { display: flex; justify-content: center; gap: 6px; margin-bottom: var(--space-xl); }
        .vt-progress-dot { width: 10px; height: 10px; border-radius: 50%; background: var(--gray-300); }
        .vt-progress-dot.answered { background: var(--primary-500); }
        .vt-progress-dot.current { background: var(--accent-500); transform: scale(1.3); }
        
        .vt-nav { display: flex; justify-content: space-between; margin-top: var(--space-xl); }
        .vt-submit { text-align: center; margin-top: var(--space-xl); }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <?php include 'includes/header.php'; ?>
        
        <main class="main-content">
            <div class="container vt-container">
                <a href="chat.php?user=<?= $partnerId ?>" class="btn btn-secondary mb-lg">← チャットに戻る</a>
                
                <div class="vt-header">
                    <h1>🎮 仮想旅</h1>
                    <div class="vt-avatars">
                        <div class="vt-avatar">
                            <?php if ($currentUser['profile_image'] !== 'default.png'): ?>
                                <img src="uploads/profiles/<?= htmlspecialchars($currentUser['profile_image']) ?>" alt="">
                            <?php else: ?>
                                <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:2rem;">👤</div>
                            <?php endif; ?>
                        </div>
                        <div class="vt-connector">🤝</div>
                        <div class="vt-avatar">
                            <?php if ($partnerImage !== 'default.png'): ?>
                                <img src="uploads/profiles/<?= htmlspecialchars($partnerImage) ?>" alt="">
                            <?php else: ?>
                                <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:2rem;">👤</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <p><?= htmlspecialchars($partnerName) ?>さんとの仮想旅</p>
                    
                    <?php if ($trip['status'] === 'completed'): ?>
                        <div class="vt-status">✅ 完了</div>
                    <?php elseif ($myCompleted): ?>
                        <div class="vt-status">⏳ 相手の回答待ち</div>
                    <?php elseif ($trip['status'] === 'accepted'): ?>
                        <div class="vt-status">🎯 回答中</div>
                    <?php else: ?>
                        <div class="vt-status">📩 招待中</div>
                    <?php endif; ?>
                </div>
                
                <?php if ($trip['status'] === 'completed'): ?>
                    <!-- 完了済み：結果ページへ -->
                    <div class="card" style="text-align: center; padding: var(--space-2xl);">
                        <p style="font-size: 3rem; margin-bottom: var(--space-md);">🎉</p>
                        <h2>仮想旅が完了しました！</h2>
                        <p style="color: var(--gray-500); margin: var(--space-lg) 0;">結果を確認しましょう</p>
                        <a href="virtual_trip_result.php?id=<?= $tripId ?>" class="btn btn-primary btn-lg">結果を見る</a>
                    </div>
                    
                <?php elseif ($myCompleted): ?>
                    <!-- 自分は完了、相手待ち -->
                    <div class="card vt-pending">
                        <div class="vt-pending-icon">⏳</div>
                        <h2><?= htmlspecialchars($partnerName) ?>さんの回答を待っています</h2>
                        <p style="color: var(--gray-500); margin-top: var(--space-md);">
                            相手が回答を完了すると、結果が表示されます
                        </p>
                    </div>
                    
                <?php elseif ($trip['status'] === 'pending' && !$isInviter): ?>
                    <!-- 招待された側で未承諾 -->
                    <div class="card vt-pending">
                        <div class="vt-pending-icon">🎮</div>
                        <h2>仮想旅に招待されました！</h2>
                        <p style="color: var(--gray-500); margin: var(--space-lg) 0;">
                            <?= htmlspecialchars($partnerName) ?>さんと一緒に仮想旅を体験しませんか？<br>
                            10問の質問に答えて、旅の相性をチェック！
                        </p>
                        <form method="POST">
                            <button type="submit" name="accept" value="1" class="btn btn-primary btn-lg">参加する</button>
                        </form>
                    </div>
                    
                <?php elseif ($trip['status'] === 'pending' && $isInviter): ?>
                    <!-- 招待した側で相手未承諾 -->
                    <div class="card vt-pending">
                        <div class="vt-pending-icon">📩</div>
                        <h2>招待を送信しました</h2>
                        <p style="color: var(--gray-500); margin-top: var(--space-md);">
                            <?= htmlspecialchars($partnerName) ?>さんが参加するのを待っています
                        </p>
                    </div>
                    
                <?php else: ?>
                    <!-- 回答フォーム -->
                    <form method="POST" id="questionForm">
                        <div class="vt-progress">
                            <?php $qNum = 0; foreach ($questions as $qId => $q): $qNum++; ?>
                                <div class="vt-progress-dot <?= isset($myAnswers[$qId]) ? 'answered' : '' ?>" data-q="<?= $qNum ?>"></div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php $qNum = 0; foreach ($questions as $qId => $q): $qNum++; ?>
                            <div class="vt-question-card" data-question="<?= $qNum ?>" style="<?= $qNum > 1 ? 'display:none;' : '' ?>">
                                <div class="vt-stage"><?= $q['icon'] ?> <?= $q['stage'] ?></div>
                                <div style="color: var(--gray-500); font-size: 0.9rem; margin-bottom: var(--space-sm);">Q<?= $qNum ?> / 10</div>
                                <div class="vt-question"><?= htmlspecialchars($q['question']) ?></div>
                                <div class="vt-answers">
                                    <?php foreach ($q['answers'] as $letter => $text): ?>
                                        <label class="vt-answer <?= (isset($myAnswers[$qId]) && $myAnswers[$qId] === $letter) ? 'selected' : '' ?>">
                                            <input type="radio" name="answers[<?= $qId ?>]" value="<?= $letter ?>" <?= (isset($myAnswers[$qId]) && $myAnswers[$qId] === $letter) ? 'checked' : '' ?> required>
                                            <div class="vt-answer-letter"><?= $letter ?></div>
                                            <div class="vt-answer-text"><?= htmlspecialchars($text) ?></div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="vt-nav">
                            <button type="button" id="prevBtn" class="btn btn-secondary" style="display:none;">← 前へ</button>
                            <button type="button" id="nextBtn" class="btn btn-primary">次へ →</button>
                        </div>
                        
                        <div class="vt-submit" style="display:none;" id="submitArea">
                            <button type="submit" class="btn btn-accent btn-lg">回答を送信する</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script>
    let currentQ = 1;
    const totalQ = 10;
    
    function showQuestion(n) {
        document.querySelectorAll('.vt-question-card').forEach((card, i) => {
            card.style.display = (i + 1 === n) ? 'block' : 'none';
        });
        
        document.querySelectorAll('.vt-progress-dot').forEach((dot, i) => {
            dot.classList.remove('current');
            if (i + 1 === n) dot.classList.add('current');
        });
        
        document.getElementById('prevBtn').style.display = (n > 1) ? 'inline-flex' : 'none';
        document.getElementById('nextBtn').style.display = (n < totalQ) ? 'inline-flex' : 'none';
        document.getElementById('submitArea').style.display = (n === totalQ) ? 'block' : 'none';
    }
    
    document.getElementById('nextBtn')?.addEventListener('click', function() {
        const currentCard = document.querySelector(`.vt-question-card[data-question="${currentQ}"]`);
        const selected = currentCard.querySelector('input:checked');
        if (!selected) {
            alert('回答を選択してください');
            return;
        }
        currentCard.querySelector('.vt-progress-dot')?.classList.add('answered');
        document.querySelectorAll('.vt-progress-dot')[currentQ - 1]?.classList.add('answered');
        currentQ++;
        showQuestion(currentQ);
    });
    
    document.getElementById('prevBtn')?.addEventListener('click', function() {
        currentQ--;
        showQuestion(currentQ);
    });
    
    document.querySelectorAll('.vt-answer').forEach(answer => {
        answer.addEventListener('click', function() {
            this.closest('.vt-answers').querySelectorAll('.vt-answer').forEach(a => a.classList.remove('selected'));
            this.classList.add('selected');
        });
    });
    </script>
</body>
</html>
