<?php
// check_password.php
require_once('helpers/MemberDAO.php');

session_start();

header('Content-Type: application/json');

// POSTリクエストとログインチェック
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['member'])) {
    echo json_encode(['success' => false, 'error' => '無効なリクエストです。']);
    exit;
}

$current_password = filter_input(INPUT_POST, 'current_password');

if (empty($current_password)) {
    echo json_encode(['success' => false, 'error' => 'パスワードを入力してください。']);
    exit;
}

$memberDAO = new MemberDAO();

// セッションからメールアドレスを取得
$member_email = $_SESSION['member']->member_email;

// MemberDAOのget_memberメソッドを利用してパスワードを照合
// get_memberは、照合成功時にMemberオブジェクトを、失敗時にfalseを返します。
$member_authenticated = $memberDAO->get_member($member_email, $current_password);

if ($member_authenticated !== false) {
    // 照合成功
    echo json_encode(['success' => true]);
} else {
    // 照合失敗
    echo json_encode(['success' => false, 'error' => '現在のパスワードが正しくありません。']);
}
exit;
?>