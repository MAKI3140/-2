<?php
    require_once('helpers/MemberDAO.php');
    // セッション開始
    session_start();

    // セッションに会員情報がなければログインページへリダイレクト
    if (empty($_SESSION['member'])) {
        header('Location: login-register.php');
        exit;
    }
    $member = $_SESSION['member'];
    
    // ▼▼▼ アイコン表示の準備 ▼▼▼
    // DBに画像パスがあればそれを表示、なければデフォルトアイコン
    $icon_html = '';
    if (!empty($member->image)) {
        $icon_html = '<img src="' . htmlspecialchars($member->image) . '" alt="プロフィール画像" class="mypage-icon-img" id="profile-icon-display">';
    } else {
        $icon_html = '<div class="mypage-icon" id="profile-icon-display">👤</div>';
    }
    // ▲▲▲ 準備ここまで ▲▲▲
?>
<!DOCTYPE html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>マイページ</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
  <div class="container">
    <div class="auth-screen">
      <header class="auth-header mypage-header">
        <a href="logout.php" class="header-home-icon"><img src="images/logout.png" alt="ログアウト"class="logout-icon"></a>
        <div class="header-profile">
          
          <div class="icon-wrapper" style="cursor: pointer; position: relative; display: inline-block;" title="アイコンを変更">
             <?= $icon_html ?>
             <div style="position: absolute; bottom: 0; right: 0; background: #fff; border-radius: 50%; padding: 4px; border: 1px solid #ccc; font-size: 12px; color: #555;">
                 <i class="fa-solid fa-camera"></i>
             </div>
          </div>
          <h1>マイページ</h1>
        </div>
      </header>

      <main class="auth-body">

      <?php if (isset($_SESSION['success'])): // 成功メッセージの表示 ?>
            <div style="background-color: #e8f5e9; color: #2e7d32; padding: 10px; border-radius: 4px; margin-bottom: 20px;">
                <?= htmlspecialchars($_SESSION['success']); ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): // エラーメッセージの表示 ?>
            <div style="background-color: #ffebee; color: #c62828; padding: 10px; border-radius: 4px; margin-bottom: 20px;">
                <?= htmlspecialchars($_SESSION['error']); ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <form id="member-form" action="update_member.php" method="POST" enctype="multipart/form-data">
            
            <input type="file" name="member_image" id="member-image-input" accept="image/*" style="display: none;">
            
            <div class="new-form-group">
                <label for="member-name"><i class="fa-regular fa-user"></i> お名前</label>
                <input type="text" id="member-name" name="member_name" placeholder="未登録" value="<?= htmlspecialchars($member->member_name) ?>" readonly>
            </div>
            <div class="new-form-group">
                <label for="member-email"><i class="fa-regular fa-envelope"></i> メールアドレス</label>
                <div class="input-with-button">
                    <input type="email" id="member-email" name="member_email" placeholder="未登録" value="<?= htmlspecialchars($member->member_email) ?>" readonly>
                </div>
            </div>
              
              <div class="new-form-group" id="current-password-group">
                  <label for="current-password"><i class="fa-solid fa-key"></i> 現在のパスワード</label>
                  <input type="password" id="current-password" name="current_password" placeholder="認証のため入力" value="">
              </div>

              <div class="new-form-group" id="new-password-group" style="display:none;">
                  <label for="member-password"><i class="fa-solid fa-lock"></i> 新しいパスワード</label>
                  <input type="password" id="member-password" name="member_password" placeholder="変更する場合は入力" value="" readonly>
              </div>
              <div class="new-form-group" id="password-confirm-group" style="display:none;"> 
                  <label for="member-password-confirm"><i class="fa-solid fa-lock"></i> パスワード(確認用)</label>
                  <input type="password" id="member-password-confirm" name="member_password_confirm" placeholder="確認のため再入力" value="" readonly>
              </div>
              
              <button type="button" id="edit-toggle-button" class="history-button">編集</button>

              <button type="submit" id="save-button" class="auth-button save-button" disabled style="display:none;">保存</button>
          </form>
      </main>
    </div>
  </div>

  <?php include('fixed-footer.php'); ?>
  
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('member-form');
        const editButton = document.getElementById('edit-toggle-button');
        const saveButton = document.getElementById('save-button');
        
        const nameInput = document.getElementById('member-name');
        const emailInput = document.getElementById('member-email');
        
        const currentPasswordGroup = document.getElementById('current-password-group');
        const currentPasswordInput = document.getElementById('current-password');
        
        const newPasswordGroup = document.getElementById('new-password-group');
        const passwordInput = document.getElementById('member-password');
        const passwordConfirmGroup = document.getElementById('password-confirm-group');
        const passwordConfirmInput = document.getElementById('member-password-confirm');

        // ▼▼▼ アイコン画像変更処理 ▼▼▼
        const iconDisplay = document.getElementById('profile-icon-display');
        const iconWrapper = document.querySelector('.icon-wrapper');
        const imageInput = document.getElementById('member-image-input');

        // アイコンクリックでファイル選択を開く
        iconWrapper.addEventListener('click', function() {
            imageInput.click();
        });

        // ファイル選択時のプレビュー表示
        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    // アイコンが表示されていた場合（imgタグ）はsrcを更新
                    // デフォルトアイコン（divタグ）だった場合はimgタグに置き換え
                    
                    if (iconDisplay.tagName === 'IMG') {
                        iconDisplay.src = event.target.result;
                    } else {
                        // 新しいimgタグを作成
                        const newImg = document.createElement('img');
                        newImg.src = event.target.result;
                        newImg.alt = "プロフィール画像";
                        newImg.className = "mypage-icon-img";
                        newImg.id = "profile-icon-display";
                        
                        // ラッパーの中身を入れ替え（カメラアイコンは残す）
                        iconDisplay.replaceWith(newImg);
                        
                        // 参照変数を更新（次回以降のために）
                        // ※DOM要素が置き換わったので、再取得等は不要だが変数の参照先はずれることに注意
                    }
                };
                reader.readAsDataURL(file);
                
                // 画像を変更したら、ユーザーに「保存」を促すために編集モードへの誘導が必要
                // ここでは簡易的にアラートを出すか、そのままにする
                // 保存するには「編集」→「認証」→「保存」のフローが必要です
            }
        });
        // ▲▲▲ ここまで ▲▲▲


        let isAuthenticated = false; 

        // 初期設定
        nameInput.readOnly = true;
        emailInput.readOnly = true;
        currentPasswordInput.readOnly = false; // 認証用は最初から入力可能

        // フォームの初期アクションは無効（認証成功後に更新）
        form.action = 'javascript:void(0);'; 
        form.onsubmit = function(e) {e.preventDefault();}; 
        saveButton.style.display = 'none'; // 保存ボタンは初期非表示

        // 「編集/認証」ボタンのクリックイベント
        editButton.addEventListener('click', function() {
            
            if (editButton.textContent === 'キャンセル') {
                 // 認証後、または認証前のキャンセルボタンが押された場合
                 location.reload(); 
                 return;
            }
            
            if (!isAuthenticated) {
                // STEP 1: 認証モード
                const currentPassword = currentPasswordInput.value.trim();

                if (editButton.textContent === '編集' && currentPassword !== '') {
                    // ★修正: 「編集」ボタンの状態で、パスワードが入力されていれば、即座に認証処理を実行
                    authenticatePassword(currentPassword);
                    
                } else if (editButton.textContent === '編集' && currentPassword === '') {
                    // 「編集」ボタンの状態でパスワードが空の場合、入力を促すモードへ移行
                    
                    // 氏名とメールは一時的にreadonlyにし、認証を要求
                    nameInput.readOnly = true;
                    emailInput.readOnly = true;
                    currentPasswordInput.value = ''; // 入力値をクリア

                    editButton.textContent = '認証'; // ボタンを「認証」に変更
                    saveButton.disabled = true; 
                    
                } else if (editButton.textContent === '認証') {
                    // 「認証」ボタンが押されたら、認証処理を実行
                    if (currentPassword === '') {
                        alert('現在のパスワードを入力してください。');
                        return;
                    }
                    authenticatePassword(currentPassword);
                }

            } else {
                // 認証済みの場合（「キャンセル」処理のみ実行）
                location.reload(); 
            }
        });

        // パスワード認証関数 (引数でパスワードを受け取るように変更)
        function authenticatePassword(password) {
            
            // 認証中はボタンを無効化
            editButton.disabled = true; 

            // AJAXでパスワードをサーバーに送信して照合
            fetch('check_password.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `current_password=${encodeURIComponent(password)}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                editButton.disabled = false; 

                if (data.success) {
                    // STEP 2: 認証成功 -> 編集モードへ移行
                    isAuthenticated = true;

                    // 画面表示を切り替え
                    currentPasswordGroup.style.display = 'none'; 
                    newPasswordGroup.style.display = 'block';     
                    passwordConfirmGroup.style.display = 'block'; 
                    
                    // 氏名、メールアドレス、新しいパスワードを入力可能に
                    nameInput.readOnly = false;
                    emailInput.readOnly = false;
                    passwordInput.readOnly = false;
                    passwordConfirmInput.readOnly = false;

                    // ボタンの切り替え
                    editButton.textContent = 'キャンセル';
                    saveButton.style.display = 'block'; 
                    saveButton.disabled = false;
                    
                    // フォームの送信先を更新用スクリプトに戻し、バリデーションを有効化
                    form.action = 'update_member.php';
                    form.onsubmit = submitFormHandler; 
                    
                } else {
                    alert('現在のパスワードが正しくありません。');
                    currentPasswordInput.value = ''; 
                    
                    // 認証失敗時、ボタンを元の「編集」に戻す
                    editButton.textContent = '編集';
                }
            })
            .catch(error => {
                editButton.disabled = false; 
                editButton.textContent = '編集'; // エラー時も「編集」に戻す
                console.error('認証エラー:', error);
                alert('認証中にエラーが発生しました。詳細はコンソールを確認してください。');
            });
        }
        
        // フォーム送信時のクライアントサイド バリデーション関数 (変更なし)
        function submitFormHandler(e) {
            if (!isAuthenticated) {
                e.preventDefault();
                return;
            }

            let errors = [];
            // 1. お名前チェック
            if (nameInput.value.trim() === '') {
                errors.push('お名前を入力してください。');
            }

            // 2. メールアドレスチェック
            const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            if (emailInput.value.trim() === '') {
                errors.push('メールアドレスを入力してください。');
            } else if (!emailPattern.test(emailInput.value.trim())) {
                errors.push('メールアドレスの形式が正しくありません。');
            }
            
            // 3. パスワードチェック (入力がある場合のみ)
            const passwordValue = passwordInput.value;
            const confirmValue = passwordConfirmInput.value; 
            
            if (passwordValue !== '' || confirmValue !== '') {
                
                if (passwordValue === '') {
                    errors.push('新しいパスワードを入力してください。');
                }
                if (confirmValue === '') {
                    errors.push('確認用パスワードを入力してください。');
                }

                if (passwordValue !== confirmValue) {
                    errors.push('パスワードと確認用パスワードが一致しません。');
                }
                
                if (passwordValue !== '') {
                    const passwordPattern = /^[a-zA-Z0-9]{8,}$/;
                    if (!passwordPattern.test(passwordValue)) {
                        errors.push('パスワードは8文字以上の半角英数字で入力してください。');
                    }
                }
            }

            if (errors.length > 0) {
                e.preventDefault();
                alert('入力エラーがあります:\n' + errors.join('\n'));
                return false;
            }
            
            return true; // エラーがなければ送信を許可
        }
        
    });
</script>
</body>
</html>