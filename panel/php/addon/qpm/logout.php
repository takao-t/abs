<h3>ログアウト</h3>

<?php
include 'config.php';

ini_set('session.save_path', SESSIONPATH);
@session_start();
setcookie(session_name(), '', 1);
session_destroy();
$home = 'Location: ' . 'login.php';
header($home);

?>
