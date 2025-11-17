<?php
    require_once('helpers/MemberDAO.php');
    require_once('helpers/HistoryDAO.php'); // ★ HistoryDAOを読み込み
    // セッション開始
    session_start();

    // セッションに会員情報がなければログインページへリダイレクト
    if (empty($_SESSION['member'])) {
        header('Location: login-register.php');
        exit;
    }

    $store_info = null;
    $detail_error = null;

    // ★ レビュー集計用の変数を初期化
    $reviews_summary = [
        'total_count' => 0,
        'average_score' => 0,
        'counts' => [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0],
        'percentages' => [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0],
    ];

    // 1. URLパラメータから店舗IDを取得
    if (isset($_GET['id']) && !empty($_GET['id'])) {
        $hotpepper_code = $_GET['id'];
        
        // 2. ホットペッパーグルメAPIの情報を設定
        $api_key = '8b7a467ccf017947'; // 💡 取得したAPIキーに置き換えてください
        $base_url = 'http://webservice.recruit.co.jp/hotpepper/gourmet/v1/';

        $params = [
            'key' => $api_key,
            'format' => 'json',
            'id' => $hotpepper_code, // 取得した店舗IDを使用
        ];

        // APIリクエスト
        $query_string = http_build_query($params);
        $request_url = $base_url . '?' . $query_string;
        $response = @file_get_contents($request_url);

        if ($response === FALSE) {
            $detail_error = "店舗詳細情報の取得中にAPIリクエストエラーが発生しました。";
        } else {
            $data = json_decode($response, true);
            
            if (isset($data['results']['error'][0]['message'])) {
                $detail_error = "APIエラー: " . $data['results']['error'][0]['message'];
            } else {
                // 3. データが存在すれば $store_info に格納
                if (isset($data['results']['shop'][0])) {
                    $store_info = $data['results']['shop'][0];

                    // ★ここからレビュー集計処理を追加
                    $historyDAO = new HistoryDAO();
                    // HistoryDAOからhotpepper_codeに紐づくレビューを取得
                    $reviews = $historyDAO->get_review($hotpepper_code);
                    
                    if (!empty($reviews)) {
                        $reviews_summary['total_count'] = count($reviews);
                        $total_score = 0;
                        
                        // 各レビューの評価点(evaluation)を集計
                        foreach ($reviews as $review) {
                            $score = (int)$review['evaluation'];
                            if ($score >= 1 && $score <= 5) {
                                $reviews_summary['counts'][$score]++;
                                $total_score += $score;
                            }
                        }
                        
                        // 平均点と割合を計算
                        if ($reviews_summary['total_count'] > 0) {
                            $reviews_summary['average_score'] = round($total_score / $reviews_summary['total_count'], 1);
                            
                            foreach ($reviews_summary['counts'] as $score => $count) {
                                $reviews_summary['percentages'][$score] = round(($count / $reviews_summary['total_count']) * 100);
                            }
                        }
                    }
                    // ★レビュー集計処理ここまで

                } else {
                    $detail_error = "指定されたID ({$hotpepper_code}) の店舗情報が見つかりませんでした。";
                }
            }
        }
    } else {
        $detail_error = "店舗IDが指定されていません。";
    }
?>
<!DOCTYPE html>
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>店舗情報</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
  </head>
  <body>
    <div class="container">
      
    <header class="page-header">
        <a href="javascript:history.back()" class="header-back-button">
           <i class="fa-solid fa-chevron-left"></i>
        </a>
            <h1>お店情報</h1>
    </header>

      <main class="store-detail-page">
        <?php if ($detail_error): ?>
          <p class="error-message" style="padding: 20px; text-align: center; color: red;"><?= htmlspecialchars($detail_error) ?></p>
        <?php elseif ($store_info): ?>
        
        <div class="detail-section">

          <div class="detail-item">
            <span class="item-label">店名</span>
            <div class="item-content">
              <span class="content-text" style="font-weight: bold; font-size: 15px;"><?= htmlspecialchars($store_info['name'] ?? 'N/A') ?></span>
            </div>
          </div>

          <div class="detail-item">
            <span class="item-label">店舗画像</span>
            <div class="item-content">
             <?php $image_url = $store_info['photo']['pc']['l'] ?? ($store_info['photo']['mobile']['l'] ?? 'images/no_image.jpg'); ?>
             <img src="<?= htmlspecialchars($image_url) ?>" alt="店舗の画像" class="detail-store-image">
             </div>
          </div>


          <div class="detail-item">
            <span class="item-label">住所</span>
            <div class="item-content">
              <span class="content-text"><?= htmlspecialchars($store_info['address'] ?? 'N/A') ?></span>
              <?php if (!empty($store_info['lat']) && !empty($store_info['lng'])): ?>
                <a href="https://maps.google.com/maps?q=<?= htmlspecialchars($store_info['lat']) ?>,<?= htmlspecialchars($store_info['lng']) ?>" target="_blank" class="map-icon"><i class="fa-solid fa-map-location-dot"></i></a>
              <?php endif; ?>
            </div>
          </div>

          <div class="detail-item">
            <span class="item-label">アクセス</span>
            <div class="item-content">
              <span class="content-text"><?= htmlspecialchars($store_info['access'] ?? 'N/A') ?></span>
            </div>
          </div>

          <div class="detail-item">
            <span class="item-label">電話番号</span>
            <div class="item-content">
              <?php $tel_num = $store_info['tel'] ?? ''; ?>
              <?php if ($tel_num): ?>
                <a href="tel:<?= htmlspecialchars($tel_num) ?>"><?= htmlspecialchars($tel_num) ?></a>
              <?php else: ?>
                <span>N/A</span>
              <?php endif; ?>
            </div>
          </div>

          <div class="detail-item">
            <span class="item-label">営業時間</span>
            <div class="item-content">
              <div class="multi-line">
                <?= nl2br(htmlspecialchars($store_info['open'] ?? 'N/A')) ?>
                <span class="notice">営業時間については、各店にお問い合わせ頂くか公式ホームページにてご確認下さい。</span>
              </div>
            </div>
          </div>

          
          <div class="detail-item review-summary-container">
            <span class="item-label">レビュー</span>
            <div class="item-content">
                
                <?php if ($reviews_summary['total_count'] > 0): // レビューが1件以上ある場合 ?>
                    <div class="review-summary-box">
                        <div class="review-summary-left">
                            <div class="review-score-avg"><?= htmlspecialchars($reviews_summary['average_score']) ?></div>
                            <div class="review-stars-avg" style="--rating: <?= htmlspecialchars($reviews_summary['average_score']) ?>;"></div>
                            <div class="review-total-count"><?= htmlspecialchars($reviews_summary['total_count']) ?>件の総合評価</div>
                        </div>
                        <div class="review-summary-right">
                            <?php for ($i = 5; $i >= 1; $i--): // 星5から星1までループ ?>
                                <div class="review-bar-row">
                                    <span class="review-bar-label">★<?= $i ?></span>
                                    <div class="review-bar-bg">
                                        <div class="review-bar-fg" style="width: <?= $reviews_summary['percentages'][$i] ?>%;"></div>
                                    </div>
                                    <span class="review-bar-percent"><?= $reviews_summary['percentages'][$i] ?>%</span>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                <?php else: // レビューがまだない場合 ?>
                    <p class="review-no-data">このお店のレビューはまだありません。</p>
                <?php endif; ?>

                <div class="review-action-links">
                  <form action="review.php" method="post" style="margin: 0;">
                      <input type="hidden" name="hotpepper_code" value="<?= htmlspecialchars($hotpepper_code) ?>">
                      <button type="submit" class="review-link-button">
                        <i class="fa-solid fa-list-ul"></i> レビュー一覧を見る
                      </button>
                  </form>
                  
                  <a href="review_post.php?id=<?= htmlspecialchars($hotpepper_code) ?>" class="review-link-button review-post-button">
                    <i class="fa-solid fa-pen-to-square"></i> レビューを投稿する
                  </a>
                </div>
            </div>
          </div>
          </div>
        
        <?php endif; ?>
      </main>
    </div>

    <?php include('fixed-footer.php'); ?>
    
  </body>
</html>