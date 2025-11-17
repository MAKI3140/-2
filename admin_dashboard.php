<?php
    require_once('helpers/MemberDAO.php');
    session_start();

    // 管理者（is_member == 1）でなければ、ログインページへリダイレクト
    if (empty($_SESSION['member']) || $_SESSION['member']->is_member != 1) {
        header('Location: admin_login.php');
        exit;
    }

    $memberDAO = new MemberDAO();
    $current_admin_id = $_SESSION['member']->member_id; // ログイン中の管理者ID

    // --- ユーザー削除処理 ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
        $delete_id = (int)$_POST['delete_id'];
        
        if ($delete_id !== $current_admin_id) {
            $memberDAO->delete_member_by_id($delete_id);
        }
    }
    
    // --- フィルタリングと一覧取得 ---
    $filter_status = $_GET['filter_status'] ?? 'all'; 
    $filter_keyword = $_GET['filter_keyword'] ?? '';   

    $all_members = $memberDAO->get_members_by_filter($filter_status, $filter_keyword);
    
    $total_members = count($all_members);

    // ▼▼▼ ここから追加 ▼▼▼
    // CSVエクスポート用のクエリ文字列（URLパラメータ）を作成
    $query_params = [
        'filter_status' => $filter_status,
        'filter_keyword' => $filter_keyword
    ];
    $csv_query_string = http_build_query($query_params);
    // ▲▲▲ ここまで追加 ▲▲▲

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理ダッシュボード - ユーザー</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="admin-dash-body">

    <div class="admin-dash-container">
        
        <header class="admin-dash-header">
            <h2>ユーザー</h2>
            <div class="admin-header-actions">
                <a href="admin_user_new.php" class="admin-button-primary">
                    <i class="fa-solid fa-plus"></i> 新しいユーザー
                </a>
                <a href="logout.php" class="admin-button-danger">
                    <i class="fa-solid fa-right-from-bracket"></i> ログアウト
                </a>
            </div>
        </header>

        <form action="admin_dashboard.php" method="GET" class="admin-filter-box">
            <div class="admin-filter-row">
                <span class="admin-filter-label">
                    <i class="fa-solid fa-filter"></i> フィルタ
                </span>
                <select name="filter_status">
                    <option value="all" <?= $filter_status == 'all' ? 'selected' : '' ?>>すべての権限</option>
                    <option value="admin" <?= $filter_status == 'admin' ? 'selected' : '' ?>>システム管理者</option>
                    <option value="user" <?= $filter_status == 'user' ? 'selected' : '' ?>>一般ユーザー</option>
                </select>
                <input type="text" name="filter_keyword" class="admin-filter-keyword" placeholder="名前またはメールアドレス" value="<?= htmlspecialchars($filter_keyword) ?>">
                <button type="submit" class="admin-button-secondary">
                    <i class="fa-solid fa-magnifying-glass"></i> 検索
                </button>
            </div>
        </form>

        <div class="admin-table-container">
            <table>
                <thead>
                    <tr>
                        <th><input type="checkbox"></th>
                        <th>ログインID / メールアドレス</th>
                        <th>氏名</th>
                        <th>システム管理者</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($all_members)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 20px;">
                                該当するユーザーが見つかりません。
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($all_members as $member): ?>
                    <tr>
                        <td><input type="checkbox"></td>
                        <td><?= htmlspecialchars($member->member_email) ?></td>
                        <td><?= htmlspecialchars($member->member_name) ?></td>
                        <td>
                            <?php if ($member->is_member == 1): ?>
                                <span class="admin-badge-success">はい</span>
                            <?php else: ?>
                                <span class="admin-badge-default">いいえ</span>
                            <?php endif; ?>
                        </td>
                        <td class="admin-action-cell">
                            <div class="admin-action-dropdown">
                                <button class="admin-button-icon-menu"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                                <div class="admin-dropdown-content">
                                    <a href="#">編集</a>
                                    <?php if ($member->member_id !== $current_admin_id): ?>
                                        <form action="admin_dashboard.php" method="POST" onsubmit="return confirm('本当に削除しますか？');">
                                            <input type="hidden" name="delete_id" value="<?= $member->member_id ?>">
                                            <button type="submit" class="admin-dropdown-item-delete">削除</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <footer class="admin-dash-footer">
            <div class="admin-pagination">
                (全 <?= $total_members ?> 件)
            </div>
            <div class="admin-export">
                他の形式にエクスポート: 
                <a href="admin_export_csv.php?<?= htmlspecialchars($csv_query_string) ?>">CSV</a>
                </div>
        </footer>

    </div>
</body>
</html>