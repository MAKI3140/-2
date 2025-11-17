<?php
    require_once('helpers/MemberDAO.php');
    // セッション開始
    session_start();

    // セッションに会員情報がなければログインページへリダイレクト
    if (empty($_SESSION['member'])) {
        header('Location: login-register.php');
        exit;
    }
?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Food Akinator - Answer</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
  <div class="container">
    <main class="akinator-screen">
      <div class="result-box">
        <p>思い浮かべているのは</p>
        <h3>釣りあじ食堂 新宿店</h3>
        <p class="distance">〇〇〇m</p>
      </div>
      <div class="result-actions">
        <a href="https://maps.app.goo.gl/ioqNbUSczyPfbm6v7" class="btn btn-primary">ここに行く</a>
        <a href="answer2.php" class="btn btn-secondary">ちょっと違うかも</a>
      </div>
    </main>

  <?php include('fixed-footer.php'); ?>
  </div>
</body>
</html>