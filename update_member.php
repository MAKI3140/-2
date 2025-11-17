<?php
require_once('helpers/MemberDAO.php');

session_start();

// POSTリクエスト以外はマイページへリダイレクト
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: mypage.php');
    exit;
}

// ログインユーザーのIDを取得
if (empty($_SESSION['member'])) {
    header('Location: login-register.php');
    exit;
}
$member_id = $_SESSION['member']->member_id;

// POSTデータから新しい情報を取得
$new_name = filter_input(INPUT_POST, 'member_name');
$new_email = filter_input(INPUT_POST, 'member_email');
$new_password = $_POST['member_password'];
$new_password_confirm = $_POST['member_password_confirm'];

$errs = [];

// --- サーバーサイド バリデーションの強化 ---

// 1. 氏名の未入力チェック
if (empty($new_name)) {
    $errs[] = 'お名前を入力してください。';
}

// 2. メールアドレスの形式チェックと必須チェック
if (empty($new_email)) {
    $errs[] = 'メールアドレスを入力してください。';
} elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
    $errs[] = 'メールアドレスの形式が正しくありません。';
}

// 3. パスワードの形式チェック (入力がある場合のみ)
if (!empty($new_password) || !empty($new_password_confirm)) {
    if (empty($new_password) || empty($new_password_confirm)) {
        $errs[] = '新しいパスワードと確認用パスワードの両方を入力してください。';
    } else if ($new_password !== $new_password_confirm) {
        $errs[] = 'パスワードと確認用パスワードが一致しません。';
    }
    if (!empty($new_password) && !preg_match('/^[a-zA-Z0-9]{8,}$/', $new_password)) {
        $errs[] = 'パスワードは8文字以上の半角英数字で入力してください。';
    }
}

// 4. エラーがある場合はセッションにエラーメッセージを保存してリダイレクト
if (!empty($errs)) {
    $_SESSION['error'] = implode('<br>', $errs); 
    header('Location: mypage.php');
    exit;
}

// エラーがなければDAO処理へ進む
$memberDAO = new MemberDAO();

// メールアドレスの重複チェック
if ($new_email !== $_SESSION['member']->member_email) {
    if ($memberDAO->email_exists($new_email)) {
        $_SESSION['error'] = 'そのメールアドレスは既に使用されています。';
        header('Location: mypage.php');
        exit;
    }
}

// 更新用 Member オブジェクトを作成し、値をセット
$updated_member = new Member();
$updated_member->member_id = $member_id;
$updated_member->member_name = $new_name;
$updated_member->member_email = $new_email;
$updated_member->member_password = $new_password;

// ▼▼▼ 画像アップロード処理 ▼▼▼
if (isset($_FILES['member_image']) && $_FILES['member_image']['error'] === UPLOAD_ERR_OK) {
    // 保存先ディレクトリ
    $upload_dir = 'images/member_icons/';
    
    // ディレクトリが存在しない場合は作成
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // ファイル名の生成 (ユニークにする)
    $file_ext = pathinfo($_FILES['member_image']['name'], PATHINFO_EXTENSION);
    $new_filename = 'user_' . $member_id . '_' . time() . '.' . $file_ext;
    $target_path = $upload_dir . $new_filename;
    
    // ファイルの移動
    if (move_uploaded_file($_FILES['member_image']['tmp_name'], $target_path)) {
        $updated_member->image = $target_path; // DB更新用にパスをセット
    } else {
        $_SESSION['error'] = '画像のアップロードに失敗しました。';
        header('Location: mypage.php');
        exit;
    }
}
// ▲▲▲ 画像処理ここまで ▲▲▲

// データベースを更新
if ($memberDAO->update($updated_member)) {
    // 成功した場合
    $_SESSION['member']->member_name = $new_name;
    $_SESSION['member']->member_email = $new_email;
    
    // 画像が更新された場合はセッションも更新
    if (!empty($updated_member->image)) {
        $_SESSION['member']->image = $updated_member->image;
    }
    
    $_SESSION['success'] = '会員情報を更新しました。';
    
} else {
    // 失敗した場合
    $_SESSION['error'] = '会員情報の更新に失敗しました。';
}

header('Location: mypage.php');
exit;
?>