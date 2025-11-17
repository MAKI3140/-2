<?php
    require_once('helpers/MemberDAO.php');
    require_once('helpers/HistoryDAO.php');
    
    // セッション開始
    session_start();

    // セッションに会員情報がなければログインページへリダイレクト
    if (empty($_SESSION['member'])) {
        header('Location: login-register.php');
        exit;
    }

    $hotpepper_code = null;
    $reviews = [];
    $review_error = '';

    // store_detail.phpからのPOSTリクエストを期待
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hotpepper_code'])) {
        $hotpepper_code = $_POST['hotpepper_code'];
    } elseif (isset($_GET['id'])) { 
        // 投稿後のリダイレクトなどを考慮し、GETパラメータも受け付ける
        $hotpepper_code = $_GET['id'];
    }

    if ($hotpepper_code) {
        $historyDAO = new HistoryDAO();
        // HistoryDAOからレビュー一覧を取得
        // (time, review, evaluation, member_name が返される)
        $reviews = $historyDAO->get_review($hotpepper_code);
    } else {
        $review_error = '店舗IDが指定されていません。';
    }
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>店舗レビュー</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  </head>
</head>
<body>
    <div class="container">
    <header class="page-header">
        <a href="javascript:history.back()" class="header-back-button">
           <i class="fa-solid fa-chevron-left"></i>
        </a>
            <h1>レビュー</h1>
    </header>

    <main class="review-page">
        <?php if ($review_error): ?>
            <p class="error-message" style="padding: 20px; text-align: center; color: red;"><?= htmlspecialchars($review_error) ?></p>
        <?php elseif (empty($reviews)): ?>
            <p style="padding: 20px; text-align: center;">このお店のレビューはまだありません。</p>
        <?php else: ?>
            <div class="review-list">
                <?php foreach ($reviews as $review): 
                    // 評価点 (1-5)
                    $evaluation = (int)$review['evaluation'];
                    
                    // 来店日 (time) のフォーマット
                    $visit_date = '日付不明';
                    if (!empty($review['time'])) {
                        try {
                            $date_obj = new DateTime($review['time']);
                            $visit_date = $date_obj->format('Y/m/d');
                        } catch (Exception $e) {
                            $visit_date = htmlspecialchars($review['time']);
                        }
                    }
                ?>
                <div class="review-card-item">
                    <div class="review-card-header">
                        <div class="review-user-icon">👤</div>
                        <div class="review-user-info">
                            <span class="review-user-name"><?= htmlspecialchars($review['member_name']) ?></span>
                            <span class="review-user-meta">
                                来店日: <?= $visit_date ?>
                            </span>
                        </div>
                    </div>
                    
                    <?php if ($evaluation > 0): ?>
                        <div class="review-card-stars" style="--rating: <?= $evaluation ?>;"></div>
                    <?php endif; ?>

                    <p class="review-card-source">ホットペッパーグルメで予約</p>
                    
                    <p class="review-card-body">
                        <?= nl2br(htmlspecialchars($review['review'])) ?>
                    </p>

                    <div class="review-card-scene">
                        <i class="fa-solid fa-moon"></i> ディナー｜来店シーン：友人・知人と
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
    <?php include('fixed-footer.php'); ?>

  </body>
</html>