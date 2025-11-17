<?php
require_once './helpers/MemberDAO.php';


    if(session_status() === PHP_SESSION_NONE){ 
        session_start();
    };
    
    if(!empty($_SESSION['member'])){
        $member = $_SESSION['member'];
    }

?>
<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Akinator - TOP</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  </head>
  <body>
    <div class="container">
      <main class="akinator-screen home-screen">
        
        <img src="images/title-logo.png" alt="logo" class="title-image" height="100px" width="100px">
        <a href="question.php" class="challenge-button">お店をさがす！</a>
        <p class="sub-text">テキストをいれる</p>
      </main>
    </div>

    <?php include('fixed-footer.php'); ?>

  </body>
</html>