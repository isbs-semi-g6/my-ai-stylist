<?php
/**
 * 設定ファイル
 * 環境変数の読み込みとデータベース接続を提供
 */

/**
 * .envファイルを読み込む
 * @param string $path .envファイルのパス
 */
function loadEnv($path) {
    if (!file_exists($path)) {
        // .envファイルがない場合は警告を表示（開発環境での利便性のため）
        error_log("Warning: .env file not found at {$path}");
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // コメント行をスキップ
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // 空行をスキップ
        if (empty(trim($line))) {
            continue;
        }

        // = で分割
        if (strpos($line, '=') === false) {
            continue;
        }

        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        // 環境変数に設定
        if (!array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// .envファイルを読み込み
loadEnv(__DIR__ . '/../.env');

/**
 * データベース接続を取得する
 * @return resource PostgreSQL接続リソース
 * @throws Exception データベース接続に失敗した場合
 */
function getDbConnection() {
    // 環境変数から取得、なければデフォルト値を使用
    $host = getenv('DB_HOST') ?: 'localhost';
    $dbname = getenv('DB_NAME') ?: 'xxx';
    $user = getenv('DB_USER') ?: 'xxx';
    $password = getenv('DB_PASSWORD') ?: 'xxx';

    $connection_string = "host={$host} dbname={$dbname} user={$user} password={$password}";
    $dbconn = pg_connect($connection_string);

    if (!$dbconn) {
        error_log('Database connection failed: ' . pg_last_error());
        throw new Exception('データベース接続に失敗しました');
    }

    return $dbconn;
}

/**
 * Gemini APIキーを取得する
 * @return string|false APIキー、設定されていない場合はfalse
 */
function getGeminiApiKey() {
    return getenv('GEMINI_API_KEY') ?: false;
}

/**
 * 天気APIキーを取得する
 * @return string|false APIキー、設定されていない場合はfalse
 */
function getWeatherApiKey() {
    return getenv('WEATHER_API_KEY') ?: false;
}
?>
