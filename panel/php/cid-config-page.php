<h2>発信者名(CID:着信時)管理</h2>

<?php
$msg = "";

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    if($_POST['function'] == 'newadd'){ //新規追加
        if(isset($_POST['cidnumber'])){
            $p_cidnumber = $_POST['cidnumber'];
        }
        if(isset($_POST['cidname'])){
            $p_cidname = $_POST['cidname'];
        }
        if(ctype_digit($p_cidnumber)){
            AbspFunctions\put_db_item('cidname', $p_cidnumber, $p_cidname);
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
                AbspFunctions\del_db_item('cidname', $entry);
            }
        }
    }
   
} // end of POST


//新規追加
if(isset($_POST['post_pnum'])){
    $pnum = trim($_POST['post_pnum']);
    $pname = AbspFunctions\get_db_item('cidname', $pnum);
    $ret = AbspFunctions\get_db_item('ABS/blacklist', $pnum);
    if($ret == '1'){
        $pname = '着信拒否中';
    }
} else {
    $pnum = '';
    $pname = '';
}

echo <<<EOT
<form action="" method="POST">
  <input type="hidden" name="function" value="newadd">
<table border=0 class="pure-table">
  <tr>
    <thead>
      <th>番号</th>
      <th>名称(CIDname)</th>
      <th></th>
      <th></th>
    </thead>
  </tr>
  <tr>
    <td nowrap>
      <input type="txt" size="12" name="cidnumber" value="$pnum">
    </td>
    <td>
      <input type="txt" size="16" name="cidname" value="$pname">
    </td>
    <td>
      <input type="submit" class={$_(ABSPBUTTON)} value="追加/変更">
    </td>
    <td>
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
      <th>名称(CIDname)</th>
      <th>削除</th>
      <th></th>
    </thead>
  </tr>
EOT;

$entry = AbspFunctions\get_db_family('cidname');

if($entry != ""){
  foreach($entry as $line){

    list($pnum, $pname) = explode(' : ', $line, 2);
    $puam = trim($pnum);
    $pname = trim($pname);
    $num_ents = $num_ents + 1;

    if($num_ents % 2 != 0){
        $tr_odd_class = '';
    } else {
        $tr_odd_class = 'class="pure-table-odd"';
    }

echo <<<EOT
  <tr $tr_odd_class>
    <td nowrap>
      <input type="txt" size="12" name="pnum_$num_ents" value="$pnum" readonly>
    </td>
    <td>
      <input type="txt" size="16" name="pname_$num_ents" value="$pname" readonly>
    </td>
    <td>
      <input type="checkbox" name="delcb_$num_ents" value="$pnum">
    </td>
    <td>
    </td>
  </tr>
EOT;
  }
} /* end of for */

echo "</table>";
echo "<br>";

echo <<<EOT
<input type="submit" class={$_(ABSPBUTTON)} value="削除">
<input type="hidden" name="numents" value="$num_ents">
</form>
EOT;

?>
