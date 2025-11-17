<?php
    require_once('helpers/MemberDAO.php');
    session_start();

    // 1. 管理者でなければリダイレクト
    if (empty($_SESSION['member']) || $_SESSION['member']->is_member != 1) {
        header('Location: admin_login.php');
        exit;
    }

    $memberDAO = new MemberDAO();

    // 2. admin_dashboard.php からフィルタ条件を受け取る
    $filter_status = $_GET['filter_status'] ?? 'all';
    $filter_keyword = $_GET['filter_keyword'] ?? '';

    // 3. DAOを使い、フィルタリングされた会員データを取得
    $members = $memberDAO->get_members_by_filter($filter_status, $filter_keyword);

    // 4. CSVダウンロード用のHTTPヘッダーを送信
    $filename = "members_export_" . date("Ymd_His") . ".csv";
    
    // 文字コードをUTF-8に指定（Excelでの文字化け対策）
    header('Content-Type: text/csv; charset=utf-8');
    // ファイル名を指定してダウンロードダイアログを強制
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    // 5. CSVデータを生成
    
    // 出力ストリームを開く
    $output = fopen('php://output', 'w');
    
    // Excelでの文字化けを防ぐためのBOM（Byte Order Mark）を先頭に追加
    fprintf($output, "\xEF\xBB\xBF");

    // ヘッダー行（CSVの1行目）を書き込む
    $header_row = ["会員ID", "氏名", "メールアドレス", "権限"];
    fputcsv($output, $header_row);

    // 6. データ行をループで書き込む
    foreach ($members as $member) {
        // 権限 (is_member の 1/0 を '管理者'/'一般' に変換)
        $status_text = ($member->is_member == 1) ? 'システム管理者' : '一般ユーザー';
        
        $row = [
            $member->member_id,
            $member->member_name,
            $member->member_email,
            $status_text
        ];
        
        // 1行分のデータをCSV形式で書き込む
        fputcsv($output, $row);
    }

    // ストリームを閉じる
    fclose($output);
    
    // 7. スクリプトを終了 (これ以降、余計なHTMLなどが出力されないように)
    exit;
?>