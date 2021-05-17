<h2 id="bllist">着信拒否番号管理</h2>

<?php
$msg = "";
$logckd = "";
$alckd = "";

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    if($_POST['function'] == 'newadd'){ //新規追加
        if(isset($_POST['blnumber'])){
            $p_blnumber = $_POST['blnumber'];
        }
        if(ctype_digit($p_blnumber)){
            AbspFunctions\put_db_item('ABS/blocklist', $p_blnumber, '1');
            $msg = '';
        } else {
            $msg = '番号は数字のみで指定';
        }
    } //新規追加

    if($_POST['function'] == 'entdel'){ //一括削除
        $p_maxent = $_POST['numents'];
        for($i=1;$i<=$p_maxent;$i++){
            $index = 'delcb_' . $i;
            if(isset($_POST[$index])){
                $entry = $_POST[$index];
                AbspFunctions\del_db_item('ABS/blocklist', $entry);
            }
        }
    }

    if($_POST['function'] == 'alcont'){ //許可リスト(cidname)のみ着信
        if(isset($_POST['alist'])){
            $p_log = $_POST['alist'];
            if($p_log == 'on'){
                AbspFunctions\put_db_item('ABS/BLC', 'ALIST', '1');
            } else {
                AbspFunctions\del_db_item('ABS/BLC', 'ALIST');
            }
        } else {
            AbspFunctions\del_db_item('ABS/BLC', 'ALIST');
        }
    }

    if($_POST['function'] == 'bllog'){ //拒否ログ設定
        if(isset($_POST['logsw'])){
            $p_log = $_POST['logsw'];
            if($p_log == 'on'){
                AbspFunctions\put_db_item('ABS/BLC', 'LOG', '1');
            } else {
                AbspFunctions\del_db_item('ABS/BLC', 'LOG');
            }
        } else {
            AbspFunctions\del_db_item('ABS/BLC', 'LOG');
        }
    }

    if($_POST['function'] == 'anonupdate'){ //匿名着信設定
        if(isset($_POST['anonopt'])){
            $p_anon = $_POST['anonopt'];
            if($p_anon == 'on'){
                AbspFunctions\put_db_item('ABS', 'ANB', '1');
            } else {
                AbspFunctions\del_db_item('ABS', 'ANB');
            }
        } else {
            AbspFunctions\del_db_item('ABS', 'ANB');
        }
    }

    if($_POST['function'] == 'blccupdate'){ //拒否リスト更新
        if(isset($_POST['blcc'])){
            $p_blcc = $_POST['blcc'];
            if($p_blcc != ''){
                AbspFunctions\put_db_item('ABS', 'BLC', $p_blcc);
            } else {
                AbspFunctions\del_db_item('ABS', 'BLC');
            }
        } else {
            AbspFunctions\del_db_item('ABS', 'BLC');
        }
    }
   
} // end of POST


//新規追加
$pnam = '';
echo <<<EOT
<form action="" method="POST">
  <input type="hidden" name="function" value="newadd">
<table border=0 class="pure-table">
  <tr>
    <thead>
      <th>番号</th>
      <th></th>
      <th></th>
    </thead>
  </tr>
  <tr>
    <td nowrap>
      <input type="txt" size="12" name="blnumber" value="$pnam">
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

//一覧表示
$num_ents = 0;

echo <<<EOT
<form action="" method="POST">
  <input type="hidden" name="function" value="entdel">
<table border=0 class="pure-table">
  <tr>
    <thead>
      <th>番号</th>
      <th>削除</th>
      <th></th>
    </thead>
  </tr>
EOT;

$entry = AbspFunctions\get_db_family('ABS/blocklist');

if($entry != ""){
  foreach($entry as $line){

    list($pnam, $pname) = explode(' : ', $line, 2);
    $pnam = trim($pnam);
    $pname = trim($pname);
    $num_ents = $num_ents + 1;

    if($num_ents % 2 != 0){
        $tr_odd_class = '';
    } else {
        $tr_odd_class = 'class="pure-table-odd"';
    }
        

echo <<<EOT
拒否中番号一覧
  <tr $tr_odd_class>
    <td nowrap>
      <input type="txt" size="12" name="pnam_$num_ents" value="$pnam" readonly>
    </td>
    <td>
      <input type="checkbox" name="delcb_$num_ents" value="$pnam">
    </td>
    <td>
    </td>
  </tr>
EOT;
  }
} /* end of for */

echo "</table>";

echo <<<EOT
<input type="submit" class={$_(ABSPBUTTON)} value="削除実行">
<input type="hidden" name="numents" value="$num_ents">
</form>
EOT;

    $anonckd = '';
    $anb = AbspFunctions\get_db_item('ABS', 'ANB');
    if($anb == '1') $anonckd="checked=\"checked\"";

    $logsw = '';
    $logsw = AbspFunctions\get_db_item('ABS/BLC', 'LOG');
    if($logsw == '1') $logckd="checked=\"checked\"";

    $alsw = '';
    $alsw = AbspFunctions\get_db_item('ABS/BLC', 'ALIST');
    if($alsw == '1') $alckd="checked=\"checked\"";

    $blcc = '';
    $blcc = AbspFunctions\get_db_item('ABS', 'BLC');

echo <<<EOT
<br>
<br>
<table class="pure-table">
  <tr>
    <td width="200">
      <h3 id="bllog">許可リストのみ着信</h3>
    </td>
    <td>
      <form action="" method="post">
      <input type="hidden" name="function" value="alcont">
      <input type="checkbox" name="alist" value="on" $alckd>
      <input type="submit" class={$_(ABSPBUTTON)} value="設定">
      </form>
    </td>
  </tr>
</table>
＊発信者名(CID)に登録されているもののみ着信します
<br>
<br>

<table class="pure-table">
  <tr>
    <td width="200">
      <h3 id="bllog">着信拒否記録</h3>
    </td>
    <td>
      <form action="" method="post">
      <input type="hidden" name="function" value="bllog">
      <input type="checkbox" name="logsw" value="on" $logckd>
      <input type="submit" class={$_(ABSPBUTTON)} value="設定">
      </form>
    </td>
  </tr>
</table>
<br>

<table class="pure-table">
  <tr>
    <td width="200">
      <h3 id="anon">匿名(anonymous)着信拒否</h3>
    </td>
    <td>
      <form action="" method="post">
      <input type="hidden" name="function" value="anonupdate">
      <input type="checkbox" name="anonopt" value="on" $anonckd>
      <input type="submit" class={$_(ABSPBUTTON)} value="設定">
      </form>
    </td>
  </tr>
</table>
<br>

<h3 id="blcontext">着信拒否時カスタムcontext</h3>
<form action="" method="post">
<input type="hidden" name="function" value="blccupdate">
<input type="txt" size="24" name="blcc" value="$blcc">
</label>
<input type="submit" class={$_(ABSPBUTTON)} value="設定">
</form>
<br>
指定時にはcontextを作成すること。未指定時は回線切断。<br>
飛び先はcontext,s,1の型式。ここでの指定はcontextのみ。
EOT;

?>
