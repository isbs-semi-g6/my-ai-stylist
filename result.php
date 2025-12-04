<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// ログインチェック
requireLogin();

// IDパラメータのチェック
if (!isset($_GET['id'])) {
    header('Location: question.php');
    exit;
}

$ask_result_id = (int)$_GET['id'];

try {
    $dbconn = getDbConnection();

    // 提案結果を取得（ユーザー本人のもののみ）
    $sql = "SELECT * FROM ai_stylist_ask_result WHERE id = $1 AND user_id = $2";
    $result = pg_query_params($dbconn, $sql, array($ask_result_id, getUserId()));
    $ask_result = pg_fetch_assoc($result);

    if (!$ask_result) {
        throw new Exception('指定された提案が見つかりません');
    }

    // 提案された服を取得
    $sql = "SELECT ari.*, c.*, i.image_url
            FROM ai_stylist_ask_result_items ari
            LEFT JOIN ai_stylist_clothes c ON ari.clothes_id = c.id
            LEFT JOIN ai_stylist_images i ON c.image_id = i.id
            WHERE ari.ask_result_id = $1
            ORDER BY ari.item_order";
    $result = pg_query_params($dbconn, $sql, array($ask_result_id));
    $items = pg_fetch_all($result) ?: array();

    pg_close($dbconn);

} catch (Exception $e) {
    error_log('Result.php error: ' . $e->getMessage());
    header('Location: question.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>今日のコーディネート - AI Stylist</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/semantic-ui@2.4.2/dist/semantic.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/semantic-ui@2.4.2/dist/semantic.min.js"></script>
    <style>
        .main-container {
            padding: 2em;
            max-width: 1000px;
            margin: 0 auto;
        }
        .outfit-card {
            margin-bottom: 2em;
        }
        .outfit-image {
            max-width: 300px;
            max-height: 300px;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="ui container main-container">
        <h1 class="ui header">
            <i class="check circle icon"></i>
            <div class="content">
                今日のおすすめコーディネート
                <div class="sub header">AIがあなたにぴったりの服装を提案しました</div>
            </div>
        </h1>

        <div class="ui success message">
            <div class="header">
                提案が完了しました！
            </div>
            <p>以下のコーディネートで素敵な一日をお過ごしください。</p>
        </div>

        <!-- 天気情報 -->
        <div class="ui segment">
            <h3 class="ui header">
                <i class="cloud sun icon"></i>
                <div class="content">
                    天気情報
                </div>
            </h3>
            <div class="ui list">
                <div class="item">
                    <i class="thermometer half icon"></i>
                    <div class="content">
                        <div class="header">気温</div>
                        <div class="description"><?php echo htmlspecialchars($ask_result['temperature']); ?>°C</div>
                    </div>
                </div>
                <div class="item">
                    <i class="cloud icon"></i>
                    <div class="content">
                        <div class="header">天気</div>
                        <div class="description"><?php echo htmlspecialchars($ask_result['weather']); ?></div>
                    </div>
                </div>
                <div class="item">
                    <i class="map marker alternate icon"></i>
                    <div class="content">
                        <div class="header">目的・用途</div>
                        <div class="description"><?php echo htmlspecialchars($ask_result['occasion']); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- AIからのアドバイス -->
        <div class="ui segment">
            <h3 class="ui header">
                <i class="lightbulb icon"></i>
                <div class="content">
                    AIからのアドバイス
                </div>
            </h3>
            <p><?php echo nl2br(htmlspecialchars($ask_result['ai_response'])); ?></p>
        </div>

        <!-- おすすめの服 -->
        <div class="ui segment">
            <h3 class="ui header">
                <i class="shopping bag icon"></i>
                <div class="content">
                    おすすめの服
                </div>
            </h3>

            <?php if (empty($items)): ?>
                <div class="ui info message">
                    <p>提案された服の詳細情報がありません。</p>
                </div>
            <?php else: ?>
                <div class="ui cards">
                    <?php foreach ($items as $item): ?>
                    <div class="card outfit-card">
                        <?php if (!empty($item['image_url'])): ?>
                        <div class="image">
                            <img src="./uploads/<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['garment_type']); ?>" class="outfit-image">
                        </div>
                        <?php endif; ?>
                        <div class="content">
                            <div class="header"><?php echo htmlspecialchars($item['garment_type']); ?></div>
                            <div class="meta">
                                <span class="color"><?php echo htmlspecialchars($item['color']); ?></span>
                            </div>
                            <div class="description">
                                <?php if (!empty($item['ai_reason'])): ?>
                                <p><strong>選んだ理由:</strong><br><?php echo nl2br(htmlspecialchars($item['ai_reason'])); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="extra content">
                            <div class="ui small labels">
                                <?php if (!empty($item['fabric'])): ?>
                                <div class="ui label">
                                    <i class="tag icon"></i> 材質: <?php echo htmlspecialchars($item['fabric']); ?>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($item['pattern'])): ?>
                                <div class="ui label">
                                    <i class="paint brush icon"></i> 模様: <?php echo htmlspecialchars($item['pattern']); ?>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($item['season'])): ?>
                                <div class="ui label">
                                    <i class="calendar icon"></i> 季節: <?php echo htmlspecialchars($item['season']); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- アクションボタン -->
        <div class="ui center aligned basic segment">
            <a href="question.php" class="ui primary button">
                <i class="redo icon"></i>
                もう一度AIに相談する
            </a>
            <a href="outfit-history.php" class="ui button">
                <i class="history icon"></i>
                過去の提案を見る
            </a>
            <a href="registar-clothes.php" class="ui button">
                <i class="plus icon"></i>
                服を追加登録する
            </a>
        </div>
    </div>
</body>
</html>
