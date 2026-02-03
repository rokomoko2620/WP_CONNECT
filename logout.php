<?php
require_once 'includes/config.php';

// セッション破棄
session_destroy();

// トップページへリダイレクト
header('Location: index.php');
exit;
?>
