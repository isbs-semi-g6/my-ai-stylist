<?php
session_start();

// DB接続
$dbconn = pg_connect("host=localhost dbname=xxx user=xxx password=xxx")
    or die('Could not connect: ' . pg_last_error());

// POSTを取得
if (isset($_POST['username']) && isset($_POST['pwf'])) {
    $username = $_POST['username'];
    $pwf = $_POST['pwf'];

    // ユーザー名でユーザー検索
    $sql = "SELECT * FROM ai_stylist_users WHERE username = $1";
    $result = pg_query_params($dbconn, $sql, array($username));
    $user = pg_fetch_assoc($result);

    // ユーザーが存在し、パスワードが一致する場合
    if ($user && password_verify($pwf, $user['password_hash'])) {
        // セッションにユーザー情報を保存
        $_SESSION['id'] = $user['id'];
        $_SESSION['username'] = $user['username'];

        // ログイン成功時のリダイレクト
        header('Location: registar-clothes.php');
        exit;
    } else {
        // ログイン失敗時
        echo "<p>ユーザー名またはパスワードが違うよ</p>";
        echo "<a href=\"./login.html\">戻る</a>";
        exit;
    }
}

// 服と画像の登録処理
if (isset($_POST['send'])) {

    $image_id = NULL;

    try {
        // トランザクション開始
        pg_query($dbconn, 'BEGIN');

        // 画像アップロード処理
        if (isset($_FILES['image_url']) && $_FILES['image_url']['error'] === UPLOAD_ERR_OK) {
            // ファイルの検証
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($_FILES['image_url']['type'], $allowed_types)) {
                throw new Exception('不正なファイル形式です');
            }
            // ファイル名の生成
            $nfn=time() . "_" . getmypid() . "." . pathinfo($_FILES["image_url"]["name"], PATHINFO_EXTENSION);
            // アップロードファイルを格納するファイルパスを指定,uploads フォルダの場合
            // 同フォルダは 777にすること
            // アップロードディレクトリの設定
            $upload_dir = "./uploads/";
            // ディレクトリ存在確認と作成
            if (!file_exists($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true)) {
                    die('Failed to create uploads directory');
                }
            }

            // 権限設定は削除し、代わりに書き込みチェックのみ行う
            if (!is_writable($upload_dir)) {
                echo "アップロードディレクトリに書き込み権限がありません。サーバ管理者に連絡してください。";
                throw new Exception('Upload directory is not writable');
            }

            $image_url = $upload_dir . $nfn;
            // ファイルのアップロード確認
            if ( $_FILES["image_url"]["size"] === 0 ) {
                echo "ファイルはアップロードされてません! " . "アップロードファイルを指定してください。";
            }else{
                // アップロードファイルされたテンポラリファイルをファイル格納パスにコピーする
                $result = move_uploaded_file($_FILES["image_url"]["tmp_name"], $image_url);
                if($result === true){
                    // RETURNING id：データを挿入し、さらに挿入されたレコードの id を返す
                    $sql = "INSERT INTO ai_stylist_images (user_id, image_url) VALUES ($1, $2) RETURNING id";
                    $params = array($_SESSION['id'], $nfn);
                    $result = pg_query_params($dbconn, $sql, $params);
                    if ($result) {
                        $row = pg_fetch_row($result);   // [新しいレコードのID] を含む配列
                        $image_id = $row[0];      // 新しく作成されたレコードの ID を取得
                    }
                }else{
                    echo "アップロード失敗!";
                }
            }
        }

        // フォームからのデータ取得
        $garment_type = $_POST['garment_type'];
        $color = $_POST['color'];
        $fabric = $_POST['fabric'];
        $pattern = $_POST['pattern'];
        $season = $_POST['season'];
        $neckline = $_POST['neckline'];
        $sleeve_length = $_POST['sleeve_length'];

        // データベースに登録
        // SQLインジェクション対策としてプリペアドステートメントを使用
        $query = "INSERT INTO ai_stylist_clothes (user_id, image_id, garment_type, color, fabric, pattern, season, neckline, sleeve_length)
                VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)";
        
        // パラメータの配列を作成
        $params = array(
            $_SESSION['id'],
            $image_id,  // 画像ID
            $garment_type,
            $color,
            $fabric,
            $pattern,
            $season,
            $neckline,
            $sleeve_length
        );

        // プリペアドステートメントを実行
        $result = pg_query_params($dbconn, $query, $params);

        if ($result) {
            echo "服の登録が完了しました。";
        } else {
            echo "服の登録に失敗しました。";
        }

        // トランザクションのコミット
        pg_query($dbconn, 'COMMIT');

        // フォーム再送を防ぐためにリダイレクト
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;

    } catch (Exception $e) {
        // エラー時はロールバック
        pg_query($dbconn, 'ROLLBACK');
        // header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}
?>


<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ja" lang="ja">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <meta http-equiv="Content-Style-Type" content="text/css">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.5.0/semantic.min.js" integrity="sha512-Xo0Jh8MsOn72LGV8kU5LsclG7SUzJsWGhXbWcYs2MAmChkQzwiW/yTQwdJ8w6UA9C6EVG18GHb/TrYpYCjyAQw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
	    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.5.0/semantic.min.css" integrity="sha512-KXol4x3sVoO+8ZsWPFI/r5KBVB/ssCGB5tsv2nVOKwLg33wTFP3fmnXa47FdSVIshVTgsYk/1734xSk9aFIa4A==" crossorigin="anonymous" referrerpolicy="no-referrer" />

        <title>服登録</title>

    </head>
	<body>
        <div class="ui container" style="padding: 2em;">
            <div class="ui centered grid">
                <div class="eight wide column">
                    <h3 class="ui header">服登録</h3>
                    <p>ようこそ<?php echo $_SESSION['username']; ?>さん</p>
                    <p>手持ちの服を登録しよう</p>
                    <p>以下のフィールドを入力してね</p>

                    <form class="ui form" action="./registar-clothes.php" method="POST" enctype="multipart/form-data">
                        <div class="field">
                            服の種類：
                            <input type="text" name="garment_type" placeholder="例：Tシャツ">
                        </div>
                        <div class="field">
                            画像：
                            <input type="file" name="image_url">
                        </div>
                        <div class="field">
                            色：
                            <input type="text" name="color" placeholder="例：赤">
                        </div>
                        <div class="field">
                            材質：
                            <input type="text" name="fabric" placeholder="例：コットン">
                        </div>
                        <div class="field">
                            模様：
                            <input type="text" name="pattern" placeholder="例：ストライプ">
                        </div>
                        <div class="field">
                            季節：
                            <input type="text" name="season" placeholder="例：春">
                        </div>
                        <div class="field">
                            首元のデザイン：
                            <input type="text" name="neckline" placeholder="例：Vネック">
                        </div>
                        <div class="field">
                            袖丈：
                            <input type="text" name="sleeve_length" placeholder="例：半袖">
                        </div>
                        <button class="ui teal button" name="send" type="submit">登録</button>
                    </form>
                </div>
            </div>

            <!-- 画像一覧の表示 -->
            <div class="ui grid">
                <div class="sixteen wide column">
                    <?php
                    $sql = "SELECT image_url FROM ai_stylist_images WHERE user_id = $1 ORDER BY id DESC";
                    $result = pg_query_params($dbconn, $sql, array($_SESSION['id']));
                    if ($result): ?>
                        <div class="ui images">
                            <?php while ($row = pg_fetch_assoc($result)): ?>
                                <img class="ui medium image" src="./uploads/<?php echo htmlspecialchars($row['image_url']); ?>">
                            <?php endwhile; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </body>
</html>

<?php
pg_close($dbconn);
?>