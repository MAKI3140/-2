<?php
require_once 'DAO.php';
require_once('MemberDAO.php');

class History {
    public int      $history_id ;   //履歴ＩＤ
    public int      $member_id ;    //会員ＩＤ
    public int      $restaurant_id; //店舗ID
    public DateTime $time;          //日付
    public int      $evaluation;    //評価
    public string   $is_favorite;   //お気に入り
    public string   $review;        //レビュー
}

class HistoryDAO {

    // hotpepper_code、訪問日時、お気に入りフラグなどを一度に取得する
    public function get_history_details($member_id) {

        $dbh = DAO::get_db_connect();

        
        $sql = "SELECT R.hotpepper_code, H.time, H.is_favorite
                FROM Restaurant AS R
                INNER JOIN History AS H ON R.restaurant_id = H.restaurant_id
                WHERE H.member_id = :member_id
                ORDER BY H.time DESC";  // 訪問日時の新しい順でソート

        $stmt = $dbh->prepare($sql);

        $stmt->bindValue(':member_id', $member_id, PDO::PARAM_INT);
        
        $stmt->execute();

        // 履歴データ（hotpepper_code, time, is_favoriteなど）を配列として返す
        $data = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
            $data[] = $row;
        }
        return $data;
    }

    public function update_favorite_status($member_id, $hotpepper_code, $is_favorite) {
        $dbh = DAO::get_db_connect();
        
        try {
            
            // 1. hotpepper_codeからrestaurant_idを取得
            $sql_r = "SELECT restaurant_id FROM Restaurant WHERE hotpepper_code = :hotpepper_code";
            $stmt_r = $dbh->prepare($sql_r);
            $stmt_r->bindValue(':hotpepper_code', $hotpepper_code, PDO::PARAM_STR);
            $stmt_r->execute();
            $restaurant_id = $stmt_r->fetchColumn();

            if (!$restaurant_id) {
                return false; // 店舗IDが見つからない
            }

            // 2. Historyテーブルのis_favoriteを更新（会員IDと店舗IDで特定）
            // 注意: 該当する全ての履歴のフラグが更新されます
            $sql_h = "UPDATE History 
                      SET is_favorite = :is_favorite 
                      WHERE member_id = :member_id 
                      AND restaurant_id = :restaurant_id";
            
            $stmt_h = $dbh->prepare($sql_h);
            
            $stmt_h->bindValue(':is_favorite', $is_favorite, PDO::PARAM_STR); // '0' or '1'
            $stmt_h->bindValue(':member_id', $member_id, PDO::PARAM_INT);
            $stmt_h->bindValue(':restaurant_id', $restaurant_id, PDO::PARAM_INT);

            return $stmt_h->execute();
        } catch (PDOException $e) {
            // エラーハンドリング
            error_log("Favorite update failed: " . $e->getMessage());
            return false;
        }
    }

    public function get_review($hotpepper_code) {

        $dbh = DAO::get_db_connect();

        
        $sql = "SELECT time, review, evaluation, member_name 
                    FROM History AS H
                    INNER JOIN Restaurant AS R ON R.restaurant_id = H.restaurant_id
                    INNER JOIN Member AS M ON M.member_id = H.member_id
                    WHERE hotpepper_code = :hotpepper_code
                    AND review IS NOT NULL -- レビューが書かれているもののみ
                    AND review != ''
                    ORDER BY H.time DESC";

        $stmt = $dbh->prepare($sql);

        $stmt->bindValue(':hotpepper_code', $hotpepper_code, PDO::PARAM_STR);
        
        $stmt->execute();

        $data = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
            $data[] = $row;
        }
        return $data;
    }

   // ▼▼▼ 修正版: レビューを投稿する (1店舗1回・上書き仕様) ▼▼▼
    public function post_review($member_id, $hotpepper_code, $evaluation, $review_text) {
        $dbh = DAO::get_db_connect();

        try {
            $dbh->beginTransaction();

            // 1. Restaurantテーブルに店舗があるか確認 (なければ登録)
            $sql_r_check = "SELECT restaurant_id FROM Restaurant WHERE hotpepper_code = :hotpepper_code";
            $stmt_r_check = $dbh->prepare($sql_r_check);
            $stmt_r_check->bindValue(':hotpepper_code', $hotpepper_code, PDO::PARAM_STR);
            $stmt_r_check->execute();
            $restaurant_id = $stmt_r_check->fetchColumn();

            if (!$restaurant_id) {
                $sql_r_insert = "INSERT INTO Restaurant (hotpepper_code) VALUES (:hotpepper_code)";
                $stmt_r_insert = $dbh->prepare($sql_r_insert);
                $stmt_r_insert->bindValue(':hotpepper_code', $hotpepper_code, PDO::PARAM_STR);
                $stmt_r_insert->execute();
                $restaurant_id = $dbh->lastInsertId();
            }

            // 2. Historyテーブルに「この会員」かつ「この店舗」の履歴が既にあるか確認
            $sql_h_check = "SELECT history_id FROM History WHERE member_id = :member_id AND restaurant_id = :restaurant_id";
            $stmt_h_check = $dbh->prepare($sql_h_check);
            $stmt_h_check->bindValue(':member_id', $member_id, PDO::PARAM_INT);
            $stmt_h_check->bindValue(':restaurant_id', $restaurant_id, PDO::PARAM_INT);
            $stmt_h_check->execute();
            $existing_history_id = $stmt_h_check->fetchColumn();

            if ($existing_history_id) {
                // A. 既に履歴がある場合 -> 上書き更新 (UPDATE)
                // 日付(time)も最新の投稿日時に更新します
                $sql_update = "UPDATE History 
                               SET evaluation = :evaluation, 
                                   review = :review, 
                                   time = GETDATE() 
                               WHERE history_id = :history_id";
                
                $stmt_update = $dbh->prepare($sql_update);
                $stmt_update->bindValue(':evaluation', $evaluation, PDO::PARAM_INT);
                $stmt_update->bindValue(':review', $review_text, PDO::PARAM_STR);
                $stmt_update->bindValue(':history_id', $existing_history_id, PDO::PARAM_INT);
                $stmt_update->execute();

            } else {
                // B. 履歴がない場合 -> 新規登録 (INSERT)
                $sql_insert = "INSERT INTO History (member_id, restaurant_id, time, evaluation, review, is_favorite) 
                               VALUES (:member_id, :restaurant_id, GETDATE(), :evaluation, :review, '0')";
                
                $stmt_insert = $dbh->prepare($sql_insert);
                $stmt_insert->bindValue(':member_id', $member_id, PDO::PARAM_INT);
                $stmt_insert->bindValue(':restaurant_id', $restaurant_id, PDO::PARAM_INT);
                $stmt_insert->bindValue(':evaluation', $evaluation, PDO::PARAM_INT);
                $stmt_insert->bindValue(':review', $review_text, PDO::PARAM_STR);
                $stmt_insert->execute();
            }

            $dbh->commit();
            return true;

        } catch (PDOException $e) {
            $dbh->rollBack();
            error_log("Review post failed: " . $e->getMessage());
            return false;
        }
    }
    // ▲▲▲ 修正ここまで ▲▲▲

}

