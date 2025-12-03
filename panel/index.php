<?php

require_once 'php/config_session.php';

ob_start();

define('ABS_PANEL_INCLUDED', true);

// 最初に設定ファイルと関数ライブラリを読み込む
require_once 'php/config.php';
require_once 'php/astman.php';
require_once 'php/functions.php';
require_once 'php/abscache.php';

$mycache = new absCache();

// AstDBからセッションタイムアウト値を取得
$session_timeout_minutes = AbspFunctions\get_db_item('ABS/PANEL', 'SESSION_TIMEOUT');
if (empty($session_timeout_minutes) || !ctype_digit((string)$session_timeout_minutes)) {
    $session_timeout_minutes = 30;
}
$session_lifetime_seconds = $session_timeout_minutes * 60;

// PHPのセッション設定
ini_set('session.gc_maxlifetime', $session_lifetime_seconds);
ini_set('session.cookie_lifetime', $session_lifetime_seconds);

session_start();

// ログイン状態の各種チェック
if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true) {
    $username = $_SESSION['username'];
    $current_session_id = session_id();
    $stored_session_id = $mycache->get("ABS/PANELUSER/{$username}", 'session_id');

    if (!empty($stored_session_id) && $stored_session_id !== $current_session_id) {
        session_unset(); session_destroy(); header('Location: login.php?reason=concurrent_login'); exit;
    }
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $session_lifetime_seconds) {
        $mycache->del("ABS/PANELUSER/{$username}", 'session_id');
        $mycache->del("ABS/PANELUSER/{$username}", 'last_activity');
        session_unset(); session_destroy(); header('Location: login.php?reason=session_expired'); exit;
    }
    $_SESSION['last_activity'] = time();
    $mycache->set("ABS/PANELUSER/{$username}", 'last_activity', time());
} else {
    header('Location: login.php');
    exit;
}

// ACL (Access Control List) 機能
function ip_in_cidr(string $ip, string $cidr): bool {
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) || strpos($cidr, '/') === false) { return false; }
    list($subnet, $mask) = explode('/', $cidr, 2);
    if (!filter_var($subnet, FILTER_VALIDATE_IP) || !ctype_digit($mask) || $mask < 0 || $mask > 32) { return false; }
    return (@ip2long($ip) & ~((1 << (32 - $mask)) - 1)) == @ip2long($subnet);
}

$acl_string = AbspFunctions\get_db_item('ABS/PANEL', 'ACL');
$is_access_allowed = empty($acl_string) ? true : false; // ACLに何も設定されていない場合は許可

if (!$is_access_allowed) { // ACLの設定がある場合には許可アドレスをチェック
    $allowed_list = explode(',', $acl_string);
    $client_ip = $_SERVER['REMOTE_ADDR'];
    foreach ($allowed_list as $entry) {
        $entry = trim($entry);
        if (empty($entry)) continue;
        if ((strpos($entry, '/') !== false && ip_in_cidr($client_ip, $entry)) || $client_ip === $entry) {
            $is_access_allowed = true;
            break;
        }
    }
}

// ページの決定とコンテンツの準備
require_once 'menu-structure.php';

// 許可するページリストを生成
$allowed_pages = ['top'];
// 1. メニュー構造から全ページのリストを抽出
array_walk_recursive($menuConfig['structure'], function($value, $key) use (&$allowed_pages) {
    if ($key === 'page') {
        $allowed_pages[] = $value;
    }
});
// 2. 非メニューの許可ページを追加
$allowed_pages = array_merge($allowed_pages, $menuConfig['allowed_non_menu_pages']);
// 3. 重複を削除してリストをクリーンにする
$allowed_pages = array_unique($allowed_pages);


// 許可するヘルプファイルのリスト
$allowed_helps = $menuConfig['allowed_non_menu_helps'];


// アクセス許可(ACL)の結果に基づいて処理
if (!$is_access_allowed) { //ACLにない場合
    $currentPage = 'access_denied';
    $content_to_include = 'pages/access_denied.php';
} else { //ACLにある場合
    if (isset($_GET['help'])) {
        $currentPage = 'top'; // help表示時はメニューをアクティブにしない
        $help_file = $_GET['help'];
        $content_to_include = in_array($help_file, $allowed_helps) ? 'help/' . $help_file : 'pages/top.php';

    } else {
        $currentPage = $_GET['page'] ?? 'top';
        if (in_array($currentPage, $allowed_pages)) {
            $page_file = 'pages/' . $currentPage . '.php';
            $content_to_include = file_exists($page_file) ? $page_file : 'pages/top.php';
        } else {
            $currentPage = 'top';
            $content_to_include = 'pages/top.php';
        }
    }
}

// 特定ページのAstDBによるアクセス制御
if (isset($menuConfig['restricted_pages'][$currentPage])) {
    $db_key = $menuConfig['restricted_pages'][$currentPage];
    list($family, $key) = explode('/', $db_key, 2);
    
    // AstDBをチェックし、値が'YES'ならアクセスを拒否
    if (AbspFunctions\get_db_item($family, $key) === 'YES') {
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => '要求されたページの機能は現在無効になっています。'];
        header('Location: index.php?page=top');
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <meta charset="UTF-8">
    <title>ABSコントロールパネル</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <nav class="menu">
            <?php 
            // menu.phpに menuStructure 変数を渡す
            include 'menu.php'; 
            ?>
        </nav>
        <main class="content">
            <?php include $content_to_include; ?>
        </main>
    </div>
    <script src="switch-theme.js"></script>
    <script src="main.js"></script>
    <div id="menu-toggle-btn" title="メニューを切り替え">
        <span class="arrow"></span>
    </div>
</body>
