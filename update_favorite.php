<?php
require_once('helpers/MemberDAO.php');
require_once('helpers/HistoryDAO.php');

session_start();

// JSONレスポンスを設定
header('Content-Type: application/json');

// ログインチェックとPOSTデータの検証
if (empty($_SESSION['member']) || $_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['code']) || !isset($_POST['favorite'])) {
    http_response_code(400); // 400 Bad Request
    echo json_encode(['success' => false, 'message' => '無効なリクエストです。']);
    exit;
}

$member_id = $_SESSION['member']->member_id;
$hotpepper_code = $_POST['code'];
// 'true' なら '1'、'false' なら '0' に変換
$is_favorite_status = ($_POST['favorite'] === 'true') ? '1' : '0';

$HistoryDAO = new HistoryDAO();

// データベース更新の実行
$success = $HistoryDAO->update_favorite_status($member_id, $hotpepper_code, $is_favorite_status);

if ($success) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500); // 500 Internal Server Error
    echo json_encode(['success' => false, 'message' => 'データベースの更新に失敗しました。']);
}