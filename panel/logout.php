<?php

require_once 'php/config_session.php';

session_start();

if (isset($_SESSION['username'])) {
    // ABSのファンクション類を読み込み
    require_once 'php/config.php';
    require_once 'php/abscache.php';
    $username = $_SESSION['username'];
    $mycache = new absCache();
    // キャッシュのセッション情報を削除
    $mycache->del("ABS/PANELUSER/{$username}", 'session_id');
    $mycache->del("ABS/PANELUSER/{$username}", 'last_activity');
}

// セッション変数を全て解除する
$_SESSION = [];

// セッションを破壊する
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// ログインページへリダイレクト
header('Location: login.php');
exit;
