<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// ログインチェック
requireLogin();

try {
    $dbconn = getDbConnection();

    // ページネーション設定
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $per_page = 10;
    $offset = ($page - 1) * $per_page;

    // 総件数取得
    $sql = "SELECT COUNT(*) FROM ai_stylist_ask_result WHERE user_id = $1";
    $result = pg_query_params($dbconn, $sql, array(getUserId()));
    $total_count = pg_fetch_result($result, 0, 0);
    $total_pages = ceil($total_count / $per_page);

    // 履歴データ取得
    $sql = "SELECT * FROM ai_stylist_ask_result
            WHERE user_id = $1
            ORDER BY created_at DESC
            LIMIT $2 OFFSET $3";
    $result = pg_query_params($dbconn, $sql, array(getUserId(), $per_page, $offset));
    $history = pg_fetch_all($result) ?: array();

    // 各履歴のアイテムを取得
    $outfits = array();
    foreach ($history as $h) {
        $sql = "SELECT ari.*, c.*, i.image_url
                FROM ai_stylist_ask_result_items ari
                LEFT JOIN ai_stylist_clothes c ON ari.clothes_id = c.id
                LEFT JOIN ai_stylist_images i ON c.image_id = i.id
                WHERE ari.ask_result_id = $1
                ORDER BY ari.item_order";
        $items_result = pg_query_params($dbconn, $sql, array($h['id']));
        $items = pg_fetch_all($items_result) ?: array();

        $outfits[] = array(
            'id' => $h['id'],
            'date' => $h['created_at'],
            'weather' => $h['weather'],
            'temp' => $h['temperature'],
            'occasion' => $h['occasion'],
            'ai_response' => $h['ai_response'],
            'items' => $items
        );
    }

    pg_close($dbconn);

} catch (Exception $e) {
    error_log($e->getMessage());
    $outfits = array();
    $total_pages = 0;
    $page = 1;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.5.0/semantic.min.js" integrity="sha512-Xo0Jh8MsOn72LGV8kU5LsclG7SUzJsWGhXbWcYs2MAmChkQzwiW/yTQwdJ8w6UA9C6EVG18GHb/TrYpYCjyAQw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.5.0/semantic.min.css" integrity="sha512-KXol4x3sVoO+8ZsWPFI/r5KBVB/ssCGB5tsv2nVOKwLg33wTFP3fmnXa47FdSVIshVTgsYk/1734xSk9aFIa4A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>服装履歴 - AI Stylist</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/semantic-ui@2.4.2/dist/semantic.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/semantic-ui@2.4.2/dist/semantic.min.js"></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="ui container" style="padding: 2em 0;">
        <h1 class="ui header">
            <i class="history icon"></i>
            <div class="content">
                服装履歴
                <div class="sub header">過去のAIスタイリストによる提案一覧</div>
            </div>
        </h1>

        <div class="ui divider"></div>

        <?php if (empty($outfits)): ?>
            <div class="ui info message">
                <div class="header">
                    まだ服装提案の履歴がありません
                </div>
                <p>AIに相談して、今日の服装を提案してもらいましょう！</p>
                <a href="question.php" class="ui primary button">AIに相談する</a>
            </div>
        <?php else: ?>
            <!-- 履歴リスト -->
            <div id="history-list" class="ui relaxed divided items">
                <!-- JavaScriptでレンダリング -->
            </div>

            <!-- ページネーション -->
            <?php if ($total_pages > 1): ?>
            <div class="ui center aligned container" style="margin-top: 2em;">
                <div class="ui pagination menu">
                    <?php if ($page > 1): ?>
                        <a class="item" href="?page=<?php echo $page - 1; ?>">
                            <i class="angle left icon"></i>
                        </a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a class="<?php echo $i === $page ? 'active ' : ''; ?>item" href="?page=<?php echo $i; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a class="item" href="?page=<?php echo $page + 1; ?>">
                            <i class="angle right icon"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script>
        // PHPから取得したデータをJavaScriptに渡す
        const outfits = <?php echo json_encode($outfits, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

        // レンダリング関数
        function renderOutfits(list) {
            const container = document.getElementById('history-list');
            if (!container) return;

            container.innerHTML = '';

            list.forEach(outfit => {
                const item = document.createElement('div');
                item.className = 'item';

                // 画像（最初のアイテムの画像を使用）
                const imgWrap = document.createElement('div');
                imgWrap.className = 'image';
                const img = document.createElement('img');
                const firstImage = outfit.items.length > 0 && outfit.items[0].image_url
                    ? './uploads/' + outfit.items[0].image_url
                    : 'https://via.placeholder.com/200x200';
                img.src = firstImage;
                img.alt = 'コーディネート画像';
                imgWrap.appendChild(img);
                item.appendChild(imgWrap);

                // コンテンツ
                const content = document.createElement('div');
                content.className = 'content';

                const header = document.createElement('div');
                header.className = 'header';
                header.textContent = `${formatDateJP(outfit.date)}のコーディネート`;
                content.appendChild(header);

                const meta = document.createElement('div');
                meta.className = 'meta';
                meta.innerHTML = `<span>気温: ${outfit.temp}°C</span> <span style="margin-left:1em;">天気: ${outfit.weather}</span> <span style="margin-left:1em;">用途: ${outfit.occasion}</span>`;
                content.appendChild(meta);

                const desc = document.createElement('div');
                desc.className = 'description';

                // AIアドバイス
                const adviceDiv = document.createElement('div');
                adviceDiv.style.marginTop = '1em';
                adviceDiv.innerHTML = '<strong>AIからのアドバイス:</strong><br>' + outfit.ai_response;
                desc.appendChild(adviceDiv);

                // 服の詳細セクション
                if (outfit.items.length > 0) {
                    const detailSegment = document.createElement('div');
                    detailSegment.className = 'ui segment';
                    detailSegment.style.marginTop = '1em';
                    detailSegment.innerHTML = '<h4 class="ui dividing header">提案された服</h4>';

                    const listWrap = document.createElement('div');
                    listWrap.className = 'ui relaxed divided list';

                    outfit.items.forEach(it => {
                        const itItem = document.createElement('div');
                        itItem.className = 'item';

                        const itContent = document.createElement('div');
                        itContent.className = 'content';

                        const itHeader = document.createElement('div');
                        itHeader.className = 'header';
                        itHeader.textContent = `${it.garment_type} — ${it.color}`;
                        itContent.appendChild(itHeader);

                        const itDesc = document.createElement('div');
                        itDesc.className = 'description';
                        itDesc.style.marginTop = '0.5em';

                        if (it.ai_reason) {
                            const reasonP = document.createElement('p');
                            reasonP.textContent = it.ai_reason;
                            itDesc.appendChild(reasonP);
                        }

                        const labels = document.createElement('div');
                        labels.className = 'ui tiny labels';
                        labels.innerHTML =
                            `<a class="ui label">材質: ${it.fabric || '-'}</a>` +
                            `<a class="ui label">模様: ${it.pattern || '-'}</a>` +
                            `<a class="ui label">季節: ${it.season || '-'}</a>`;

                        itDesc.appendChild(labels);
                        itContent.appendChild(itDesc);
                        itItem.appendChild(itContent);
                        listWrap.appendChild(itItem);
                    });

                    detailSegment.appendChild(listWrap);
                    desc.appendChild(detailSegment);
                }

                content.appendChild(desc);
                item.appendChild(content);
                container.appendChild(item);
            });
        }

        function formatDateJP(dateStr) {
            const d = new Date(dateStr);
            if (isNaN(d)) return dateStr;
            return `${d.getFullYear()}年${d.getMonth() + 1}月${d.getDate()}日`;
        }

        // 初期レンダリング
        document.addEventListener('DOMContentLoaded', () => {
            renderOutfits(outfits);
        });
    </script>
</body>
</html>
