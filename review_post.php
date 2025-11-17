<?php
    require_once('helpers/MemberDAO.php');
    require_once('helpers/HistoryDAO.php');

    session_start();

    // ログインチェック
    if (empty($_SESSION['member'])) {
        header('Location: login-register.php');
        exit;
    }

    $member = $_SESSION['member'];
    $store_info = null;
    $errs = [];

    // 店舗ID(hotpepper_code)の取得
    // POST(確認画面等があれば) または GET(詳細画面から) で受け取る
    $hotpepper_code = $_POST['hotpepper_code'] ?? ($_GET['id'] ?? '');

    if (empty($hotpepper_code)) {
        // IDがない場合はトップへ戻すなどの処理
        header('Location: index.php');
        exit;
    }

    // --- 店舗情報の取得 (表示用) ---
    $api_key = '8b7a467ccf017947'; // 💡 APIキー
    $base_url = 'http://webservice.recruit.co.jp/hotpepper/gourmet/v1/';
    $params = [
        'key' => $api_key,
        'format' => 'json',
        'id' => $hotpepper_code,
    ];
    $url = $base_url . '?' . http_build_query($params);
    $response = @file_get_contents($url);
    if ($response !== false) {
        $data = json_decode($response, true);
        $store_info = $data['results']['shop'][0] ?? null;
    }

    // --- 投稿処理 ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
        $evaluation = (int)($_POST['evaluation'] ?? 0);
        $review_text = trim($_POST['review_text'] ?? '');

        // バリデーション
        if ($evaluation < 1 || $evaluation > 5) {
            $errs[] = '星の数を選択してください。';
        }
        if (empty($review_text)) {
            $errs[] = 'レビューコメントを入力してください。';
        }

        if (empty($errs)) {
            $historyDAO = new HistoryDAO();
            // HistoryDAOに追加した post_review メソッドを呼び出し
            $success = $historyDAO->post_review($member->member_id, $hotpepper_code, $evaluation, $review_text);

            if ($success) {
                // 投稿成功 -> 店舗詳細へリダイレクト
                header('Location: store_detail.php?id=' . urlencode($hotpepper_code));
                exit;
            } else {
                $errs[] = 'レビューの投稿に失敗しました。もう一度お試しください。';
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>レビュー投稿</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        /* このページ独自のスタイル */
        .review-post-container {
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .target-store-info {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        .target-store-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }
        .target-store-name {
            font-weight: bold;
            font-size: 16px;
            color: #333;
        }

        /* 星評価のスタイル */
        .rating-group {
            display: flex;
            flex-direction: row-reverse; /* 星を右から左へ並べる（CSSでの選択ロジックのため） */
            justify-content: center;
            gap: 5px;
            margin-bottom: 20px;
        }
        .rating-group input {
            display: none; /* ラジオボタンは隠す */
        }
        .rating-group label {
            font-size: 30px;
            color: #ddd; /* 未選択の色 */
            cursor: pointer;
            transition: color 0.2s;
        }
        /* 選択された星、およびその「後ろ」にある星（見た目上は左側）を黄色にする */
        .rating-group input:checked ~ label,
        .rating-group label:hover,
        .rating-group label:hover ~ label {
            color: #FFC107;
        }

        .review-textarea {
            width: 100%;
            height: 150px;
            padding: 15px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 16px;
            box-sizing: border-box;
            resize: none;
            margin-bottom: 20px;
        }

        .error-box {
            background-color: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="page-header">
            <a href="javascript:history.back()" class="header-back-button">
               <i class="fa-solid fa-chevron-left"></i>
            </a>
            <h1>レビューを書く</h1>
        </header>

        <main class="review-page"> <form action="review_post.php" method="POST" class="review-post-container">
                
                <div class="target-store-info">
                    <?php 
                        $img_url = $store_info['photo']['pc']['l'] ?? ($store_info['photo']['mobile']['l'] ?? 'images/no_image.jpg');
                        $name = $store_info['name'] ?? '店舗名不明';
                    ?>
                    <img src="<?= htmlspecialchars($img_url) ?>" alt="店舗画像" class="target-store-img">
                    <div class="target-store-name"><?= htmlspecialchars($name) ?></div>
                </div>

                <?php if (!empty($errs)): ?>
                    <div class="error-box">
                        <?php foreach ($errs as $err): ?>
                            <p><?= htmlspecialchars($err) ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <input type="hidden" name="hotpepper_code" value="<?= htmlspecialchars($hotpepper_code) ?>">

                <p style="text-align: center; font-weight: bold; margin-bottom: 10px;">評価を選択</p>
                <div class="rating-group">
                    <input type="radio" id="star5" name="evaluation" value="5"><label for="star5" title="5点"><i class="fa-solid fa-star"></i></label>
                    <input type="radio" id="star4" name="evaluation" value="4"><label for="star4" title="4点"><i class="fa-solid fa-star"></i></label>
                    <input type="radio" id="star3" name="evaluation" value="3"><label for="star3" title="3点"><i class="fa-solid fa-star"></i></label>
                    <input type="radio" id="star2" name="evaluation" value="2"><label for="star2" title="2点"><i class="fa-solid fa-star"></i></label>
                    <input type="radio" id="star1" name="evaluation" value="1"><label for="star1" title="1点"><i class="fa-solid fa-star"></i></label>
                </div>

                <p style="font-weight: bold; margin-bottom: 10px;">レビューコメント</p>
                <textarea name="review_text" class="review-textarea" placeholder="お店の雰囲気や料理の感想を教えてください..."><?= htmlspecialchars($_POST['review_text'] ?? '') ?></textarea>

                <button type="submit" name="submit_review" class="review-post-button" style="padding: 15px; font-size: 16px; font-weight: bold; width: 100%; border-radius: 8px; cursor: pointer;">
                    投稿する
                </button>
            </form>

        </main>
    </div>

    <?php include('fixed-footer.php'); ?>
</body>
</html>