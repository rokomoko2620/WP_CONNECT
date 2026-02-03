<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$currentUser = getCurrentUser();
$planId = (int)($_GET['id'] ?? 0);

if (!$planId) {
    header('Location: plans.php');
    exit;
}

$pdo = getDB();

// 計画を取得
$stmt = $pdo->prepare("
    SELECT tp.*, u.id as owner_id, u.display_name, u.username, u.profile_image,
        (SELECT COUNT(*) FROM likes WHERE plan_id = tp.id) as like_count,
        (SELECT COUNT(*) FROM likes WHERE plan_id = tp.id AND user_id = ?) as user_liked
    FROM travel_plans tp
    JOIN users u ON tp.user_id = u.id
    WHERE tp.id = ?
");
$stmt->execute([$currentUser['id'], $planId]);
$plan = $stmt->fetch();

if (!$plan) {
    header('Location: plans.php');
    exit;
}

// コメントを取得
$stmt = $pdo->prepare("
    SELECT c.*, u.display_name, u.username, u.profile_image
    FROM comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.plan_id = ?
    ORDER BY c.created_at DESC
");
$stmt->execute([$planId]);
$comments = $stmt->fetchAll();

// コメント投稿処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $content = trim($_POST['comment']);
    if ($content) {
        $stmt = $pdo->prepare("INSERT INTO comments (user_id, plan_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$currentUser['id'], $planId, $content]);
        header("Location: plan_detail.php?id=$planId");
        exit;
    }
}

$activities = $plan['activities'] ? explode(',', $plan['activities']) : [];
$isOwner = $plan['owner_id'] === $currentUser['id'];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($plan['destination']) ?> - CONNECT</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .detail-header {
            background: linear-gradient(135deg, var(--primary-500), var(--primary-600));
            color: white;
            padding: var(--space-2xl);
            border-radius: var(--radius-xl);
            margin-bottom: var(--space-xl);
        }
        .detail-user {
            display: flex;
            align-items: center;
            gap: var(--space-md);
            margin-bottom: var(--space-lg);
        }
        .detail-user img, .detail-user .avatar-placeholder {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: 3px solid rgba(255,255,255,0.5);
        }
        .avatar-placeholder {
            background: var(--primary-300);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        .comment-item {
            display: flex;
            gap: var(--space-md);
            padding: var(--space-lg);
            border-bottom: 1px solid var(--gray-100);
        }
        .comment-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            flex-shrink: 0;
            overflow: hidden;
            background: var(--gray-100);
        }
        .comment-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .comment-content {
            flex: 1;
        }
        .comment-meta {
            display: flex;
            gap: var(--space-md);
            font-size: 0.85rem;
            color: var(--gray-500);
            margin-bottom: var(--space-xs);
        }
        .comment-text {
            color: var(--gray-700);
            line-height: 1.6;
        }
        .photo-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.9);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: var(--space-lg);
        }
        .photo-modal.active {
            display: flex;
        }
        .photo-modal img {
            max-width: 100%;
            max-height: 90vh;
            border-radius: var(--radius-lg);
        }
        .photo-modal-close {
            position: absolute;
            top: 20px;
            right: 30px;
            font-size: 2rem;
            color: white;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <?php include 'includes/header.php'; ?>
        
        <main class="main-content">
            <div class="container" style="max-width: 800px;">
                <a href="plans.php" class="btn btn-secondary mb-lg">← 一覧に戻る</a>
                
                <!-- 計画詳細ヘッダー -->
                <div class="detail-header">
                    <div class="detail-user" onclick="showUserProfile(<?= $plan['owner_id'] ?>)" style="cursor: pointer;">
                        <?php if ($plan['profile_image'] !== 'default.png'): ?>
                            <img src="uploads/profiles/<?= htmlspecialchars($plan['profile_image']) ?>" alt="">
                        <?php else: ?>
                            <div class="avatar-placeholder">👤</div>
                        <?php endif; ?>
                        <div>
                            <div style="font-weight: 600;"><?= htmlspecialchars($plan['display_name'] ?: $plan['username']) ?></div>
                            <div style="opacity: 0.8; font-size: 0.9rem;">
                                <?= date('Y年m月d日', strtotime($plan['created_at'])) ?> に投稿
                            </div>
                        </div>
                    </div>
                    
                    <h1 style="font-size: 2rem; margin-bottom: var(--space-md);">📍 <?= htmlspecialchars($plan['destination']) ?></h1>
                    <p style="font-size: 1.1rem; opacity: 0.9;">
                        <?= htmlspecialchars($plan['prefecture']) ?> ・ 
                        <?= date('Y/m/d', strtotime($plan['travel_date_start'])) ?>
                        <?php if ($plan['travel_date_end']): ?>
                            〜 <?= date('Y/m/d', strtotime($plan['travel_date_end'])) ?>
                        <?php endif; ?>
                    </p>
                </div>
                
                <!-- 計画詳細 -->
                <div class="card mb-lg">
                    <?php if ($plan['photo']): ?>
                        <div class="form-group">
                            <label class="form-label">📷 写真</label>
                            <div style="text-align: center;">
                                <img src="uploads/plans/<?= htmlspecialchars($plan['photo']) ?>" alt="旅行の写真" style="max-width: 100%; max-height: 400px; border-radius: var(--radius-lg); cursor: pointer;" onclick="openPhotoModal(this.src)">
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($plan['purpose']): ?>
                        <div class="form-group">
                            <label class="form-label">旅行の目的</label>
                            <p><?= htmlspecialchars($plan['purpose']) ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($activities): ?>
                        <div class="form-group">
                            <label class="form-label">やりたいこと</label>
                            <div style="display: flex; flex-wrap: wrap; gap: var(--space-sm);">
                                <?php foreach ($activities as $act): ?>
                                    <span class="activity-tag"><?= htmlspecialchars($act) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($plan['description']): ?>
                        <div class="form-group">
                            <label class="form-label">詳細</label>
                            <p style="white-space: pre-wrap; line-height: 1.8;"><?= htmlspecialchars($plan['description']) ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <!-- アクション -->
                    <div style="display: flex; gap: var(--space-md); margin-top: var(--space-lg); padding-top: var(--space-lg); border-top: 1px solid var(--gray-200);">
                        <?php if (!$isOwner): ?>
                            <button onclick="toggleLike(<?= $plan['id'] ?>, this)" class="btn <?= $plan['user_liked'] ? 'btn-accent' : 'btn-secondary' ?>">
                                <?= $plan['user_liked'] ? '❤️ いいね済み' : '🤍 いいね' ?>
                                <span id="likeCount">(<?= $plan['like_count'] ?>)</span>
                            </button>
                            <a href="chat.php?user=<?= $plan['owner_id'] ?>" class="btn btn-primary">💬 メッセージを送る</a>
                        <?php else: ?>
                            <span class="btn btn-secondary" style="cursor: default;">❤️ いいね (<?= $plan['like_count'] ?>)</span>
                            <a href="plan_create.php?edit=<?= $plan['id'] ?>" class="btn btn-primary">✏️ 編集する</a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- コメント欄 -->
                <div class="card">
                    <h3 style="margin-bottom: var(--space-lg);">💬 コメント (<?= count($comments) ?>)</h3>
                    
                    <!-- コメント投稿 -->
                    <form method="POST" style="margin-bottom: var(--space-xl);">
                        <div style="display: flex; gap: var(--space-md);">
                            <div class="comment-avatar">
                                <?php if ($currentUser['profile_image'] !== 'default.png'): ?>
                                    <img src="uploads/profiles/<?= htmlspecialchars($currentUser['profile_image']) ?>" alt="">
                                <?php else: ?>
                                    <div style="width: 100%; height: 100%; background: var(--primary-300); display: flex; align-items: center; justify-content: center; color: white;">👤</div>
                                <?php endif; ?>
                            </div>
                            <div style="flex: 1;">
                                <textarea name="comment" class="form-textarea" rows="2" placeholder="コメントを書く..." required></textarea>
                                <button type="submit" class="btn btn-primary btn-sm mt-md">投稿</button>
                            </div>
                        </div>
                    </form>
                    
                    <!-- コメント一覧 -->
                    <?php if ($comments): ?>
                        <?php foreach ($comments as $c): ?>
                            <div class="comment-item">
                                <div class="comment-avatar" onclick="showUserProfile(<?= $c['user_id'] ?>)" style="cursor: pointer;">
                                    <?php if ($c['profile_image'] !== 'default.png'): ?>
                                        <img src="uploads/profiles/<?= htmlspecialchars($c['profile_image']) ?>" alt="">
                                    <?php else: ?>
                                        <div style="width: 100%; height: 100%; background: var(--primary-300); display: flex; align-items: center; justify-content: center; color: white;">👤</div>
                                    <?php endif; ?>
                                </div>
                                <div class="comment-content">
                                    <div class="comment-meta">
                                        <span style="font-weight: 600; color: var(--gray-800);"><?= htmlspecialchars($c['display_name'] ?: $c['username']) ?></span>
                                        <span><?= date('Y/m/d H:i', strtotime($c['created_at'])) ?></span>
                                    </div>
                                    <div class="comment-text"><?= nl2br(htmlspecialchars($c['content'])) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align: center; color: var(--gray-500); padding: var(--space-xl);">まだコメントはありません</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <!-- ユーザープロフィールモーダル -->
    <div class="profile-overlay" id="userProfileOverlay" onclick="closeUserProfile(event)">
        <div class="profile-tab" id="userProfileTab" onclick="event.stopPropagation()">
            <div class="profile-tab-header">
                <button class="profile-tab-close" onclick="document.getElementById('userProfileOverlay').classList.remove('active')">&times;</button>
                <div class="profile-tab-avatar" id="userAvatar"></div>
                <h2 class="profile-tab-name" id="userName"></h2>
            </div>
            <div class="profile-tab-content" id="userProfileContent"></div>
            <div class="profile-tab-actions" id="userProfileActions"></div>
        </div>
    </div>
    
    <!-- フォトモーダル -->
    <div class="photo-modal" id="photoModal" onclick="closePhotoModal()">
        <span class="photo-modal-close">&times;</span>
        <img src="" id="photoModalImg" alt="拡大写真" onclick="event.stopPropagation()">
    </div>
    
    <script>
    function toggleLike(planId, btn) {
        fetch('api/like.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({plan_id: planId})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                btn.className = 'btn ' + (data.liked ? 'btn-accent' : 'btn-secondary');
                btn.innerHTML = (data.liked ? '❤️ いいね済み' : '🤍 いいね') + ' <span id="likeCount">(' + data.count + ')</span>';
            }
        });
    }
    
    function showUserProfile(userId) {
        fetch('api/user_profile.php?id=' + userId)
        .then(r => r.json())
        .then(user => {
            if (user.error) return;
            
            document.getElementById('userAvatar').innerHTML = user.profile_image !== 'default.png' 
                ? '<img src="uploads/profiles/' + user.profile_image + '" alt="">'
                : '<span style="display:flex;align-items:center;justify-content:center;width:100%;height:100%;font-size:3rem;">👤</span>';
            
            document.getElementById('userName').textContent = user.display_name || user.username;
            
            const genderLabels = {male: '男性', female: '女性', other: 'その他', secret: '秘密'};
            document.getElementById('userProfileContent').innerHTML = `
                <div class="profile-info-item"><div class="profile-info-icon">📍</div><div><div class="profile-info-label">出身地</div><div class="profile-info-value">${user.birthplace || '未設定'}</div></div></div>
                <div class="profile-info-item"><div class="profile-info-icon">👤</div><div><div class="profile-info-label">性別</div><div class="profile-info-value">${genderLabels[user.gender] || '未設定'}</div></div></div>
                <div class="profile-info-item"><div class="profile-info-icon">🎂</div><div><div class="profile-info-label">年齢</div><div class="profile-info-value">${user.age ? user.age + '歳' : '未設定'}</div></div></div>
                <div class="profile-info-item"><div class="profile-info-icon">❤️</div><div><div class="profile-info-label">好きなもの</div><div class="profile-info-value">${user.interests || '未設定'}</div></div></div>
                <div class="profile-info-item"><div class="profile-info-icon">💬</div><div><div class="profile-info-label">コメント</div><div class="profile-info-value">${user.comment || '未設定'}</div></div></div>
            `;
            
            document.getElementById('userProfileActions').innerHTML = user.is_self ? '' : 
                `<a href="chat.php?user=${userId}" class="btn btn-primary">💬 メッセージを送る</a>`;
            
            document.getElementById('userProfileOverlay').classList.add('active');
        });
    }
    
    function closeUserProfile(e) {
        if (e.target === document.getElementById('userProfileOverlay')) {
            document.getElementById('userProfileOverlay').classList.remove('active');
        }
    }
    
    function openPhotoModal(src) {
        document.getElementById('photoModalImg').src = src;
        document.getElementById('photoModal').classList.add('active');
    }
    
    function closePhotoModal() {
        document.getElementById('photoModal').classList.remove('active');
    }
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closePhotoModal();
        }
    });
    </script>
</body>
</html>
