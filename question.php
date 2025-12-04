<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/csrf.php';

// ログインチェック
requireLogin();

$error_message = '';
$processing = false;

// POSTリクエストの処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRFトークン検証
    requireValidCSRFToken();

    $processing = true;

    try {
        // フォームデータ取得
        $gender = $_POST['gender'] ?? '';
        $age = $_POST['age'] ?? '';
        $purpose = $_POST['purpose'] ?? '';
        $style = $_POST['style'] ?? '';
        $additional = $_POST['additional'] ?? '';
        $latitude = $_POST['latitude'] ?? null;
        $longitude = $_POST['longitude'] ?? null;

        // 必須項目チェック
        if (empty($gender) || empty($age) || empty($purpose) || empty($style)) {
            throw new Exception('すべての必須項目を入力してください');
        }

        // 天気情報を取得
        $weather_data = getWeatherData($latitude, $longitude);

        // データベース接続
        $dbconn = getDbConnection();

        // ユーザーの服データを取得
        $clothes_data = getUserClothes(getUserId(), $dbconn);

        if (empty($clothes_data)) {
            throw new Exception('登録されている服がありません。まず服を登録してください。');
        }

        // プロンプトを生成
        $prompt = generatePrompt([
            'gender' => $gender,
            'age' => $age,
            'purpose' => $purpose,
            'style' => $style,
            'additional' => $additional
        ], $weather_data, $clothes_data);

        // Gemini APIを呼び出し
        $ai_response = callGeminiAPI($prompt);

        // 結果をデータベースに保存
        $result_id = saveResult(getUserId(), [
            'gender' => $gender,
            'age' => $age,
            'purpose' => $purpose,
            'style' => $style,
            'additional' => $additional
        ], $weather_data, $ai_response, $clothes_data, $dbconn);

        pg_close($dbconn);

        // 結果ページにリダイレクト
        header('Location: result.php?id=' . $result_id);
        exit;

    } catch (Exception $e) {
        error_log('Question.php error: ' . $e->getMessage());
        $error_message = $e->getMessage();
        $processing = false;
    }
}

/**
 * 天気情報を取得
 */
function getWeatherData($latitude, $longitude) {
    $api_key = getWeatherApiKey();

    if (!$api_key) {
        // APIキーが設定されていない場合はダミーデータを返す
        return [
            'weather' => '晴れ',
            'temperature' => 20
        ];
    }

    if (empty($latitude) || empty($longitude)) {
        // 位置情報がない場合は東京の情報を取得
        $latitude = 35.6762;
        $longitude = 139.6503;
    }

    $url = "https://api.openweathermap.org/data/2.5/weather?lat={$latitude}&lon={$longitude}&appid={$api_key}&units=metric&lang=ja";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200 || !$response) {
        // エラー時はダミーデータ
        return [
            'weather' => '晴れ',
            'temperature' => 20
        ];
    }

    $data = json_decode($response, true);

    return [
        'weather' => $data['weather'][0]['description'] ?? '晴れ',
        'temperature' => round($data['main']['temp'] ?? 20, 1)
    ];
}

/**
 * ユーザーの服データを取得
 */
function getUserClothes($user_id, $dbconn) {
    $sql = "SELECT c.*, i.image_url
            FROM clothes c
            LEFT JOIN images i ON c.image_id = i.id
            WHERE c.user_id = $1
            ORDER BY c.created_at DESC";
    $result = pg_query_params($dbconn, $sql, array($user_id));

    $clothes = array();
    while ($row = pg_fetch_assoc($result)) {
        $clothes[] = $row;
    }

    return $clothes;
}

/**
 * プロンプトを生成
 */
function generatePrompt($user_input, $weather_data, $clothes_data) {
    $clothes_list = "";
    foreach ($clothes_data as $cloth) {
        $pattern = $cloth['pattern'] ?: '無地';
        $season = $cloth['season'] ?: '通年';
        $clothes_list .= "- {$cloth['garment_type']}: {$cloth['color']}, {$cloth['fabric']}, {$pattern}, {$season}\n";
    }

    $additional_text = !empty($user_input['additional']) ? "\n- その他要望: {$user_input['additional']}" : '';

    $prompt = "あなたはプロのファッションスタイリストです。以下の情報を元に、今日のおすすめコーディネートを提案してください。

## ユーザー情報
- 性別: {$user_input['gender']}
- 年齢: {$user_input['age']}歳
- 目的: {$user_input['purpose']}
- 好みのスタイル: {$user_input['style']}{$additional_text}

## 天気情報
- 天気: {$weather_data['weather']}
- 気温: {$weather_data['temperature']}°C

## 所有している服
{$clothes_list}

上記の服の中から、今日の天気と目的に最適なコーディネートを3〜5アイテム提案してください。
提案は以下のJSON形式で返してください（JSON以外の文字は含めないでください）：

{
  \"advice\": \"全体的なコーディネートアドバイス（2〜3文）\",
  \"items\": [
    {
      \"garment_type\": \"トップス\",
      \"color\": \"白\",
      \"reason\": \"この服を選んだ理由\"
    }
  ]
}";

    return $prompt;
}

/**
 * Gemini APIを呼び出し
 */
function callGeminiAPI($prompt) {
    $api_key = getGeminiApiKey();

    if (!$api_key) {
        // APIキーが設定されていない場合はダミーデータを返す
        return [
            'advice' => '天気に合わせて軽めのコーディネートをおすすめします。清潔感のある組み合わせで、目的に合った装いになります。',
            'items' => []
        ];
    }

    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key={$api_key}";

    $data = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.7,
            'maxOutputTokens' => 1024
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200 || !$response) {
        throw new Exception('AI APIの呼び出しに失敗しました');
    }

    $response_data = json_decode($response, true);

    if (!isset($response_data['candidates'][0]['content']['parts'][0]['text'])) {
        throw new Exception('AIからの応答を解析できませんでした');
    }

    $ai_text = $response_data['candidates'][0]['content']['parts'][0]['text'];

    // JSONを抽出（マークダウンコードブロックを除去）
    $ai_text = preg_replace('/```json\s*/', '', $ai_text);
    $ai_text = preg_replace('/```\s*/', '', $ai_text);
    $ai_text = trim($ai_text);

    $ai_json = json_decode($ai_text, true);

    if (!$ai_json || !isset($ai_json['advice']) || !isset($ai_json['items'])) {
        throw new Exception('AIの応答形式が不正です');
    }

    return $ai_json;
}

/**
 * 結果をデータベースに保存
 */
function saveResult($user_id, $user_input, $weather_data, $ai_response, $clothes_data, $dbconn) {
    pg_query($dbconn, 'BEGIN');

    try {
        // ask_resultに保存
        $sql = "INSERT INTO ask_result
                (user_id, prompt, weather, temperature, occasion, ai_response)
                VALUES ($1, $2, $3, $4, $5, $6)
                RETURNING id";
        $params = [
            $user_id,
            $user_input['purpose'] . ' / ' . $user_input['style'],
            $weather_data['weather'],
            $weather_data['temperature'],
            $user_input['purpose'],
            $ai_response['advice']
        ];
        $result = pg_query_params($dbconn, $sql, $params);
        $row = pg_fetch_row($result);
        $ask_result_id = $row[0];

        // ask_result_itemsに各服を保存
        $item_order = 1;
        foreach ($ai_response['items'] as $item) {
            // clothes_idを検索（完全一致するものを探す）
            $sql = "SELECT id FROM clothes
                    WHERE user_id = $1
                    AND garment_type = $2
                    AND color = $3
                    LIMIT 1";
            $result = pg_query_params($dbconn, $sql, [
                $user_id,
                $item['garment_type'],
                $item['color']
            ]);

            $clothes_id = null;
            if ($clothes_row = pg_fetch_assoc($result)) {
                $clothes_id = $clothes_row['id'];
            }

            // clothes_idが見つからない場合は、部分一致で検索
            if (!$clothes_id) {
                foreach ($clothes_data as $cloth) {
                    if (mb_strpos($cloth['garment_type'], $item['garment_type']) !== false &&
                        mb_strpos($cloth['color'], $item['color']) !== false) {
                        $clothes_id = $cloth['id'];
                        break;
                    }
                }
            }

            // それでも見つからない場合はスキップ
            if (!$clothes_id) {
                continue;
            }

            $sql = "INSERT INTO ask_result_items
                    (ask_result_id, clothes_id, item_order, item_type, ai_reason)
                    VALUES ($1, $2, $3, $4, $5)";
            pg_query_params($dbconn, $sql, [
                $ask_result_id,
                $clothes_id,
                $item_order,
                $item['garment_type'],
                $item['reason']
            ]);

            $item_order++;
        }

        pg_query($dbconn, 'COMMIT');
        return $ask_result_id;

    } catch (Exception $e) {
        pg_query($dbconn, 'ROLLBACK');
        throw $e;
    }
}

// ビュー部分が以下に続きます
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Stylist - 今日の服装相談</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/semantic-ui@2.4.2/dist/semantic.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/semantic-ui@2.4.2/dist/semantic.min.js"></script>
    <style>
        .main-container {
            padding: 2em;
            max-width: 800px;
            margin: 0 auto;
        }
        .question-section {
            margin: 2em 0;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="ui container main-container">
        <h1 class="ui header">
            <i class="tshirt icon"></i>
            <div class="content">
                AI Stylist
                <div class="sub header">今日の服装をAIがアドバイスします</div>
            </div>
        </h1>

        <?php if ($error_message): ?>
        <div class="ui error message">
            <div class="header">エラーが発生しました</div>
            <p><?php echo htmlspecialchars($error_message); ?></p>
        </div>
        <?php endif; ?>

        <div class="question-section">
            <form class="ui form<?php echo $processing ? ' loading' : ''; ?>" method="POST" action="question.php">
                <?php echo csrfTokenField(); ?>

                <input type="hidden" id="latitude" name="latitude">
                <input type="hidden" id="longitude" name="longitude">

                <div class="field required">
                    <label>性別</label>
                    <select class="ui dropdown" id="gender" name="gender" required>
                        <option value="">選択してください</option>
                        <option value="男性">男性</option>
                        <option value="女性">女性</option>
                        <option value="その他">その他</option>
                    </select>
                </div>

                <div class="field required">
                    <label>年齢</label>
                    <input type="number" id="age" name="age" placeholder="年齢を入力してください" min="0" max="120" required>
                </div>

                <div class="field required">
                    <label>場所・目的</label>
                    <input type="text" id="purpose" name="purpose" placeholder="例: オフィス、デート、買い物など" required>
                </div>

                <div class="field required">
                    <label>好みのスタイル</label>
                    <select class="ui dropdown" id="style" name="style" required>
                        <option value="">選択してください</option>
                        <option value="カジュアル">カジュアル</option>
                        <option value="フォーマル">フォーマル</option>
                        <option value="スポーティー">スポーティー</option>
                        <option value="エレガント">エレガント</option>
                    </select>
                </div>

                <div class="field">
                    <label>その他の要望</label>
                    <textarea id="additional" name="additional" rows="3" placeholder="その他の要望があればご記入ください"></textarea>
                </div>

                <button class="ui primary button" type="submit">AIに相談する</button>
                <a href="registar-clothes.php" class="ui button">服を追加登録する</a>
            </form>
        </div>
    </div>

    <script>
        $('.ui.dropdown').dropdown();

        // 位置情報を取得
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    document.getElementById('latitude').value = position.coords.latitude;
                    document.getElementById('longitude').value = position.coords.longitude;
                },
                function(error) {
                    console.log('位置情報の取得に失敗しました:', error);
                }
            );
        }
    </script>
</body>
</html>
