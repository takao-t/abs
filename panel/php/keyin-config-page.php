<h2>キー着信設定</h2>

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
            AbspFunctions\put_db_item("ABS/TRUNK/$p_didnumber" ,"KEY", $p_target);
            $msg = '';
        } else {
            if($p_didnumber == 'any'){
                AbspFunctions\put_db_item("ABS/TRUNK/$p_didnumber", "KEY", $p_target);
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
                AbspFunctions\del_db_item("ABS/TRUNK/$entry", 'KEY');
            }
        }
    }

    if($_POST['function'] == 'pfxadd'){ //着信時プレフィクス付加
        $p_apfx = $_POST['apfx'];
        if($p_apfx == '1') AbspFunctions\put_db_item('ABS', 'APF', '1');
        else  AbspFunctions\put_db_item('ABS', 'APF', '0');
        $p_opf57 = $_POST['opf57'];
        AbspFunctions\put_db_item('ABS', 'OPF57', $p_opf57);
    }
   
} // end of POST


//新規追加
$pnam = '';
$target = '';
echo <<<EOT
<form action="" method="POST">
<input type="hidden" name="function" value="newadd">
<table border=0 class="pure-table">
<tr>
<thead>
<th>着信番号</th>
<th>着信キー</th>
<th></th>
<th></th>
</thead>
</tr>
<tr>
</td>
<td nowrap>
<input type=\"txt\" size="12" name="didnumber" value="$pnam">
</td>
<td>
<input type=\"txt\" size="5" name="target" value="$target">
</td>
<td>
<input type="submit" class={$_(ABSPBUTTON)} value="追加/変更">
</td>
<td nowrap>
<font color="red">
$msg
</font>
</td>
</tr>
</table>
</form>
※ 着信キーは単独(1,2,3...)または範囲(1-2,3-4...)を指定
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
<th>着信キー</th>
<th>削除</th>
<th></th>
</thead>
</tr>
EOT;

$entry = AbspFunctions\get_db_family('ABS/TRUNK');

foreach($entry as $line){

    list($pnam, $target) = explode(' : ', $line, 2);
    $pnam = trim($pnam);
    list($pnam, $dummy) = explode('/', $pnam, 2);
    if(!ctype_digit($pnam)){
        if($pnam != 'any') continue;
    }
    $target = trim($target);
    $num_ents = $num_ents + 1;

    if($num_ents % 2 == 0){
        $tr_odd_class = 'class="pure-table-odd"';
    } else {
        $tr_odd_class = '';
    }

echo <<<EOT
<tr $tr_odd_class>
</td>
<td nowrap>
<input type="txt" size="12" name="pnam_$num_ents" value="$pnam" readonly>
</td>
<td>
<input type="txt" size="5" name="pname_$num_ents" value="$target" readonly>
</td>
<td>
<label>
  <input type="checkbox" name="delcb_$num_ents" value="$pnam">
</label>
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

//プレフィクス付加

    $apfx_selected = array('0'=>'','1'=>'');
    $apfx = AbspFunctions\get_db_item('ABS', 'APF');
    $apfx_selected = array('','');
    $apfx_selected["$apfx"] = "selected";
    $opf57_selected = array('','','','','','','');
    $t_opf57 = AbspFunctions\get_db_item('ABS', 'OPF57');
    if($t_opf57 == "") $t_opf57 = 0;
    $opf57_selected[$t_opf57] = 'selected';

echo <<<EOT
<br>
<h3 id="tdis">着信時外線捕捉プレフィクス付加</h3>
<font size="-1">
着信時のCIDに外線発信用プレフィクスを付加します<br>
ダイヤルイン時はOGP1、キー着信時は*56xまたは*571～4を付加します。<br>
(*56は固定キー位置、*57は空きキーオートハント)<br>
</font>
<form action="" method="POST">
<input type="hidden" name="function" value="pfxadd">
<select name="apfx">
<option value="0" {$apfx_selected['0']}>しない</option>
<option value="1" {$apfx_selected['1']}>する</option>
</select>
<select name="opf57">
<option value="0" {$opf57_selected['0']}>*56</option>
<option value="1" {$opf57_selected['1']}>*571</option>
<option value="2" {$opf57_selected['2']}>*572</option>
<option value="3" {$opf57_selected['3']}>*573</option>
<option value="4" {$opf57_selected['4']}>*574</option>
</select>
<input type="submit" class={$_(ABSPBUTTON)} value="設定">
</form>
<br>
EOT;

?>

