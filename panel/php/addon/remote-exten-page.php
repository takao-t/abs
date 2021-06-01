<h2 id="bllist">リモート内線設定</h2>

<?php
$msg = "";

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    if($_POST['function'] == 'newadd'){ //新規追加
        if(isset($_POST['localexten'])){
            $p_localexten = trim($_POST['localexten']);
        }
        if(isset($_POST['iopnum'])){
            $p_iopnum = trim($_POST['iopnum']);
        }
        if(isset($_POST['iopexten'])){
            $p_iopexten = trim($_POST['iopexten']);
        }
        if(!ctype_digit($p_localexten)){
            $msg = '内線番号不正';
        }
        else if(!ctype_digit($p_iopnum)){
            $msg = '拠点番号不正';
        }
        else if(!ctype_digit($p_iopexten)){
            $msg = '相手内線番号不正';
        }
        else {
            $c_exten = AbspFunctions\get_db_item('ABS/EXT',$p_localexten);
            if($c_exten != ""){
                $msg = '内線重複';
            }
            else {
                $remote_exten = 'R' . $p_iopnum . $p_iopexten;
                echo $remote_exten;
                AbspFunctions\put_db_item('ABS/EXT', $p_localexten, $remote_exten);
                $msg = '登録完了';
            }
        }
    } //新規追加

    if($_POST['function'] == 'entdel'){ //一括削除
        $p_maxent = $_POST['numents'];
        for($i=1;$i<=$p_maxent;$i++){
            $index = 'confirm_' . $i;
            if(isset($_POST[$index])){
                $entry = $_POST[$index];
                AbspFunctions\del_db_item('ABS/EXT', $entry);
            }
        }
    }

} // end of POST


//新規追加

$entry = AbspFunctions\get_db_family('ABS/IOP');
$here  = AbspFunctions\get_db_item('ABS/IOP','HERE');
$here = trim($here);

$i = 0;

$select_options = '';

foreach($entry as $line){
    if(strpos($line, "/NAME") === false) continue;

    list($iopnum ,$line2) = explode("/", $line);
    list($dummy, $iopname) = explode(":", $line2);
    $iopnum = trim($iopnum);
    $iopname = trim($iopname);

    if(strcmp($iopnum ,$here) == 0) continue;

    $select_options .= "<option value=$iopnum>" . $iopname . "($iopnum)" . "</option>";

}

echo <<<EOT
<form action="" method="POST">
 <input type="hidden" name="function" value="newadd">
<table border=0 class="pure-table">
  <tr>
    <thead>
      <th>内線番号</th>
      <th>相手拠点</th>
      <th>相手内線番号</th>
      <th></th>
      <th></th>
    </thead>
  </tr>
  <tr>
    <td nowrap>
      <input type="txt" size="4" name="localexten" value="">
    </td>
    <td>
      <select name="iopnum" value="">
      $select_options
      </select>
    </td>
    <td>
      <input type="txt" size="4" name="iopexten" value="">
    </td>
    <td>
      <input type="submit" class={$_(ABSPBUTTON)} value="追加">
    </td>
    <td nowrap>
      <font color="red">
        $msg
      </font>
    </td>
  </tr>
</table>
</form>
<br>
EOT;

//一覧表示と削除
$numents = 0;

echo "登録済リモート内線";
echo <<<EOT
<form action="" method="POST">
  <input type="hidden" name="function" value="entdel">
<table border=0 class="pure-table">
  <tr>
    <thead>
      <th>内線番号</th>
      <th>拠点番号</th>
      <th>相手内線番号</th>
      <th>削除</th>
    </thead>
  </tr>
EOT;

$entry = AbspFunctions\get_db_family('ABS/EXT');
$iop_digits = AbspFunctions\get_db_item('ABS/IOP','DIGITS');

$numents = 0;
$tr_odd_class ='';

foreach($entry as $line){
    if(strpos($line, "/") !== false) continue;

    list($exten, $peer) = explode(":", $line);
    $exten = trim($exten);
    $peer = trim($peer);
    if(strpos($peer, "R") !== 0) continue;

    $iop_site = substr($peer, 1, $iop_digits);
    $iop_exten = substr($peer, $iop_digits+1);

    $numents = $numents + 1;

echo <<<EOT
  <tr $tr_odd_class>
    <td nowrap>
        <input type="txt" size="4" name="exten_$numents" value="$exten" readonly>
    </td>
    <td>
        $iop_site
    </td>
    <td>
        $iop_exten
    </td>
    <td>
        <input type="checkbox" name="confirm_$numents" value="$exten">
    </td>
  </tr>
EOT;

   if($numents % 2 == 0){
       $tr_odd_class = '';
   } else {
       $tr_odd_class = 'class="pure-table-odd"';
   }

} /* end of foreach */

echo "</table>";

echo <<<EOT
<input type="submit" class={$_(ABSPBUTTON)} value="削除実行">
<input type="hidden" name="numents" value="$numents">
</form>
EOT


?>
