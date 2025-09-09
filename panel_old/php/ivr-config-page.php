<h2>IVR設定</h2>

<?php
//音声フォーマット変換
exec('audio/convert.sh abs-ivrmenu > /dev/null 2>&1');

/* IVRメニュー一覧 */
/* 注意:ivr-item1はextensions_ivr.confのcontextに対応する */
$ivr_selection = array(
    "ivr-item1" => "通常着信処理",
    "ivr-item2" => "特定内線着信",
    "ivr-item3" => "特定キー着信",
    "ivr-item4" => "留守番録音",
    "ivr-item5" => "保留音",
    "ivr-item6" => "エコーバック",
    "ivr-item7" => "音声再生",
    "ivr-item8" => "FAX受信(サンプル)",
    "ivr-item9" => "カスタム1",
    "ivr-item10" => "カスタム2",
    "ivr-item11" => "カスタム3",
    "ivr-item12" => "カスタム4",
);

//セレクション生成
$cnt=1;
$ivr_opt_list = "";
foreach($ivr_selection as $ivrent){
    $item_num = "ivr-item" . $cnt;
    $opt_ent = "<option value=\"" . $item_num . "\">" . $ivr_selection[$item_num] . "</option>\n";
    $ivr_opt_list .= $opt_ent;
    $cnt++;
}

$msg = "";

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    if($_POST['function'] == 'newadd'){ //番号新規追加
        if(isset($_POST['ivrnumber'])){
            $p_ivrnumber = $_POST['ivrnumber'];
        }
        if(isset($_POST['target'])){
            $p_target = $_POST['target'];
        }
        if(isset($_POST['ivrtype'])){
            $p_ivrtype = $_POST['ivrtype'];
        }
        if(isset($_POST['ivritem'])){
            $p_ivritem = $_POST['ivritem'];
        } else {
            $p_ivritem = "";
        }
        if(isset($_POST['ivrvalue'])){
            $p_ivrvalue = $_POST['ivrvalue'];
        } else {
            $p_ivrvalue = "";
        }
        if(ctype_digit($p_ivrnumber)){
            if(($p_ivrtype == "direct")&($p_ivritem != "")){
                AbspFunctions\put_db_item("ABS/IVR/NUM" , $p_ivrnumber, "YES");
                AbspFunctions\put_db_item("ABS/IVR/DIR/$p_ivrnumber" , "CTX", $p_ivritem);
                AbspFunctions\put_db_item("ABS/IVR/DIR/$p_ivrnumber" , "VAL", $p_ivrvalue);
            } else {
                AbspFunctions\put_db_item("ABS/IVR/NUM" , $p_ivrnumber, "YES");
                AbspFunctions\del_db_tree("ABS/IVR/DIR/$p_ivrnumber");
            }
            $msg = '';
        } else {
            if($p_ivrnumber == 'any'){
                AbspFunctions\put_db_item("ABS/IVR/NUM" , $p_ivrnumber, "YES");
                $msg = '';
            } else {
                $msg = '番号は数字のみまたはanyで指定';
            }
        }
    } //新規追加

    if($_POST['function'] == 'entdel'){ //番号一括削除
        $p_maxent = $_POST['numents'];
        for($i=1;$i<=$p_maxent;$i++){
            $index = 'delcb_' . $i;
            if(isset($_POST[$index])){
                $entry = $_POST[$index];
                AbspFunctions\del_db_item("ABS/IVR/NUM", $entry);
                AbspFunctions\del_db_tree("ABS/IVR/DIR/$entry");
            }
        }
    }

    if($_POST['function'] == 'menudel'){ //メニュー一括削除
        $p_maxent = $_POST['numents'];
        for($i=1;$i<=$p_maxent;$i++){
            $index = 'delcb_' . $i;
            if(isset($_POST[$index])){
                $entry = $_POST[$index];
                AbspFunctions\del_db_tree("ABS/IVR/MENU/$entry");
            }
        }
    }

    if($_POST['function'] == 'menuadd'){ //IVRメニュー追加
        $p_tone = trim($_POST['tone']);
        $p_ivritem = trim($_POST['ivritem']);
        $p_ivrvalue = trim($_POST['ivrvalue']);
        if($p_ivritem != ""){
            AbspFunctions\put_db_item("ABS/IVR/MENU/$p_tone" , "CTX", $p_ivritem);
            AbspFunctions\put_db_item("ABS/IVR/MENU/$p_tone" , "VAL", $p_ivrvalue);
        }
    }

    if($_POST['function'] == 'sftimeset'){ //セーフティタイマ設定
        if(isset($_POST['sfts'])){
            $p_sftim = trim($_POST['sfts']);
            if(!ctype_digit($p_sftim)) $p_sftim = "300";
            if($p_sftim < 0) $p_sftim = "300";
        } else {
            $p_sftim = "300";
        }
        AbspFunctions\put_db_item("ABS/IVR" , "TIM", $p_sftim);
    }

    if($_POST['function'] == 'rpinset'){ //録音PIN設定
        if(isset($_POST['rpin'])){
            $p_rpin = trim($_POST['rpin']);
            AbspFunctions\put_db_item("ABS/IVR" , "RPIN", $p_rpin);
        }
    }

} // end of POST


//番号新規追加
$pnum = '';
$target = '';
echo <<<EOT
<h3>IVR処理番号</h3>
IVR番号追加(この番号にマッチする着信先のみIVR処理)
<form action="" method="POST">
<input type="hidden" name="function" value="newadd">
<table border=0 class="pure-table">
<tr>
<thead>
<th>着信番号</th>
<th>種別</th>
<th>処理</th>
<th>設定値</th>
<th></th>
<th></th>
</thead>
</tr>
<tr>
</td>
<td nowrap>
<input type=\"txt\" size="12" name="ivrnumber" value="$pnum">
</td>
<td>
<select name="ivrtype">
<option value="ivr">IVR
<option value="direct">ダイレクト 
</select>
</td>
<td>
<select name="ivritem">
<option value=""></option>
$ivr_opt_list
</select>
</td>
<td>
<input type="txt" size="8" name="ivrvalue" value="">
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
注意:"any"を指定した場合、他の番号は無視されます。
<br>
<br>
EOT;

//一覧表示
$num_ents = 0;

echo <<<EOT
IVR着信番号一覧
<form action="" method="POST">
<input type="hidden" name="function" value="entdel">
<table border=0 class="pure-table">
<tr>
<thead>
<th>着信番号</th>
<th>種別</th>
<th>処理</th>
<th>設定値</th>
<th>削除</th>
<th></th>
</thead>
</tr>
EOT;

$entry = AbspFunctions\get_db_family('ABS/IVR/NUM');

if(is_array($entry)){
  foreach($entry as $line){

    list($pnum, $flag) = explode(' : ', $line, 2);
    $pnum = trim($pnum);
    $flag = trim($flag);
    $ivritem = AbspFunctions\get_db_item("ABS/IVR/DIR/$pnum", 'CTX');
    if($ivritem != ""){
        $ivtype = "ダイレクト";
        $ivctx = $ivr_selection[$ivritem];
        $ivval = AbspFunctions\get_db_item("ABS/IVR/DIR/$pnum", 'VAL');
    } else {
        $ivtype = "IVR";
        $ivctx = "";
        $ivval = "";
    }

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
$pnum
</td>
<td>
$ivtype
</td>
<td>
$ivctx
</td>
<td>
$ivval
</td>
<td>
<label>
  <input type="checkbox" name="delcb_$num_ents" value="$pnum">
</label>
</td>
<td>
</td>
</tr>
EOT;
  } /* end of foreach */

} /* is_array  */
echo "</table>";
echo "<br>";

echo <<<EOT
<input type="submit" class={$_(ABSPBUTTON)} value="削除">
<input type="hidden" name="numents" value="$num_ents">
</form>
EOT;

/* メニュー設定部 */
echo <<<EOT
<br>
<hr>
<h3>IVRメニュー項目設定</h3>
EOT;

$num_ents = 0;

echo <<<EOT
設定済IVRメニュー一覧
<form action="" method="POST">
<input type="hidden" name="function" value="menudel">
<table border=0 class="pure-table">
<tr>
<thead>
<th>トーン</th>
<th>処理</th>
<th>設定値</th>
<th>削除</th>
</thead>
</tr>
EOT;

$num_ents = 0;
//check tone 0 to 9
for($i=0;$i<10;$i++){
  $target = "ABS/IVR/MENU/" . $i;
  $entry = AbspFunctions\get_db_family($target);

  if(is_array($entry)){
    foreach($entry as $line){

      $tone = $i;
      list($tag, $val) = explode(' : ', $line, 2);
      $tag = trim($tag);
      $val = trim($val);
      if($tag == "CTX") $context = $ivr_selection[$val];
      if($tag == "VAL") $param = $val;
      else $param ="";

    } /* end of foreach */

  $num_ents = $num_ents + 1;

  if($num_ents % 2 == 0){
    $tr_odd_class = 'class="pure-table-odd"';
  } else {
    $tr_odd_class = '';
  }

echo <<<EOT
<tr $tr_odd_class>
</td>
<td>
$tone
</td>
<td>
$context
</td>
<td>
$param
</td>
<td>
<label>
  <input type="checkbox" name="delcb_$num_ents" value="$i">
</label>
</td>
</tr>
EOT;

  } /* is_array  */
}/*/ end of for */

echo "</table>";
echo "<br>";

echo <<<EOT
<input type="submit" class={$_(ABSPBUTTON)} value="削除">
<input type="hidden" name="numents" value="$num_ents">
</form>
<br>
EOT;

//メニュー新規追加
$pnum = '';
$target = '';
echo <<<EOT
IVRメニュー項目追加
<form action="" method="POST">
<input type="hidden" name="function" value="menuadd">
<table border=0 class="pure-table">
<tr>
<thead>
<th>トーン</th>
<th>処理</th>
<th>設定値</th>
<th></th>
</thead>
</tr>
<tr>
</td>
<td nowrap>
<select name="tone">
<option value="0">0</option>
<option value="1">1</option>
<option value="2">2</option>
<option value="3">3</option>
<option value="4">4</option>
<option value="5">5</option>
<option value="6">6</option>
<option value="7">7</option>
<option value="8">8</option>
<option value="9">9</option>
</select>
</td>
<td>
<select name="ivritem">
$ivr_opt_list
</select>
</td>
<td>
<input type="txt" size="8" name="ivrvalue" value="">
</td>
<td>
<input type="submit" class={$_(ABSPBUTTON)} value="追加">
</td>
</tr>
</table>
</form>
注意:すでに設定済のトーンを指定すると設定内容が上書きされます。
<br>
<br>
EOT;

//セーフティタイマ
$sft = AbspFunctions\get_db_item("ABS/IVR", "TIM");
if(!ctype_digit($sft)) $sft = "300";
if($sft < 0) $sft = 300;

echo <<<EOT
<form action="" method="POST">
<input type="hidden" name="function" value="sftimeset">
<table border=0 class="pure-table">
<tr>
<td>
セーフティタイマ
</td>
<td>
<input type="text" size="2" name="sfts" value="$sft"> 秒
</td>
<td>
<input type="submit" class={$_(ABSPBUTTON)} value="設定">
</td>
</tr>
</table>
</form>
<br>
EOT;

//録音用PIN
$rpin = AbspFunctions\get_db_item("ABS/IVR", "RPIN");
if(!ctype_digit($rpin)) $rpin = "0000";

echo <<<EOT
<form action="" method="POST">
<input type="hidden" name="function" value="rpinset">
<table border=0 class="pure-table">
<tr>
<td>
メニュー音声録音PIN
</td>
<td>
<input type="text" size="2" name="rpin" value="$rpin">
</td>
<td>
<input type="submit" class={$_(ABSPBUTTON)} value="設定">
</td>
</tr>
</table>
</form>
EOT;

?>

<h3>IVRメニュー音声確認</h3>
<figure>
    <figcaption>IVRメニュー音声</figcaption>
    <audio controls>
      <source src="audio/abs-ivrmenu.mp3" type="audio/mp3">
    </audio>
</figure>
