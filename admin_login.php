<?php
    require_once('helpers/MemberDAO.php');
    session_start();

    // 既に管理者としてログイン済みの場合はダッシュボードへ
    if (!empty($_SESSION['member']) && $_SESSION['member']->is_member == 1) {
        header('Location: admin_dashboard.php');
        exit;
    }

    $errs = [];
    $member_email = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $member_email = $_POST['member_email'];
        $member_password = $_POST['member_password'];

        $memberDAO = new MemberDAO();
        $member = $memberDAO->get_member($member_email, $member_password);

        if ($member !== false) {
            // ログイン成功
            if ($member->is_member == 1) {
                // ★ 管理者（is_member == 1）であるかチェック
                session_regenerate_id(true);
                $_SESSION['member'] = $member;
                
                // (ここに最終ログイン日時を更新する処理を追加するのがオススメです)
                
                header('Location: admin_dashboard.php');
                exit;
            } else {
                // 一般ユーザーだった場合
                $errs[] = '管理者権限がありません。';
            }
        } else {
            // 認証失敗
            $errs[] = 'メールアドレスまたはパスワードに誤りがあります。';
        }
    }
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理画面ログイン</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="admin-login-body">

    <div class="admin-login-container">
        <div class="admin-login-box">
            <h1 class="admin-login-logo">アテメシ 管理画面</h1>
            
            <p class="admin-login-lead">
                管理者アカウントでログインしてください。
            </p>

            <?php if (!empty($errs)): // エラーメッセージ表示 ?>
                <div class="admin-error-message">
                    <?php foreach ($errs as $err): ?>
                        <p><?= htmlspecialchars($err) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form action="admin_login.php" method="POST">
                <div class="admin-form-group">
                    <label for="email">メールアドレス</label>
                    <input type="email" id="email" name="member_email" value="<?= htmlspecialchars($member_email) ?>" required>
                </div>
                
                <div class="admin-form-group">
                    <label for="password">パスワード</label>
                    <input type="password" id="password" name="member_password" required>
                </div>

                <div class="admin-form-group-checkbox">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">ログイン状態を保持する</label>
                </div>

                <button type="submit" class="admin-login-button">ログイン</button>
            </form>
        </div>
        <p class="admin-login-footer">Copyright © アテメシ. All Rights Reserved.</p>
    </div>

</body>
</html>