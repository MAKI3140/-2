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
  $member = $_SESSION['member'];
  $HistoryDAO = new HistoryDAO();
 
  // DAOからhotpepper_code, time, is_favoriteを取得
  $raw_history_data = $HistoryDAO->get_history_details($member->member_id);

  // hotpepper_code, time, is_favorite を保持し、かつ is_favorite が '1' であるアイテムのみを抽出
  $favorite_history_data = array_filter($raw_history_data, function($item) {
      return $item['is_favorite'] === '1';
  });

  // Hot Pepper Gourmet API情報
  $api_key = '8b7a467ccf017947'; // 💡 取得したAPIキーに置き換えてください
  $base_url = 'http://webservice.recruit.co.jp/hotpepper/gourmet/v1/';

  // ----------------------------------------------------
  // 📌 お気に入りの店舗情報取得処理 (hotpepper_codeによる検索)
  // ----------------------------------------------------
  $all_favorites = []; // 全てのお気に入り店舗を保持
  $combined_favorites = []; // 検索後の表示用リスト

  if (!empty($favorite_history_data)) {
      // 1. hotpepper_codeのリストを作成
      $hotpepper_codes_list = array_column($favorite_history_data, 'hotpepper_code');
      $unique_hotpepper_codes = array_unique($hotpepper_codes_list);
      
      // hotpepper_codeが空でない場合のみAPIを叩く
      if (!empty($unique_hotpepper_codes)) {
          // ... (APIリクエストロジックは変更なし) ...
          // APIパラメータの設定（ID検索用）
          $params = [
              'key' => $api_key,
              'format' => 'json',
              'id' => implode(',', $unique_hotpepper_codes), // 複数のIDをカンマ区切りで指定
              'count' => 100,
          ];
    
          $query_string = http_build_query($params);
          $request_url = $base_url . '?' . $query_string;
          $response = @file_get_contents($request_url);
    
          if ($response !== FALSE) {
              $data = json_decode($response, true);
              
              if (!isset($data['results']['error'][0]['message'])) {
                  $favorite_shops_info = $data['results']['shop'] ?? [];
    
                  $shops_map = [];
                  foreach ($favorite_shops_info as $shop) {
                      $shops_map[$shop['id']] = $shop;
                  }
    
                  // 3. 履歴データとAPIデータを結合し、全お気に入りリストを作成
                  foreach ($unique_hotpepper_codes as $code) {
                      if (isset($shops_map[$code])) {
                          $shop_info = $shops_map[$code];
                          $all_favorites[] = [ // 💡 $all_favorites に格納
                              'hotpepper_code' => $code,
                              'shop_name' => $shop_info['name'] ?? '店舗名情報なし',
                              'genre' => $shop_info['genre']['name'] ?? 'カテゴリ情報なし',
                              'sub_area' => $shop_info['sub_area']['name'] ?? 'エリア情報なし',
                              'budget_name' => $shop_info['budget']['name'] ?? '予算情報なし',
                              'access' => $shop_info['access'] ?? '最寄り駅情報なし',
                              'image_url' => $shop_info['photo']['pc']['l'] ?? ($shop_info['photo']['mobile']['l'] ?? 'images/no_image.jpg'), 
                          ];
                      }
                  }
              }
          }
      }
  }

  // ----------------------------------------------------
  // 📌 検索処理 (お気に入りリストに対する絞り込み)
  // ----------------------------------------------------
  $search_name = '';

  // GETリクエストで検索キーワードが送信されたかチェック
  if (isset($_GET['shop_name']) && !empty($_GET['shop_name'])) {
      $search_name = trim($_GET['shop_name']);
      $search_term_lower = mb_strtolower($search_name, 'UTF-8');
      
      // $all_favorites（全お気に入りリスト）から、店舗名にキーワードを含むものをフィルタリング
      $combined_favorites = array_filter($all_favorites, function($item) use ($search_term_lower) {
          $shop_name_lower = mb_strtolower($item['shop_name'], 'UTF-8');
          return mb_strpos($shop_name_lower, $search_term_lower, 0, 'UTF-8') !== false;
      });

  } else {
      // 検索キーワードがない場合は、全てのお気に入り店舗を表示
      $combined_favorites = $all_favorites;
  }
  
  $favorite_count = count($combined_favorites); // 表示件数


  

?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>お気に入り</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
  <div class="container">
    <header class="page-header">
      <h1>お気に入り</h1>
    </header>

    <main class="favorites-page">
      <div class="search-bar-container">
        <form method="GET" action="favorites.php" class="search-form">
          <div class="search-bar">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" name="shop_name" placeholder="お気に入りからお店を探す" value="<?= htmlspecialchars($search_name) ?>">
          </div>
          <button type="submit" style="display: none;"></button>
        </form>
        
        <button class="filter-button" disabled>
          <i class="fa-solid fa-sliders"></i>
          <span>絞込み</span>
        </button>
      </div>

      <p class="item-count"><?= $favorite_count ?>件</p>

      <div class="favorites-list">
        <?php if (empty($combined_favorites) && !empty($search_name)): ?>
            <p>お気に入りの中で、店舗名「<?= htmlspecialchars($search_name) ?>」に一致するお店は見つかりませんでした。</p>
        <?php elseif (empty($combined_favorites)): ?>
            <p>お気に入りのお店はありません。</p>
        <?php else: ?>
            <?php foreach ($combined_favorites as $favorite_item): 
                // ... (店舗カードのHTMLは変更なし) ...
                $detail_url = 'store_detail.php?id=' . urlencode($favorite_item['hotpepper_code']);
            ?>
                <div class="favorite-card" data-store-id="<?= htmlspecialchars($favorite_item['hotpepper_code']) ?>">
                    <a href="<?= htmlspecialchars($detail_url) ?>" class="card-link-wrapper">
                      <img src="<?= htmlspecialchars($favorite_item['image_url']) ?>" alt="店舗画像" class="fav-card-image">
                    </a>
                    <div class="fav-card-details">
                        <p class="fav-card-category"><?= htmlspecialchars($favorite_item['genre']) ?> </p>
                        <a href="<?= htmlspecialchars($detail_url) ?>" class="card-link-wrapper"><h3 class="fav-card-title"><?= htmlspecialchars($favorite_item['shop_name']) ?></h3></a>
                        <div class="fav-card-info">
                            <span class="info-item"><i class="fa-solid fa-money-bill-wave"></i> <?= htmlspecialchars($favorite_item['budget_name']) ?></span>
                            <span class="info-item"><i class="fa-solid fa-train"></i> <?= htmlspecialchars($favorite_item['access']) ?></span>
                        </div>
                    </div>
                    <div class="favorite-star" data-favorited="true"> 
                        <i class="fa-solid fa-star"></i>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

      </div>
    </main>
  </div>

  <?php include('fixed-footer.php'); ?>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const favoriteStars = document.querySelectorAll('.favorite-star');
      const itemCountElement = document.querySelector('.item-count');
      const searchInput = document.querySelector('.search-bar input[name="shop_name"]');
      const searchForm = document.querySelector('.search-form');

      // 💡 Enterキーで検索をトリガー
      searchInput.addEventListener('keydown', (event) => {
          if (event.key === 'Enter') {
              event.preventDefault(); // デフォルトの送信を防止
              searchForm.submit(); // フォームを送信
          }
      });

      favoriteStars.forEach(star => {
        star.addEventListener('click', (event) => {
          event.stopPropagation();
          
          const isFavorited = star.dataset.favorited === 'true';
          const card = star.closest('.favorite-card');
          const storeId = card.dataset.storeId;

          if (isFavorited) {
            if (confirm('このお店をお気に入りから削除しますか？')) {
              
              star.innerHTML = '<i class="fa-regular fa-star"></i>'; 
              star.dataset.favorited = 'false';
              
              const formData = new URLSearchParams();
              formData.append('code', storeId);
              formData.append('favorite', 'false'); 
              
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
                      console.log(`店舗ID: ${storeId} のお気に入りをDBに反映しました。新しい状態: false`);
                      // 💡 削除成功後、カードをDOMから削除し、URLに検索キーワードがあれば再検索を促す
                      card.remove(); 
                      let currentCount = parseInt(itemCountElement.textContent) || 0;
                      itemCountElement.textContent = `${Math.max(0, currentCount - 1)}件`;
                      
                      // 💡 検索中にお気に入りを解除した場合、再検索を促すメッセージ
                      if (searchInput.value.length > 0) {
                          alert('お気に入りを解除しました。検索結果を最新にするには、再度検索ボタンを押してください。');
                      }
                      
                      if (Math.max(0, currentCount - 1) === 0) {
                          const favoritesList = document.querySelector('.favorites-list');
                          favoritesList.innerHTML = '<p>お気に入りのお店はありません。</p>';
                      }

                  } else {
                      console.error('DB更新失敗:', data.message);
                      alert('お気に入りの状態を更新できませんでした。');
                      star.dataset.favorited = 'true';
                      star.innerHTML = '<i class="fa-solid fa-star"></i>';
                  }
              })
              .catch(error => {
                  console.error('通信エラー:', error);
                  alert('通信エラーが発生しました。お気に入りの状態を更新できませんでした。');
                  star.dataset.favorited = 'true';
                  star.innerHTML = '<i class="fa-solid fa-star"></i>';
              });
            }
          }
        });
      });
    });
  </script>
</body>
</html>