<html>
<head>
<title>新規登録</title>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">

</head>
<body>
<?php
session_start();

if (isset($_POST['username'])){$username=$_POST['username'];}
if (isset($_POST['email'])){$email=$_POST['email'];}
if (isset($_POST['pwf1'])){$pwf1=$_POST['pwf1'];}
if (isset($_POST['pwf2'])){$pwf2=$_POST['pwf2'];}

if ($pwf1 !== $pwf2){
  echo "<p>パスワードが一致しませんでした。</p>";
  echo "<a href=\"./regform.html\">戻る</p>";
}
elseif (isset($username) && isset($pwf1)){
  // usernameが既に登録されているか確認するSQL
  $sql="select * from ai_stylist_users where username='". $username . "';";
  $dbconn = pg_connect("host=localhost dbname=xxx user=xxx password=xxx")
      or die('Could not connect: ' . pg_last_error());
  $result = pg_query($sql) or die('Query failed: ' . pg_last_error());
  // 重複するusernameがない場合
  if(pg_num_rows($result)==0){
    $npw=$pwf1; // パスワードのハッシュ化
    $npwh=password_hash($npw, PASSWORD_BCRYPT);
    $sql="insert into ai_stylist_users(username, email, password_hash) values ('" .
      $username . "','" . $email . "','" . $npwh . "');";
    pg_query($sql) or die('Query failed: ' . pg_last_error());
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
