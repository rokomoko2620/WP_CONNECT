<?php
require_once 'includes/config.php';
$currentUser = getCurrentUser();
$unreadCount = $currentUser ? getUnreadCount($currentUser['id']) : ['total' => 0, 'likes' => 0, 'messages' => 0];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CONNECT - 旅の出会いを、もっと特別に</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Zen+Maru+Gothic:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        /* コルクボード */
        .cork-board {
            flex: 1;
            background-image: url('images/cork_board_texture.jpg');
            background-size: cover;
            background-position: center;
            padding: 40px 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 140px);
        }
        
        .cork-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 30px;
            max-width: 1200px;
            width: 100%;
        }
        
        /* 写真カード */
        .photo-card {
            position: relative;
            background: #fff;
            padding: 10px 10px 30px 10px;
            box-shadow: 
                0 4px 8px rgba(0,0,0,0.2),
                0 8px 20px rgba(0,0,0,0.15);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            flex-shrink: 0;
        }
        .photo-card:hover {
            box-shadow: 
                0 8px 16px rgba(0,0,0,0.25),
                0 12px 30px rgba(0,0,0,0.2);
        }
        .photo-card-a {
            transform: rotate(-6deg);
        }
        .photo-card-a:hover {
            transform: rotate(-6deg) scale(1.03);
        }
        .photo-card-b {
            transform: rotate(5deg);
        }
        .photo-card-b:hover {
            transform: rotate(5deg) scale(1.03);
        }
        .photo-card img {
            width: 360px;
            height: 240px;
            object-fit: cover;
            display: block;
        }
        
        /* マグネット */
        .magnet {
            position: absolute;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            top: -8px;
            box-shadow: 
                0 2px 4px rgba(0,0,0,0.3),
                inset 0 2px 4px rgba(255,255,255,0.4);
        }
        .magnet-pink {
            background: linear-gradient(145deg, #E991DC, #C471B7);
            left: 15px;
        }
        .magnet-blue {
            background: linear-gradient(145deg, #5BC0EB, #3AA0D0);
            right: 15px;
        }
        
        /* 中央コンテンツ */
        .center-content {
            text-align: center;
            padding: 20px;
        }
        .site-title {
            font-family: 'Zen Maru Gothic', sans-serif;
            font-size: 2.5rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 8px;
            text-shadow: 2px 2px 4px rgba(255,255,255,0.8);
        }
        .site-subtitle {
            font-family: 'Zen Maru Gothic', sans-serif;
            font-size: 1rem;
            color: #555;
            margin-bottom: 40px;
            text-shadow: 1px 1px 2px rgba(255,255,255,0.8);
        }
        
        /* ボタンエリア */
        .action-buttons {
            display: flex;
            gap: 25px;
            justify-content: center;
        }
        .action-btn {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            border: 3px solid #333;
            background: #fff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #333;
            font-family: 'Zen Maru Gothic', sans-serif;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            position: relative;
        }
        .action-btn:hover {
            background: #333;
            color: #fff;
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.25);
        }
        .action-btn span {
            font-size: 0.9rem;
            text-align: center;
            line-height: 1.4;
        }
        .action-btn-badge {
            position: absolute;
            top: 5px;
            right: 5px;
            background: #E53935;
            color: white;
            font-size: 0.7rem;
            font-weight: bold;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* レスポンシブ */
        @media (max-width: 900px) {
            .cork-content {
                flex-direction: column;
                gap: 20px;
            }
            .photo-card {
                transform: rotate(0deg) !important;
            }
            .photo-card:hover {
                transform: scale(1.03) !important;
            }
            .photo-cards-row {
                display: flex;
                gap: 20px;
                justify-content: center;
            }
            .photo-card img {
                width: 180px;
                height: 130px;
            }
            .site-title {
                font-size: 1.8rem;
            }
            .action-buttons {
                gap: 15px;
            }
            .action-btn {
                width: 100px;
                height: 100px;
            }
            .action-btn span {
                font-size: 0.8rem;
            }
        }
        
        @media (max-width: 500px) {
            .photo-card img {
                width: 140px;
                height: 100px;
            }
            .site-title {
                font-size: 1.5rem;
            }
            .site-subtitle {
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <?php include 'includes/header.php'; ?>
        
        <!-- コルクボード -->
        <main class="cork-board">
            <div class="cork-content">
                <!-- 左の写真 -->
                <div class="photo-card photo-card-a">
                    <div class="magnet magnet-pink"></div>
                    <img src="images/picture_imageA.jpg" alt="旅の写真">
                </div>
                
                <!-- 中央のタイトルとボタン -->
                <div class="center-content">
                    <h1 class="site-title">CONNECT</h1>
                    <p class="site-subtitle">〜旅の出会いを、もっと特別に〜</p>
                    
                    <div class="action-buttons">
                        <?php if ($currentUser): ?>
                            <a href="plan_create.php" class="action-btn">
                                <span>旅行計画書<br>を作る</span>
                            </a>
                            <a href="plans.php" class="action-btn">
                                <span>旅行計画書<br>を見る</span>
                                <?php if ($unreadCount['likes'] > 0): ?>
                                    <span class="action-btn-badge"><?= $unreadCount['likes'] > 9 ? '9+' : $unreadCount['likes'] ?></span>
                                <?php endif; ?>
                            </a>
                        <?php else: ?>
                            <a href="login.php" class="action-btn">
                                <span>旅行計画書<br>を作る</span>
                            </a>
                            <a href="login.php" class="action-btn">
                                <span>旅行計画書<br>を見る</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- 右の写真 -->
                <div class="photo-card photo-card-b">
                    <div class="magnet magnet-blue"></div>
                    <img src="images/picture_imageB.jpg" alt="旅の写真">
                </div>
            </div>
        </main>
        
        <footer style="text-align: center; padding: 20px; color: var(--gray-500); background: #fff;">
            <p>&copy; 2024 CONNECT. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>
