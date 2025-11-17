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
      <img src="images/genie.png" alt="魔人" class="genie-image" style="margin-top: 10px; width: 120px;">
      <div class="result-card">
        <img src="images/food_photo.jpg" alt="韓国料理">
        <div class="result-card-info">
          <p class="details">サムギョプサルと韓国料理</p>
          <h3 class="name">gossam 新大久保店</h3>
          <p class="details">⭐ 4.8 (1000 reviews) ⚪︎ 1.2 miles</p>
          <p class="price">¥2,000-3,000 / lunch</p>
        </div>
      </div>
      <div class="result-actions">
        <a href="#" class="btn btn-primary">ここに行く</a>
        <a href="index.html" class="btn btn-secondary">最初からやりなおす</a>
      </div>
    </main>

    <?php include('fixed-footer.php'); ?>
  </div>
</body>
</html>