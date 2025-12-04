<?php
/**
 * データベース接続テストページ
 * .envファイルの設定を使用してPostgreSQLデータベースへの接続をテストします
 */

// エラー表示を有効化（デバッグ用）
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 設定ファイルを読み込む
require_once __DIR__ . '/includes/config.php';

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>データベース接続テスト</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            max-width: 700px;
            width: 100%;
            padding: 40px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
            font-size: 28px;
        }
        
        .test-section {
            margin-bottom: 30px;
        }
        
        .test-section h2 {
            color: #555;
            font-size: 20px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        
        .result-box {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
        }
        
        .success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .info {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }
        
        .env-value {
            padding: 10px;
            background-color: #f8f9fa;
            border-left: 3px solid #667eea;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
        }
        
        .label {
            font-weight: bold;
            color: #667eea;
            display: inline-block;
            width: 150px;
        }
        
        .value {
            color: #333;
        }
        
        .icon {
            font-size: 20px;
            margin-right: 10px;
        }
        
        .back-button {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: transform 0.2s;
            text-align: center;
        }
        
        .back-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 データベース接続テスト</h1>
        
        <!-- 環境変数の確認 -->
        <div class="test-section">
            <h2>1. 環境変数の読み込み</h2>
            <?php
            $envFile = __DIR__ . '/.env';
            if (file_exists($envFile)) {
                echo '<div class="result-box success"><span class="icon">✓</span>.envファイルが見つかりました</div>';
                echo '<div class="info" style="padding: 10px; margin-top: 10px; font-size: 13px;">';
                echo '<strong>ファイルパス:</strong> ' . htmlspecialchars($envFile);
                echo '</div>';
            } else {
                echo '<div class="result-box error"><span class="icon">✗</span>.envファイルが見つかりません</div>';
                echo '<div class="warning" style="padding: 10px; margin-top: 10px; font-size: 13px;">';
                echo '<strong>期待されるパス:</strong> ' . htmlspecialchars($envFile);
                echo '</div>';
            }
            ?>
        </div>
        
        <!-- 環境変数の値 -->
        <div class="test-section">
            <h2>2. データベース設定値</h2>
            <div class="env-value">
                <span class="label">DB_HOST:</span>
                <span class="value"><?php echo htmlspecialchars(getenv('DB_HOST') ?: '(未設定 - デフォルト: localhost)'); ?></span>
            </div>
            <div class="env-value">
                <span class="label">DB_NAME:</span>
                <span class="value"><?php echo htmlspecialchars(getenv('DB_NAME') ?: '(未設定)'); ?></span>
            </div>
            <div class="env-value">
                <span class="label">DB_USER:</span>
                <span class="value"><?php echo htmlspecialchars(getenv('DB_USER') ?: '(未設定)'); ?></span>
            </div>
            <div class="env-value">
                <span class="label">DB_PASSWORD:</span>
                <span class="value"><?php echo getenv('DB_PASSWORD') ? '********** (設定済み)' : '(未設定)'; ?></span>
            </div>
        </div>
        
        <!-- PostgreSQL拡張の確認 -->
        <div class="test-section">
            <h2>3. PostgreSQL拡張モジュール</h2>
            <?php
            if (function_exists('pg_connect')) {
                echo '<div class="result-box success"><span class="icon">✓</span>PostgreSQL拡張モジュールが利用可能です</div>';
            } else {
                echo '<div class="result-box error"><span class="icon">✗</span>PostgreSQL拡張モジュールがインストールされていません</div>';
                echo '<div class="warning" style="padding: 10px; margin-top: 10px; font-size: 13px;">';
                echo 'PHPのPostgreSQL拡張をインストールする必要があります。';
                echo '</div>';
            }
            ?>
        </div>
        
        <!-- データベース接続テスト -->
        <div class="test-section">
            <h2>4. データベース接続テスト</h2>
            <?php
            if (function_exists('pg_connect')) {
                try {
                    $dbconn = getDbConnection();
                    
                    if ($dbconn) {
                        echo '<div class="result-box success"><span class="icon">✓</span>データベース接続に成功しました！</div>';
                        
                        // データベース情報を取得
                        $version = pg_version($dbconn);
                        echo '<div class="info" style="padding: 15px; margin-top: 10px;">';
                        echo '<strong>接続情報:</strong><br>';
                        echo 'PostgreSQLバージョン: ' . htmlspecialchars($version['server'] ?? 'unknown') . '<br>';
                        echo 'クライアントバージョン: ' . htmlspecialchars($version['client'] ?? 'unknown') . '<br>';
                        
                        // 現在のデータベース名を取得
                        $result = pg_query($dbconn, "SELECT current_database(), current_user");
                        if ($result) {
                            $row = pg_fetch_assoc($result);
                            echo '現在のデータベース: ' . htmlspecialchars($row['current_database']) . '<br>';
                            echo '現在のユーザー: ' . htmlspecialchars($row['current_user']) . '<br>';
                        }
                        
                        // 簡単なクエリテスト
                        $test_query = pg_query($dbconn, "SELECT 1 as test");
                        if ($test_query) {
                            echo '<br><strong>クエリテスト:</strong> SELECT文の実行に成功しました ✓';
                        }
                        
                        echo '</div>';
                        
                        // 接続を閉じる
                        pg_close($dbconn);
                    } else {
                        echo '<div class="result-box error"><span class="icon">✗</span>データベース接続に失敗しました</div>';
                    }
                } catch (Exception $e) {
                    echo '<div class="result-box error"><span class="icon">✗</span>エラー: ' . htmlspecialchars($e->getMessage()) . '</div>';
                    
                    // より詳細なエラー情報
                    $lastError = pg_last_error();
                    if ($lastError) {
                        echo '<div class="warning" style="padding: 10px; margin-top: 10px; font-size: 13px;">';
                        echo '<strong>詳細エラー:</strong><br>' . htmlspecialchars($lastError);
                        echo '</div>';
                    }
                }
            } else {
                echo '<div class="result-box error"><span class="icon">✗</span>PostgreSQL拡張がないため、接続テストをスキップしました</div>';
            }
            ?>
        </div>
        
        <!-- その他の環境変数 -->
        <div class="test-section">
            <h2>5. その他のAPI設定</h2>
            <div class="env-value">
                <span class="label">GEMINI_API_KEY:</span>
                <span class="value"><?php echo getGeminiApiKey() ? '********** (設定済み)' : '(未設定)'; ?></span>
            </div>
            <div class="env-value">
                <span class="label">WEATHER_API_KEY:</span>
                <span class="value"><?php echo getWeatherApiKey() ? '********** (設定済み)' : '(未設定)'; ?></span>
            </div>
        </div>
        
        <div style="text-align: center;">
            <a href="index.html" class="back-button">← ホームに戻る</a>
        </div>
    </div>
</body>
</html>
