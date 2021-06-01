<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QPM Login</title>
        <link rel="stylesheet" href="css/pure-min.css">
        <!--[if lte IE 8]>
            <link rel="stylesheet" href="css/layouts/side-menu-old-ie.css">
        <![endif]-->
        <!--[if gt IE 8]><!-->
            <link rel="stylesheet" href="css/layouts/side-menu.css">
        <!--<![endif]-->
</head>
<body>

<div>
    <style scoped>

        .absp-button1 {
            color: white;
            border-radius: 6px;
            font-size: 85%;
            text-shadow: 0 1px 1px rgba(0, 0, 0, 0.2);
            background: rgb(28, 184, 65);
        }

    </style>
</div>

<?php
include 'dbfunctions.php';

$msg_info = '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    if(isset($_POST['username'])) $uname = $_POST['username'];
    else $uname = '';
    if(isset($_POST['password'])) $upass = $_POST['password'];
    else $upass = '';

    $ukey = $uname . ':abspanel:' . $upass;
    $ukey = trim(md5($ukey));

    $db_pass_key = DBFUNC\get_db_password($uname);
    $user_name = DBFUNC\u_login_name($uname);

    $msg_info = '<font color="red">ログイン失敗</font>';
    if($ukey === $db_pass_key){
        ini_set('session.gc_maxlifetime', 28800);
        ini_set('session.save_path', SESSIONPATH);
        session_set_cookie_params(43200);
        @session_start();
        $_SESSION['qpm_session'] = "logged_in";
        $_SESSION['qpm_user'] = $uname;
        $_SESSION['qpm_user_name'] = $user_name;
        if(strpos($_SERVER['HTTP_REFERER'], 'login.php') !== false)
          @header('Location: index.php');
          $msg_info = 'ログインしました';
    } else {
        @http_response_code(403);
        sleep(1);
    }
}

header('Content-Type: text/html; charset=UTF-8');

echo <<<EOT
<!DOCTYPE html>
<title>QMP login</title>
<center>
<h3>QPM login</h3>
<table border=0 class="pure-table">
<form method="post" action="">
  <tr>
    <td>
      Username :
    </td>
    <td>
      <input type="text" name="username" value="">
    </td>
  </tr>
  <tr>
   <td>
      Password : 
   </td>
   <td>
      <input type="password" name="password" value="">
   </td>
  </tr>
  <tr>
    <td></td>
    <td>$msg_info</td>
  </tr>
  <tr>
    <td>
    </td>
    <td align="right">
      <input type="hidden" name="token" value="<?=h(generate_token())?>">
      <input type="submit" value="login">
    </td>
  </tr>
</form>
</table>
</center>
EOT;

?>

</body>
</html>
