<h2>ダイヤルイン着信設定</h2>

<?php

$msg = "";

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    if($_POST['function'] == 'newadd'){ //新規追加
        if(isset($_POST['didnumber'])){
            $p_didnumber = $_POST['didnumber'];
        }
        if(isset($_POST['target'])){
            $p_target = $_POST['target'];
        }
        if(ctype_digit($p_didnumber)){
            AbspFunctions\put_db_item('ABS/DID', $p_didnumber, $p_target);
            $msg = '';
        } else {
            if($p_didnumber == 'any'){
                AbspFunctions\put_db_item('ABS/DID', $p_didnumber, $p_target);
                $msg = '';
            } else {
                $msg = '番号は数字のみまたはanyで指定';
            }
        }
    } //新規追加

    if($_POST['function'] == 'entdel'){ //一括削除
        $p_maxent = $_POST['numents'];
        for($i=1;$i<=$p_maxent;$i++){
            $index = 'delcb_' . $i;
            if(isset($_POST[$index])){
                $entry = $_POST[$index];
                AbspFunctions\del_db_item('ABS/DID', $entry);
            }
        }
    }

    if($_POST['function'] == 'rgptset'){ //鳴動パターン設定
        $p_rgpt = $_POST['rgpt'];
        AbspFunctions\put_db_item('ABS/DID', 'RGPT', $p_rgpt);
    }

    if($_POST['function'] == 'pfxadd'){ //着信時プレフィクス付加
        $p_apfx = $_POST['apfx'];
        if($p_apfx == '1') AbspFunctions\put_db_item('ABS', 'APF', '1');
        else  AbspFunctions\put_db_item('ABS', 'APF', '0');
    }
   
} // end of POST


//新規追加
$pnam = '';
$target = '';

$selectors = AbspFunctions\create_target_list('group','');

echo <<<EOT
<form action="" method="POST">
  <input type="hidden" name="function" value="newadd">
  <table border=0 class="pure-table">
    <tr>
      <thead>
        <th>着信番号</th>
        <th>着信先</th>
        <th></th>
        <th></th>
      </thead>
    </tr>
    <tr>
      <td nowrap>
        <input class="abs-textinput" type="txt" size="12" name="didnumber" value="$pnam">
      </td>
      <td>
        <select name="target">
          $selectors
        </select>
      </td>
      <td>
        <input type="submit" class={$_(ABSPBUTTON)} value="追加/変更">
      </td>
      <td>
        <font color="#ffe000">
        $msg
        </font>
      </td>
    </tr>
</table>
</form>
番号は数字のみ(ハイフン等を入れない)で指定。<br>
着信番号に'any'を指定するとどんな番号でも着信します。
<br>
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
        <th>着信番号</th>
        <th>着信先</th>
        <th>削除</th>
        <th></th>
      </thead>
    </tr>
EOT;

$entry = AbspFunctions\get_db_family('ABS/DID');

foreach($entry as $line){

    list($pnam, $target) = explode(' : ', $line, 2);
    $pnam = trim($pnam);
    if(!ctype_digit($pnam)){
        if($pnam != 'any') continue;
    }
    $target = trim($target);
    $num_ents = $num_ents + 1;

    if($num_ents % 2 != 0){
        $tr_odd_class = '';
    } else {
        $tr_odd_class = 'class="pure-table-odd"';
    }

echo <<<EOT
    <tr $tr_odd_class>
      <td nowrap>
        <input type="txt" size="12" name="pnam_$num_ents" value="$pnam" readonly>
      </td>
      <td>
        <input type="txt" size="4" name="pname_$num_ents" value="$target" readonly>
      </td>
      <td>
        <input type="checkbox" name="delcb_$num_ents" value="$pnam">
      </td>
      <td>
      </td>
    </tr>
EOT;

} /* end of for */

echo "</table>";
echo "<br>";

echo <<<EOT
<input type="submit" class={$_(ABSPBUTTON)} value="削除">
<input type="hidden" name="numents" value="$num_ents">
</form>
EOT;

//鳴動パターン

    $rgpt_selected = array('1'=>'', '2'=>'', '3'=>'', '4'=>'', '5'=>'');
    $rgpt = AbspFunctions\get_db_item('ABS/DID', 'RGPT');
    if($rgpt == '') $rgpt = '1'; 
    $rgpt_selected["$rgpt"] = "selected";

echo <<<EOT
<br>
<br>
<h3>ダイヤルイン時鳴動パターン</h3>
<form action="" method="post">
  <input type="hidden" name="function" value="rgptset">
  <select name="rgpt">
    <option value="1"  {$rgpt_selected['1']}>1</option>
    <option value="2"  {$rgpt_selected['2']}>2</option>
    <option value="3"  {$rgpt_selected['3']}>3</option>
    <option value="4"  {$rgpt_selected['4']}>4</option>
    <option value="5"  {$rgpt_selected['5']}>5</option>
    </select>
  <input type="submit" class={$_(ABSPBUTTON)} value="設定">
</form>
EOT;

//プレフィクス付加

    $apfx_selected = array('0'=>'','1'=>'');
    $apfx = AbspFunctions\get_db_item('ABS', 'APF');
    $apfx_selected["$apfx"] = "selected";

echo <<<EOT
<br>
<h3 id="tdis">着信時外線捕捉プレフィクス付加</h3>
<font size="-1">
着信時のCIDに外線発信用プレフィクスを付加します<br>
ダイヤルイン時はOGP1、キー着信時は*56xを付加します。<br>
</font>
<form action="" method="POST">
<input type="hidden" name="function" value="pfxadd">
<select name="apfx">
<option value="0" {$apfx_selected['0']}>しない</option>
<option value="1" {$apfx_selected['1']}>する</option>
<input type="submit" class={$_(ABSPBUTTON)} value="設定">
</form>
<br>
EOT;

?>
