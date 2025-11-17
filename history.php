<?php
// 大量データ処理のためのPHP設定変更 💡 追加
ini_set('max_execution_time', 120); // 実行時間の上限を120秒に延長
ini_set('memory_limit', '256M');    // メモリ上限を256MBに延長
ini_set('default_socket_timeout', 30); // ネットワーク通信のタイムアウトを30秒に設定

  require_once('helpers/MemberDAO.php');
  require_once('helpers/HistoryDAO.php');

  // セッション開始
  session_start();

  // セッションに会員情報がなければログインページへリダイレクト
  if (empty($_SESSION['member'])) {
      header('Location: login-register.php');
      exit;
  }
  $member = $_SESSION['member'];
  $HistoryDAO = new HistoryDAO();
 
  // DAOからhotpepper_code, time, is_favoriteを取得
  $raw_history_data = $HistoryDAO->get_history_details($member->member_id);

  //hotpepper
  $api_key = '8b7a467ccf017947'; // 💡 取得したAPIキーに置き換えてください
  $base_url = 'http://webservice.recruit.co.jp/hotpepper/gourmet/v1/';

  // ----------------------------------------------------
  // 📌 履歴の店舗情報取得処理 (hotpepper_codeによるAPIバッチ検索) 💡 大幅修正箇所
  // ----------------------------------------------------
  $combined_history = [];
  $api_batch_size = 100; // Hot Pepper APIのID検索の最大件数

    
  if (!empty($raw_history_data)) {
    
      // 1. hotpepper_codeのユニークなリストを作成
      $hotpepper_codes_list = array_column($raw_history_data, 'hotpepper_code');
      $unique_hotpepper_codes = array_unique($hotpepper_codes_list); 

      // hotpepper_codeが空でない場合のみAPIを叩く
      if (!empty($unique_hotpepper_codes)) {

          // 2. IDリストを100件ごとに分割 (バッチ処理) 💡 導入
          $id_chunks = array_chunk($unique_hotpepper_codes, $api_batch_size);
          $shops_map = []; // 全バッチで取得した店舗情報を保持するマップ

          foreach ($id_chunks as $chunk) {
              $id_string = implode(',', $chunk);
              
              // APIパラメータの設定（ID検索用）
              $params = [
                  'key' => $api_key,
                  'format' => 'json',
                  'id' => $id_string, 
                  'count' => $api_batch_size, // 取得件数もバッチサイズに合わせる
              ];
        
              $query_string = http_build_query($params);
              $request_url = $base_url . '?' . $query_string;
              $response = @file_get_contents($request_url);
        
              if ($response === FALSE) {
                  error_log("History API Batch Request Error");
              } else {
                  $data = json_decode($response, true);
                  
                  if (!isset($data['results']['error'][0]['message'])) {
                      $history_shops_info = $data['results']['shop'] ?? [];
                      
                      // 取得した店舗情報をマップに格納
                      foreach ($history_shops_info as $shop) {
                          $shops_map[$shop['id']] = $shop;
                      }
                  } else {
                    error_log("History API Batch Error: " . $data['results']['error'][0]['message']);
                  }
              }
          }
          
          // 3. 履歴データ（訪問日時順）とAPIデータを結合
          foreach ($raw_history_data as $history_item) {
              $code = $history_item['hotpepper_code'];
              
              if (isset($shops_map[$code])) {
                  $shop_info = $shops_map[$code];
                  $combined_history[] = [ // 💡 $combined_history に格納
                      'hotpepper_code' => $code,
                      'visit_time' => $history_item['time'], // DAOから取得した訪問日時
                      'is_favorite' => $history_item['is_favorite'], // DAOから取得したお気に入りフラグ
                      'shop_name' => $shop_info['name'] ?? '店舗名情報なし',
                      'access' => $shop_info['access'] ?? '最寄り駅情報なし',
                      'image_url' => $shop_info['photo']['pc']['l'] ?? ($shop_info['photo']['mobile']['l'] ?? 'images/no_image.jpg'), 
                  ];
              }
          }
      }
  }


  // 💡 ページネーション処理と変数 ($paginated_history, $total_pages, $total_items) は全て削除
  $total_items = count($combined_history); // 全件数を取得
  $paginated_history = $combined_history; // 全件をそのまま表示リストに格納


?>


<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>マイ履歴</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  </head>
  <body>
    <div class="container">
      <header class="page-header">
        <h1>マイ履歴</h1>
      </header>

      <main class="history-page">
        <h2 class="section-title">過去の履歴 (<?= $total_items ?>件)</h2>

        <div class="history-list">
          <?php if (empty($paginated_history) && $total_items === 0): ?>
            <p>過去の履歴はありません。</p>
          <?php else: ?>
            <?php foreach ($paginated_history as $history_item): // 💡 全件表示
                $is_favorited_str = $history_item['is_favorite'] === '1' ? 'true' : 'false';
                $star_icon = $is_favorited_str === 'true' ? 'fa-solid fa-star' : 'fa-regular fa-star';
                // 💡 遷移先のURLを生成
                $detail_url = 'store_detail.php?id=' . urlencode($history_item['hotpepper_code']);
            ?>
              
                <div class="history-card" data-store-id="<?= htmlspecialchars($history_item['hotpepper_code']) ?>">
                  <div class="card-main-content">
                    <a href="<?= htmlspecialchars($detail_url) ?>" class="history-card-link"><img src="<?= htmlspecialchars($history_item['image_url']) ?>" alt="店舗画像" class="card-image"></a>
                    <div class="card-details">
                      <div class="card-header">
                        <span class="status-tag visited">来店済み</span>
                      </div>
                      <a href="<?= htmlspecialchars($detail_url) ?>" class="history-card-link"><h3 class="card-title"><?= htmlspecialchars($history_item['shop_name']) ?></h3></a>
                      <p class="card-access"><?= htmlspecialchars($history_item['access']) ?></p>
                      <p class="card-datetime"><?= htmlspecialchars($history_item['visit_time']) ?></p>
                    </div>
                  </div>
                  <div class="favorite-star" data-favorited="<?= $is_favorited_str ?>">
                    <i class="<?= $star_icon ?>"></i>
                  </div>
                </div>
              
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <?php // ページネーションのHTMLブロックは削除 ?>

      </main>
    </div>

    <?php include('fixed-footer.php'); ?>

    <script>
      document.addEventListener('DOMContentLoaded', () => {
        const favoriteStars = document.querySelectorAll('.favorite-star');

        favoriteStars.forEach(star => {
          star.addEventListener('click', (event) => {
            event.stopPropagation(); 
            
            const card = star.closest('.history-card');
            const isFavorited = star.dataset.favorited === 'true';
            const storeId = card.dataset.storeId; 

            const newFavoriteStatus = !isFavorited;
            const newFavoriteStatusStr = newFavoriteStatus ? 'true' : 'false';

            // 1. UIを先に更新（即時フィードバック）
            if (newFavoriteStatus) {
              star.innerHTML = '<i class="fa-solid fa-star"></i>';
            } else {
              star.innerHTML = '<i class="fa-regular fa-star"></i>';
            }
            star.dataset.favorited = newFavoriteStatusStr;
            
            // 2. データベース更新リクエスト（AJAX）
            const formData = new URLSearchParams();
            formData.append('code', storeId);
            formData.append('favorite', newFavoriteStatusStr);

            fetch('update_favorite.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Server response not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    console.log(`店舗ID: ${storeId} のお気に入りをDBに反映しました。新しい状態: ${newFavoriteStatusStr}`);
                } else {
                    console.error('DB更新失敗:', data.message);
                    alert('お気に入りの状態を更新できませんでした。');
                    // UIを元に戻す
                    star.dataset.favorited = isFavorited ? 'true' : 'false';
                    star.innerHTML = isFavorited ? '<i class="fa-solid fa-star"></i>' : '<i class="fa-regular fa-star"></i>';
                }
            })
            .catch(error => {
                console.error('通信エラー:', error);
                alert('通信エラーが発生しました。お気に入りの状態を更新できませんでした。');
                // UIを元に戻す
                star.dataset.favorited = isFavorited ? 'true' : 'false';
                star.innerHTML = isFavorited ? '<i class="fa-solid fa-star"></i>' : '<i class="fa-regular fa-star"></i>';
            });
          });
        });
      });
    </script>
    
  </body>
</html>