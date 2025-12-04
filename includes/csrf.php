<?php
/**
 * CSRF（Cross-Site Request Forgery）対策ファイル
 * CSRFトークンの生成と検証を提供
 */

/**
 * CSRFトークンを生成する
 * セッションにトークンが存在しない場合は新規作成
 * @return string CSRFトークン
 */
function generateCSRFToken() {
    // セッションを開始（まだ開始されていない場合）
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // トークンが存在しない場合は生成
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * CSRFトークンを検証する
 * @param string $token 検証するトークン
 * @return bool トークンが有効な場合true
 */
function validateCSRFToken($token) {
    // セッションを開始
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // セッションにトークンが存在し、送信されたトークンと一致するか確認
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }

    // タイミング攻撃対策のためhash_equals()を使用
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * CSRFトークンのhiddenフィールドを出力する
 * フォームに埋め込むためのHTMLを生成
 * @return string hiddenフィールドのHTML
 */
function csrfTokenField() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * POSTリクエストのCSRFトークンを検証し、無効な場合はエラーを表示して終了
 */
function requireValidCSRFToken() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
            http_response_code(403);
            die('Invalid CSRF token. リクエストが無効です。もう一度お試しください。');
        }
    }
}
?>
