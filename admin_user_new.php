<?php
    require_once('helpers/MemberDAO.php');
    session_start();

    // 管理者（is_member == 1）でなければ、ログインページへリダイレクト
    if (empty($_SESSION['member']) || $_SESSION['member']->is_member != 1) {
        header('Location: admin_login.php');
        exit;
    }

    $errs = [];
    $member_name = '';
    $member_email = '';
    $is_member = 0; 

    if($_SERVER['REQUEST_METHOD'] === 'POST'){
        $member_name = $_POST['member_name'];
        $member_email = $_POST['member_email'];
        $member_password = $_POST['member_password'];
        $is_member = (int)($_POST['is_member'] ?? 0); 

        $memberDAO = new MemberDAO();

        if($member_name === ''){
            $errs[] = '氏名を入力してください。';
        }
        if (!filter_var($member_email, FILTER_VALIDATE_EMAIL)) {
            $errs[] = 'メールアドレスの形式が正しくありません。';
        }
        elseif ($memberDAO->email_exists($member_email)) {
            $errs[] = 'このメールアドレスは既に使用されています。';
        }
        if(!preg_match('/^[a-zA-Z0-9]{8,}$/', $member_password)){
            $errs[] = 'パスワードは8文字以上の半角英数字で入力してください。';
        }

        if(empty($errs)){
          $member = new Member();
          $member->member_email = $member_email;
          $member->member_name = $member_name;
          $member->member_password = $member_password;
          $member->is_member = $is_member;
          
          if ($memberDAO->insert($member)) {
            header('Location: admin_dashboard.php'); 
            exit;
          } else {
            $errs[] = 'データベースへの登録に失敗しました。';
          }
        }
    }
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理ダッシュボード - 新規ユーザー</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="admin-dash-body">

    <div class="admin-dash-container admin-new-user-container">
        
        <header class="admin-dash-header">
            <h2>新しいユーザー</h2>
            <div class="admin-header-actions">
                <a href="admin_dashboard.php" class="admin-button-secondary">
                    <i class="fa-solid fa-xmark"></i> キャンセル
                </a>
                <a href="logout.php" class="admin-button-danger">
                    <i class="fa-solid fa-right-from-bracket"></i> ログアウト
                </a>
                </div>
        </header>

        <div class="admin-form-container">

            <?php if (!empty($errs)): // エラーメッセージ表示 ?>
                <div class="admin-error-message">
                    <?php foreach ($errs as $err): ?>
                        <p><?= htmlspecialchars($err) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form action="admin_user_new.php" method="POST">
                
                <div class="admin-form-group">
                    <label for="member_name">氏名 <span class="admin-required">*</span></label>
                    <input type="text" id="member_name" name="member_name" value="<?= htmlspecialchars($member_name) ?>" required>
                </div>

                <div class="admin-form-group">
                    <label for="member_email">メールアドレス (ログインID) <span class="admin-required">*</span></label>
                    <input type="email" id="member_email" name="member_email" value="<?= htmlspecialchars($member_email) ?>" required>
                </div>
                
                <div class="admin-form-group">
                    <label for="member_password">パスワード <span class="admin-required">*</span></label>
                    <input type="password" id="member_password" name="member_password" required minlength="8" placeholder="8文字以上の半角英数字">
                    <p class="admin-form-hint">8文字以上の半角英数字で入力してください。</p>
                </div>

                <div class="admin-form-group">
                    <label>権限 <span class="admin-required">*</span></label>
                    <div class="admin-radio-group">
                        <input type="radio" id="is_member_0" name="is_member" value="0" <?= $is_member == 0 ? 'checked' : '' ?>>
                        <label for="is_member_0">一般ユーザー</label>
                    </div>
                    <div class="admin-radio-group">
                        <input type="radio" id="is_member_1" name="is_member" value="1" <?= $is_member == 1 ? 'checked' : '' ?>>
                        <label for="is_member_1">システム管理者</label>
                    </div>
                </div>

                <div class="admin-form-actions">
                    <button type="submit" class="admin-button-primary">
                        <i class="fa-solid fa-check"></i> 作成する
                    </button>
                </div>
            </form>
        </div>

    </div>
</body>
</html>