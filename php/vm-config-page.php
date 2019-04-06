<h2 id="vmconfig">留守録設定</h2>

<?php
$msg = "";

//音声フォーマット変換
exec('audio/convert.sh');

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    if($_POST['function'] == 'setpin'){
        if(isset($_POST['pin'])){
            $p_pin = $_POST['pin'];
            AbspFunctions\put_db_item('ABS/VM', 'PIN', $p_pin);
        }
    }

    if($_POST['function'] == 'setrpin'){
        if(isset($_POST['rpin'])){
            $p_rpin = $_POST['rpin'];
            AbspFunctions\put_db_item('ABS/VM', 'RPIN', $p_rpin);
        }
    }

} // end of POST


$pin = AbspFunctions\get_db_item('ABS/VM', 'PIN');
$rpin = AbspFunctions\get_db_item('ABS/VM', 'RPIN');

echo <<<EOT
<table border=0 class="pure-table">
  <tr>
    <td>
      <form action="" method="POST">
      <input type="hidden" name="function" value="setpin">
      留守録再生PIN　　
    </td>
    <td>
      <input type="text" size="4" name="pin" value="$pin">
    </td>
    <td>
      <input type="submit" class={$_(ABSPBUTTON)} value="設定">
    </td>
      </form>
  </tr>
</table>
<br>

<table border=0 class="pure-table">
  <tr>
    <td>
      <form action="" method="POST">
      <input type="hidden" name="function" value="setrpin">
      メッセージ録音PIN
    </td>
    <td>
      <input type="text" size="4" name="rpin" value="$rpin">
    </td>
    <td>
      <input type="submit" class={$_(ABSPBUTTON)} value="設定">
    </td>
      </form>
  </tr>
</table>
EOT;

?>

<hr>
<h3>応答音声確認</h3>
<figure>
    <figcaption>再生後切断音声</figcaption>
    <audio controls>
      <source src="audio/abs-tcmessage.mp3" type="audio/mp3">
    </audio>
</figure>

<figure>
    <figcaption>再生後要件録音音声</figcaption>
    <audio controls>
      <source src="audio/abs-tcrmessage.mp3" type="audio/mp3">
    </audio>
</figure>
