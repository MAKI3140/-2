<?php
require_once 'DAO.php';

class Member {
    public int    $member_id;
    public string $member_password;
    public string $member_email;
    public string $member_name;
    public int    $is_member;
    public  $is_verified;
    public ?string $profile_icon = null;
    public bool $temporary_registration = false;
    public $image; 
    
    // ▼▼▼ 追加: LINE ID用プロパティ ▼▼▼
    public $line_id; 
}

class MemberDAO {
    // ... (既存の get_member, email_exists, insert, update メソッドはそのまま残す) ...

    // 既存のコードの insert メソッドの下あたりに以下を追加してください

    // ▼▼▼ 追加: LINE ID で会員を取得 ▼▼▼
    public function get_member_by_line_id(string $line_id) {
        $dbh = DAO::get_db_connect();
        $sql = "SELECT * FROM Member WHERE line_id = :line_id";
        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(':line_id', $line_id, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchObject('Member');
    }

    // ▼▼▼ 追加: LINEログイン用の新規登録 ▼▼▼
    public function register_by_line($line_id, $line_name, $line_image_url) {
        $dbh = DAO::get_db_connect();

        // LINEログインではメールアドレスやパスワードが必須ではない場合が多いため、
        // ダミーデータを生成してNOT NULL制約を回避します
        $dummy_email = "line_" . $line_id . "@example.com";
        $dummy_password = password_hash(bin2hex(random_bytes(10)), PASSWORD_DEFAULT); // ランダムなパスワード

        // 名前が取得できなかった場合のデフォルト
        $name = $line_name ?: "ゲストユーザー";

        // 既にLINE IDが存在するか確認
        if ($this->get_member_by_line_id($line_id)) {
            return false; 
        }

        $sql = "INSERT INTO Member(member_email, member_name, member_password, is_member, line_id, image)
                VALUES(:member_email, :member_name, :member_password, 0, :line_id, :image)";
        
        $stmt = $dbh->prepare($sql);

        $stmt->bindValue(':member_email', $dummy_email, PDO::PARAM_STR);
        $stmt->bindValue(':member_name', $name, PDO::PARAM_STR);
        $stmt->bindValue(':member_password', $dummy_password, PDO::PARAM_STR);
        $stmt->bindValue(':line_id', $line_id, PDO::PARAM_STR);
        
        // LINEのプロフィール画像を保存 (カラム型がNVARCHARに変更されている前提)
        $stmt->bindValue(':image', $line_image_url, PDO::PARAM_STR); // nullならnullが入る

        return $stmt->execute();
    }

    // ... (残りのメソッド update, get_pass, admin用メソッドなどはそのまま) ...
    
    // ※念のため update メソッドなど既存コードは前回の「MemberDAO.php (修正版)」の状態を維持してください。
    // 省略していますが、既存のコードを消さないように注意してください。
    
    //DBからメールアドレスとパスワードが一致する会員データを取得
    public function get_member(string $member_email ,string $member_password){
        $dbh = DAO::get_db_connect();

        $sql = "SELECT * FROM Member 
                WHERE member_email = :member_email";

        $stmt = $dbh->prepare($sql);

        $stmt->bindValue(':member_email',$member_email,PDO::PARAM_STR);
        
        $stmt->execute();

        $member = $stmt->fetchObject('Member');

        // 会員データが取得できたとき
        if($member !== false){
            if(password_verify($member_password,$member->member_password)){
                return $member;
            }
        }
        return false;
    }

       // 指定したメールアドレスの会員データが存在すれば true を返す
    public function email_exists(string $member_email){

        $dbh = DAO::get_db_connect();
        $sql = "SELECT member_email FROM Member
                    WHERE member_email = :member_email";
        $stmt = $dbh->prepare($sql);

        $stmt->bindValue(':member_email',$member_email,PDO::PARAM_STR);

        $stmt->execute();

        if($stmt->fetch() !== false){
            return true; //member_emailが存在する
        }else{
            return false; //member_emailが存在しない
        }
    }

    // 会員データを登録する (is_member も登録できるように変更)
    public function insert(Member $member){
        $dbh = DAO::get_db_connect();
        $member_email = $member->member_email;

        if(!($this->email_exists($member_email))){

            // is_member も変数から受け取るように修正
            $sql = "INSERT INTO Member(member_email,member_name,member_password,is_member)
                    VALUES(:member_email,:member_name,:member_password,:is_member)";
            $stmt = $dbh->prepare($sql);

            // パスワードをハッシュ化
            $member_password = password_hash($member->member_password, PASSWORD_DEFAULT);

            $stmt->bindValue(':member_email',$member->member_email,PDO::PARAM_STR);
            $stmt->bindValue(':member_name',$member->member_name,PDO::PARAM_STR);
            $stmt->bindValue(':member_password',$member_password,PDO::PARAM_STR);
            // is_member を $member オブジェクトから取得
            $stmt->bindValue(':is_member',$member->is_member,PDO::PARAM_INT);

            $stmt->execute();

            return true;
            
        }else{

            return false; 

        }

    }

    // 会員データを更新する
    public function update(Member $member) {
        $dbh = DAO::get_db_connect();

        $sql_parts = [
            "member_name = :member_name",
            "member_email = :member_email"
        ];
        
        if (!empty($member->member_password)) {
            $sql_parts[] = "member_password = :member_password";
        }

        if (isset($member->is_member)) {
            $sql_parts[] = "is_member = :is_member";
        }

        if (!empty($member->image)) {
            $sql_parts[] = "image = :image";
        }
        
        $sql = "UPDATE Member SET " . implode(", ", $sql_parts) . " WHERE member_id = :member_id";
        
        $stmt = $dbh->prepare($sql);

        $stmt->bindValue(':member_name', $member->member_name, PDO::PARAM_STR);
        $stmt->bindValue(':member_email', $member->member_email, PDO::PARAM_STR);
        $stmt->bindValue(':member_id', $member->member_id, PDO::PARAM_INT);
        
        if (!empty($member->member_password)) {
            $member_password_hashed = password_hash($member->member_password, PASSWORD_DEFAULT);
            $stmt->bindValue(':member_password', $member_password_hashed, PDO::PARAM_STR);
        }

        if (isset($member->is_member)) {
            $stmt->bindValue(':is_member', $member->is_member, PDO::PARAM_INT);
        }

        if (!empty($member->image)) {
            $stmt->bindValue(':image', $member->image, PDO::PARAM_STR);
        }

        return $stmt->execute();
    }


    //パスワード確認
    public function get_pass(string $member_email ,string $member_password){
        $dbh = DAO::get_db_connect();

        $sql = "SELECT * FROM member 
                WHERE member_email = :member_email and member_password = :member_password";

        $stmt = $dbh->prepare($sql);

        $stmt->bindValue(':member_email',$member_email,PDO::PARAM_STR);
        $stmt->bindValue(':member_password',$member_password,PDO::PARAM_STR);
        
        $stmt->execute();

        $member = $stmt->fetchObject('Member');

        // 会員データが取得できたとき
        if($member !== false){
            return true;
        }
            return false;
    }


    // ▼▼▼ ここから admin_dashboard.php 用の関数 ▼▼▼

    public function get_all_members() {
        $dbh = DAO::get_db_connect();
        $sql = "SELECT member_id, member_email, member_name, is_member 
                FROM Member 
                ORDER BY member_id ASC";
        
        $stmt = $dbh->query($sql);
        return $stmt->fetchAll(PDO::FETCH_CLASS, 'Member');
    }

    public function delete_member_by_id(int $member_id) {
        $dbh = DAO::get_db_connect();
        $sql = "DELETE FROM Member WHERE member_id = :member_id";
        
        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(':member_id', $member_id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function get_members_by_filter(string $filter_status, string $filter_keyword) {
        $dbh = DAO::get_db_connect();
        
        $sql_parts = [];
        $params = [];

        $sql = "SELECT member_id, member_email, member_name, is_member FROM Member WHERE 1=1";

        if ($filter_status === 'admin') {
            $sql .= " AND is_member = 1";
        } elseif ($filter_status === 'user') {
            $sql .= " AND is_member = 0";
        }
        
        if (!empty($filter_keyword)) {
            $sql .= " AND (member_name LIKE :keyword OR member_email LIKE :keyword)";
            $params[':keyword'] = '%' . $filter_keyword . '%';
        }

        $sql .= " ORDER BY member_id ASC";
        
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_CLASS, 'Member');
    }
}
?>