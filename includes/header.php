<?php
/**
 * 共通ヘッダーファイル
 * 全ページで使用するナビゲーションを提供
 */

// セッションを開始
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ログイン状態をチェック
$is_logged_in = isset($_SESSION['id']);
$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';
?>
<header style="display: flex; justify-content: center; align-items: center; padding: 16px 32px;">
    <nav style="text-align: center;">
        <a href="index.html" style="margin: 0 16px; text-decoration: none; color: #4f5caa; font-weight: bold; font-size: 20px;">home</a>
        <?php if ($is_logged_in): ?>
            <a href="question.php" style="margin: 0 16px; text-decoration: none; color: #4f5caa; font-weight: bold; font-size: 20px;">chat</a>
            <a href="registar-clothes.php" style="margin: 0 16px; text-decoration: none; color: #4f5caa; font-weight: bold; font-size: 20px;">register</a>
            <a href="outfit-history.php" style="margin: 0 16px; text-decoration: none; color: #4f5caa; font-weight: bold; font-size: 20px;">history</a>
            <a href="logout.php" style="margin: 0 16px; text-decoration: none; color: #4f5caa; font-weight: bold; font-size: 20px;">logout</a>
            <span style="margin-left: 16px; color: #5182b3;"><?php echo htmlspecialchars($username); ?>さん</span>
        <?php else: ?>
            <a href="login.html" style="margin: 0 16px; text-decoration: none; color: #4f5caa; font-weight: bold; font-size: 20px;">login</a>
            <a href="regform.html" style="margin: 0 16px; text-decoration: none; color: #4f5caa; font-weight: bold; font-size: 20px;">register</a>
        <?php endif; ?>
    </nav>
</header>
