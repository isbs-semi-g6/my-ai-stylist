<?php
/**
 * 認証・セッション管理ファイル
 * ログイン状態のチェックと管理を提供
 */

/**
 * セッションを開始する（まだ開始されていない場合）
 */
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * ログインが必要なページで使用
 * ログインしていない場合はログインページにリダイレクト
 */
function requireLogin() {
    startSession();

    if (!isset($_SESSION['id'])) {
        header('Location: login.html');
        exit;
    }
}

/**
 * ログイン状態をチェック
 * @return bool ログインしている場合true
 */
function isLoggedIn() {
    startSession();
    return isset($_SESSION['id']);
}

/**
 * ユーザーIDを取得
 * @return int|null ログインしている場合はユーザーID、していない場合はnull
 */
function getUserId() {
    startSession();
    return isset($_SESSION['id']) ? $_SESSION['id'] : null;
}

/**
 * ユーザー名を取得
 * @return string|null ログインしている場合はユーザー名、していない場合はnull
 */
function getUsername() {
    startSession();
    return isset($_SESSION['username']) ? $_SESSION['username'] : null;
}

/**
 * ログイン処理を実行
 * @param int $user_id ユーザーID
 * @param string $username ユーザー名
 */
function login($user_id, $username) {
    startSession();

    // セッション固定攻撃対策
    session_regenerate_id(true);

    $_SESSION['id'] = $user_id;
    $_SESSION['username'] = $username;
    $_SESSION['login_time'] = time();
}

/**
 * ログアウト処理を実行
 */
function logout() {
    startSession();

    // セッション変数をクリア
    $_SESSION = array();

    // セッションクッキーを削除
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 42000, '/');
    }

    // セッションを破棄
    session_destroy();
}

/**
 * セッションタイムアウトをチェック（オプション）
 * @param int $timeout タイムアウト時間（秒）デフォルトは30分
 * @return bool タイムアウトした場合true
 */
function checkSessionTimeout($timeout = 1800) {
    startSession();

    if (isset($_SESSION['login_time'])) {
        if (time() - $_SESSION['login_time'] > $timeout) {
            logout();
            return true;
        }
        // 最終アクセス時刻を更新
        $_SESSION['login_time'] = time();
    }

    return false;
}
?>
