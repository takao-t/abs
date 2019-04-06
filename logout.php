<h3>ログアウト</h3>

<?php

@session_start();
setcookie(session_name(), '', 1);
session_destroy();
$home = 'Location: ' . 'login.php';
header($home);

?>
