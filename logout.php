<?php
    session_start();

    $_SESSION = [];

    $session_name = session_name();
    
    if(isset($_COOKIE[$session_name])){
        setcookie($session_name,'',time()-3600);
    }

    session_destroy();

    //index.phpへダイレクト
    header('Location: index.php');
    exit;
?>