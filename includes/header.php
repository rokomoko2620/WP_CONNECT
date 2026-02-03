<?php
require_once __DIR__ . '/config.php';

$currentUser = getCurrentUser();
$unreadCount = $currentUser ? getUnreadCount($currentUser['id']) : ['total' => 0];
?>
<header class="header">
    <div class="container header-inner">
        <a href="index.php" class="logo">
            <span class="logo-icon">✈</span>
            <span>CONNECT</span>
        </a>
        
        <button class="profile-btn" onclick="toggleProfile()">
            <?php if ($currentUser && $currentUser['profile_image'] !== 'default.png'): ?>
                <img src="uploads/profiles/<?= htmlspecialchars($currentUser['profile_image']) ?>" alt="プロフィール">
            <?php else: ?>
                <span class="default-avatar">👤</span>
            <?php endif; ?>
            
            <?php if ($unreadCount['total'] > 0): ?>
                <span class="notification-badge"><?= $unreadCount['total'] > 9 ? '9+' : $unreadCount['total'] ?></span>
            <?php endif; ?>
        </button>
    </div>
</header>

<!-- Profile Tab -->
<div class="profile-overlay" id="profileOverlay" onclick="closeProfileOnOverlay(event)">
    <div class="profile-tab" onclick="event.stopPropagation()">
        <div class="profile-tab-header">
            <button class="profile-tab-close" onclick="toggleProfile()">&times;</button>
            
            <div class="profile-tab-avatar">
                <?php if ($currentUser && $currentUser['profile_image'] !== 'default.png'): ?>
                    <img src="uploads/profiles/<?= htmlspecialchars($currentUser['profile_image']) ?>" alt="プロフィール">
                <?php else: ?>
                    <span style="display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; font-size: 3rem;">👤</span>
                <?php endif; ?>
            </div>
            
            <?php if ($currentUser): ?>
                <h2 class="profile-tab-name"><?= htmlspecialchars($currentUser['display_name'] ?: $currentUser['username']) ?></h2>
            <?php else: ?>
                <h2 class="profile-tab-name">ゲスト</h2>
            <?php endif; ?>
        </div>
        
        <div class="profile-tab-content">
            <?php if ($currentUser): ?>
                <div class="profile-info-item">
                    <div class="profile-info-icon">📍</div>
                    <div>
                        <div class="profile-info-label">出身地</div>
                        <div class="profile-info-value"><?= htmlspecialchars($currentUser['birthplace'] ?: '未設定') ?></div>
                    </div>
                </div>
                
                <div class="profile-info-item">
                    <div class="profile-info-icon">👤</div>
                    <div>
                        <div class="profile-info-label">性別</div>
                        <div class="profile-info-value">
                            <?php
                            $genderLabels = ['male' => '男性', 'female' => '女性'];
                            echo $genderLabels[$currentUser['gender']] ?? '未設定';
                            ?>
                        </div>
                    </div>
                </div>
                
                <div class="profile-info-item">
                    <div class="profile-info-icon">🎂</div>
                    <div>
                        <div class="profile-info-label">年齢</div>
                        <div class="profile-info-value"><?= $currentUser['age'] ? $currentUser['age'] . '歳' : '未設定' ?></div>
                    </div>
                </div>
                
                <div class="profile-info-item">
                    <div class="profile-info-icon">❤️</div>
                    <div>
                        <div class="profile-info-label">好きなもの</div>
                        <div class="profile-info-value"><?= htmlspecialchars($currentUser['interests'] ?: '未設定') ?></div>
                    </div>
                </div>
                
                <div class="profile-info-item">
                    <div class="profile-info-icon">💬</div>
                    <div>
                        <div class="profile-info-label">コメント</div>
                        <div class="profile-info-value"><?= htmlspecialchars($currentUser['comment'] ?: '未設定') ?></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="profile-tab-actions">
            <?php if ($currentUser): ?>
                <a href="profile_edit.php" class="btn btn-secondary">プロフィール編集</a>
                <a href="matching.php" class="btn btn-accent">
                    🔍 マッチングを探す
                </a>
                <a href="chat_list.php" class="btn btn-secondary">
                    💬 チャット
                    <?php if ($unreadCount['messages'] > 0): ?>
                        <span class="notification-badge" style="position: relative; top: 0; right: 0; margin-left: 8px;">
                            <?= $unreadCount['messages'] ?>
                        </span>
                    <?php endif; ?>
                </a>
                <a href="logout.php" class="btn btn-secondary">ログアウト</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary btn-lg">ログイン</a>
                <a href="signup.php" class="btn btn-secondary">新規登録</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleProfile() {
    document.getElementById('profileOverlay').classList.toggle('active');
    document.body.style.overflow = document.getElementById('profileOverlay').classList.contains('active') ? 'hidden' : '';
}

function closeProfileOnOverlay(event) {
    if (event.target === document.getElementById('profileOverlay')) {
        toggleProfile();
    }
}

// ESCキーで閉じる
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('profileOverlay').classList.contains('active')) {
        toggleProfile();
    }
});
</script>
