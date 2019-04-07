<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="A layout example with a side menu that hides on mobile, just like the Pure website.">
    <title>ABS Panel</title>
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
if(!isset($uinfolocation)){
    include 'php/config.php';
}

$msg_info = '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    if(isset($_POST['username'])) $uname = $_POST['username'];
    else $uname = '';
    if(isset($_POST['password'])) $upass = $_POST['password'];
    else $upass = '';

    $ukey = $uname . ':abspanel:' . $upass;
    $ukey = trim(md5($ukey));

    $userinfo = $uinfolocation . '/' .  'userinfo.dat';
    $user_temp = file_get_contents($userinfo);
    $user_list = explode("\n", $user_temp);

    foreach($user_list as $user_ent){
      $msg_info = '<font color="red">ログイン失敗</font>';
      $udata = json_decode($user_ent);
      if($udata != ''){
        if($udata->name === $uname){
          $ufkey = trim($udata->key);
          if($ufkey === $ukey){
            @session_start();
            $_SESSION['absp_session'] = "logged_in";
            $_SESSION['absp_user'] = $uname;
            if(strpos($_SERVER['HTTP_REFERER'], 'login.php') !== false)
              @header('Location: index.php');
              $msg_info = 'ログインしました';
            } else {
              @http_response_code(403);
              sleep(1);
            }
          }
       }
    }//end foreach
}

header('Content-Type: text/html; charset=UTF-8');

echo <<<EOT
<!DOCTYPE html>
<title>login</title>
<center>
<h3>ABS Panel login</h3>
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
