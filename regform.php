<html>
<head>
<title>新規登録</title>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">

</head>
<body>
<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

startSession();

if (isset($_POST['username'])){$username=$_POST['username'];}
if (isset($_POST['email'])){$email=$_POST['email'];}
if (isset($_POST['pwf1'])){$pwf1=$_POST['pwf1'];}
if (isset($_POST['pwf2'])){$pwf2=$_POST['pwf2'];}

if ($pwf1 !== $pwf2){
  echo "<p>パスワードが一致しませんでした。</p>";
  echo "<a href=\"./regform.html\">戻る</p>";
}
elseif (isset($username) && isset($pwf1)){
  // データベース接続
  try {
      $dbconn = getDbConnection();
  } catch (Exception $e) {
      die('データベース接続エラー: ' . $e->getMessage());
  }

  // usernameが既に登録されているか確認するSQL（プリペアドステートメント使用）
  $sql = "SELECT * FROM ai_stylist_users WHERE username = $1";
  $result = pg_query_params($dbconn, $sql, array($username))
      or die('Query failed: ' . pg_last_error());

  // 重複するusernameがない場合
  if(pg_num_rows($result)==0){
    $npw=$pwf1; // パスワードのハッシュ化
    $npwh=password_hash($npw, PASSWORD_BCRYPT);

    // INSERT文もプリペアドステートメント使用
    $sql = "INSERT INTO ai_stylist_users(username, email, password_hash) VALUES ($1, $2, $3)";
    pg_query_params($dbconn, $sql, array($username, $email, $npwh))
        or die('Query failed: ' . pg_last_error());

    echo '<p>ユーザ登録完了!</p>';
    echo "<a href=\"./login.html\">ログイン画面へ</a>";
  }
  else{
    echo "<p>その名前はすでに登録されています。</p>";
    echo "<a href=\"./regform.html\">戻る</a>";
  }
}
else{echo 'error';}
 ?>
</body>
</html>
