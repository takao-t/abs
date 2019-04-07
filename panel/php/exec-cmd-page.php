<h3>コマンド実行</h3>

<?php

$msg = '';
///
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $p_command = $_POST['command'];
    $msg = AbspFunctions\exec_cli_command($p_command);
    $msg = str_replace('Output: ', '<br>', $msg);
    $msg = str_replace('Responce: ', '<br>Responce: ', $msg);
    $msg = str_replace('Message: ', '<br>Message: ', $msg);
}

///


echo <<<EOT
<font color="red">コマンドは十分注意して実行してください(特にcore,db系)</font><br>
<form action="" method="POST">
*CLI> 
<input type="txt" size="40" name="command" value="">
<input type="submit" class={$_(ABSPBUTTON)} value="実行">
</form>
$msg
EOT;
?>
