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
  <title>Food Akinator - Question</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
  <div class="container">
    <main class="akinator-screen">
      
      <div class="question-box">
        <h2>質問その1</h2>
        <p>何人？</p>
      </div>
      <div class="answer-options">
        <a href="answer1.php" class="btn">1人</a>
        <a href="answer1.php" class="btn">2人-4人</a>
        <a href="answer1.php" class="btn">5-6人</a>
        <a href="answer1.php" class="btn">それ以上</a>
      </div>
    </main>

    <?php include('fixed-footer.php'); ?>
</body>
</html>