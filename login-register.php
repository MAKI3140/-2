<?php
    require_once('helpers/MemberDAO.php');

    $member_email = '';
    $member_name = '';
    $errs = [];
    $is_login_mode = true; // デフォルトはログインモード

    // セッション開始
    session_start();

    // ▼▼▼ LINEログインのエラーなどがあれば表示に追加 ▼▼▼
    if (isset($_SESSION['error'])) {
        $errs[] = $_SESSION['error'];
        unset($_SESSION['error']);
    }
    // ▲▲▲ ここまで追加 ▲▲▲

    // どのフォームが送信されたかに基づいてモードを決定
    if (isset($_POST['register_submit'])) {
        $is_login_mode = false;
    } elseif (isset($_POST['login_submit'])) {
        $is_login_mode = true;
    }

    //POSTメソッドでリクエストされたとき
    if($_SERVER['REQUEST_METHOD'] === 'POST'){
      $memberDAO = new MemberDAO();

      // --- 1. ログイン処理 ---
      if(isset($_POST['login_submit'])){
        $member_email = $_POST['member_email'];
        $member_password = $_POST['member_password'];

        $member = $memberDAO->get_member($member_email, $member_password);

        if($member !== false){
          session_regenerate_id(true);
          $_SESSION['member'] = $member;
          header('Location: index.php');
          exit;
        } else {
          $errs[] = 'メールアドレスまたはパスワードに誤りがあります。'; 
        }
      } 
      // --- 2. 新規登録処理 ---
      elseif(isset($_POST['register_submit'])){
        $member_email = $_POST['member_email'];
        $member_name = $_POST['member_name'];
        $member_password = $_POST['member_password'];

        // メールアドレスの重複チェック
        if ($memberDAO->email_exists($member_email)) {
            $errs[] = 'このメールアドレスは既に使用されています。';
        }

        // 氏名の未入力チェック
        if($member_name === ''){
            $errs[] = '氏名を入力してください。';
        }

        // パスワードのバリデーション
        if(!preg_match('/^[a-zA-Z0-9]{8,}$/', $member_password)){
            $errs[] = 'パスワードは8文字以上の半角英数字で入力してください。';
        }

        //エラーがないとき
        if(empty($errs)){
          $member = new Member();
          $member->member_email = $member_email;
          $member->member_name = $member_name;
          $member->member_password = $member_password;
          $member->is_member = 0;
          
          $memberDAO->insert($member);
          
          // 登録成功後、そのままログイン状態にする
          $new_member = $memberDAO->get_member($member_email, $member_password);
          if($new_member){
            session_regenerate_id(true);
            $_SESSION['member'] = $new_member;
            header('Location: index.php');
            exit;
          }
        }
      }
    }
    
    // エラーメッセージをHTML形式に整形
    $err_message_html = '';
    if (!empty($errs)) {
        $err_message_html = '<div class="error-message" style="color: #D8000C; background-color: #FFBABA; border: 1px solid; padding: 10px; margin-bottom: 15px; border-radius: 5px;">';
        foreach ($errs as $err) {
            $err_message_html .= '<p style="margin: 0;">' . htmlspecialchars($err) . '</p>';
        }
        $err_message_html .= '</div>';
    }
?>

<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン / 新規登録</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

  </head>
  <body onContextmenu="return false;">
    <div class="container">
      <main class="new-auth-screen">
        <header class="new-auth-header">
          <h1 class="logo-placeholder">アテメシ</h1>
        </header>

        <nav class="tab-nav">
          <a href="#login-form" class="tab-link <?= $is_login_mode ? 'active' : '' ?>">ログイン</a>
          <a href="#register-form" class="tab-link <?= !$is_login_mode ? 'active' : '' ?>">新規会員登録</a>
        </nav>

        <div class="tab-content">
          <form method="POST" action="login-register.php">
            <div id="login-form" class="tab-pane <?= $is_login_mode ? 'active' : '' ?>">
              
              <?php if($is_login_mode) echo $err_message_html; ?>

              <div class="new-form-group">
                <label for="login-email"><i class="fa-regular fa-envelope"></i> メールアドレス</label>
                <input type="email" id="login-email" name="member_email" placeholder="sample@google.com" value="<?= htmlspecialchars($member_email) ?>">
              </div>

              <div class="new-form-group" >
                <label for="login-password"><i class="fa-solid fa-lock"></i> パスワード</label>
                <div class="password-wrapper">
                  <input type="password" id="login-password" name="member_password" placeholder="********" oncopy="return false;" oncut="return false">
                  <i class="fa-regular fa-eye-slash toggle-password"></i>
                </div>
                <p class="password-hint">半角英数字のみ・8文字以上</p>
              </div>

              <button type="submit" class="new-auth-button" name="login_submit">ログイン</button>

              <div class="divider-or">または</div>
              <div class="social-login-section">
                  <a href="line_login.php" class="new-auth-button" style="background-color: #06C755; color: white; display: flex; align-items: center; justify-content: center; gap: 10px; text-decoration: none;">
                      <i class="fa-brands fa-line" style="font-size: 24px;"></i> LINEでログイン
                  </a>
              </div>
              </div>
          </form>
          
          <form method="POST" action="login-register.php">
            <div id="register-form" class="tab-pane <?= !$is_login_mode ? 'active' : '' ?>">

              <?php if(!$is_login_mode) echo $err_message_html; ?>

              <div class="new-form-group">
                <label for="register-name"><i class="fa-regular fa-user"></i> 氏名</label>
                <input type="text" id="register-name" name="member_name" placeholder="山田太郎" value="<?= htmlspecialchars($member_name) ?>" required>
              </div>

              <div class="new-form-group" >
                <label for="register-email"><i class="fa-regular fa-envelope"></i> メールアドレス</label>
                <input type="email" id="register-email" name="member_email" placeholder="sample@google.com" value="<?= htmlspecialchars($member_email) ?>" required>
              </div>

              <div class="new-form-group">
                <label for="register-password"><i class="fa-solid fa-lock"></i> パスワード</label>
                <div class="password-wrapper">
                  <input type="password" id="register-password" name="member_password"  oncopy="return false;"oncut="return false" placeholder="********" required minlength="8" pattern="[a-zA-Z0-9]+" title="半角英数字のみ・8文字以上">
                  <i class="fa-regular fa-eye-slash toggle-password"></i>
                </div>
                <p class="password-hint">半角英数字のみ・8文字以上</p>
              </div>
              
              <button type="submit" class="new-auth-button" name="register_submit">新規登録</button>

              <div class="divider-or">または</div>
              <div class="social-login-section">
                  <a href="line_login.php" class="new-auth-button" style="background-color: #06C755; color: white; display: flex; align-items: center; justify-content: center; gap: 10px; text-decoration: none;">
                      <i class="fa-brands fa-line" style="font-size: 24px;"></i> LINEでログイン
                  </a>
              </div>
              </div>
          </form>
        </div>
        
      </main>
    </div>

    <script>
      // タブ切り替え機能
      const tabLinks = document.querySelectorAll('.tab-link');
      const tabPanes = document.querySelectorAll('.tab-pane');

      tabLinks.forEach(link => {
        link.addEventListener('click', (e) => {
          e.preventDefault();
          tabLinks.forEach(l => l.classList.remove('active'));
          tabPanes.forEach(p => p.classList.remove('active'));
          link.classList.add('active');
          const targetPane = document.querySelector(link.getAttribute('href'));
          targetPane.classList.add('active');
        });
      });

      // パスワード表示/非表示機能
      const togglePasswordIcons = document.querySelectorAll('.toggle-password');
      togglePasswordIcons.forEach(icon => {
        icon.addEventListener('click', () => {
          const passwordInput = icon.closest('.password-wrapper').querySelector('input');
          if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
          } else {
            passwordInput.type = 'password';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
          }
        });
      });
    </script>

    <?php include('fixed-footer.php'); ?>
  </body>
</html>