<h2>フリーアドレスユーザ設定</h2>

<?php
$msg = "";
$p_uid = '';
$p_ext = '';
$p_pin = '';
$p_ogcid = '';
$limit_selected = array('0'=>'', '1'=>'', '2'=>'', '3'=>'', '4'=>'');

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    if($_POST['function'] == 'newadd'){ //ユーザ新規追加
        if(isset($_POST['uid'])){
            $p_uid = trim($_POST['uid']);
        }
        if(isset($_POST['ext'])){
            $p_ext = trim($_POST['ext']);
        }
        if(isset($_POST['pin'])){
            $p_pin = trim($_POST['pin']);
        }
        if(isset($_POST['ogcid'])){
            $p_limit = trim($_POST['limit']);
        }
        if(isset($_POST['ogcid'])){
            $p_ogcid = trim($_POST['ogcid']);
        }

        if($p_limit<0 | $p_limit>4) $p_limit = 0;
        $limit_selected[$p_limit] = 'selected';

        $p_exists = "";
        if($p_uid != "" & $p_ext != "" & $p_pin != ""){
          if(ctype_digit($p_uid) & ctype_digit($p_ext) & ctype_digit($p_pin) ){
            //OGCIDが数値でない場合には削除
            if(!ctype_digit($p_ogcid)) $p_ogcid ="";
            //内線重複チェック
            $e_ext = AbspFunctions\get_db_item("ABS/EXT", $p_ext);
            if($e_ext != ""){
                if(strstr($e_ext,"FAP") === false){
                    $msg = "内線番号重複";
                } else {
                    $msg = "ログイン中";
                }
            } else {
                //FDユーザ内線チェック
                $entry = AbspFunctions\get_db_family('ABS/FAP/UID');
                if(is_array($entry)){
                  foreach($entry as $line){
                    list($uid, $ent) = explode('/', $line, 2);
                    list($cat, $val) = explode(':',$ent,2);
                    $cat = trim($cat);
                    $val = trim($val);
                    if($cat == 'EXT'){
                        if($p_uid != $uid){ //自UIDでなければ重複
                            if($val == $p_ext) $p_exists = 'yes';
                        }
                    }
                  }
                }
                if($p_exists == "yes"){
                    $msg = "内線番号重複(FD)";
                } else { //内線重複なし、登録実行
                    AbspFunctions\put_db_item("ABS/FAP/UID/$p_uid", "EXT", $p_ext);
                    AbspFunctions\put_db_item("ABS/FAP/UID/$p_uid", "PIN", $p_pin);
                    AbspFunctions\put_db_item("ABS/FAP/UID/$p_uid", "LMT", $p_limit);
                    AbspFunctions\put_db_item("ABS/FAP/UID/$p_uid", "OGCID", $p_ogcid);
                }
            }
          } else {
              $msg = "設定は数字で行ってください";
          } 
        } else {
            $msg = "ユーザID,PIN,内線番号は必須です";
        }
    } //新規追加

    if($_POST['function'] == 'entdel'){ //ユーザ削除
        if(isset($_POST['delcb'])){
            if($_POST['delcb'] == "yes"){
                if(isset($_POST['d_uid'])){
                    $p_d_uid = $_POST['d_uid'];
                    $p_d_ext = $_POST['d_ext'];
                    $p_d_peer = $_POST['d_peer'];
                    if($p_d_peer != ""){ //ログイン済内線の場合には内線削除
                        AbspFunctions\del_db_item("ABS/EXT/$p_d_ext", "OGCID");
                        AbspFunctions\del_db_item("ABS/EXT", $p_d_ext);
                        AbspFunctions\del_db_item("ABS/ERV", $p_d_peer);
                        AbspFunctions\del_db_item("ABS/LMT", $p_d_peer);
                    }
                    //エントリ削除
                    AbspFunctions\del_db_tree("ABS/FAP/UID/$p_d_uid");
                }
            }
        }
    }

    if($_POST['function'] == 'entedi'){ //ユーザ編集
        if(isset($_POST['e_uid'])){
           $p_uid = $_POST['e_uid'];
           $p_ext = $_POST['e_ext'];
           $p_pin = $_POST['e_pin'];
           $p_ogcid = $_POST['e_ogcid'];
           $limit_selected = array('0'=>'', '1'=>'', '2'=>'', '3'=>'', '4'=>'');
           $p_limit = trim($_POST['e_limit']);
           $limit_selected[$p_limit] = 'selected';
        }
        $e_ext = AbspFunctions\get_db_item("ABS/EXT", $p_ext);
        if($e_ext != ""){ //ログイン中なら警告を出しておく
            $msg = "ログイン中";
        }
    }

    if($_POST['function'] == 'tlogout'){ //端末ログアウト
        if(isset($_POST['d_ext']) & isset($_POST['d_peer'])){
            $p_l_ext = $_POST['d_ext'];
            $p_l_peer = $_POST['d_peer'];
            AbspFunctions\del_db_item("ABS/EXT/$p_l_ext", "OGCID");
            AbspFunctions\del_db_item("ABS/EXT", $p_l_ext);
            AbspFunctions\del_db_item("ABS/ERV", $p_l_peer);
            AbspFunctions\del_db_item("ABS/LMT", $p_l_peer);
        }
    }

    if($_POST['function'] == 'flogout'){ //強制ログアウト設定
        if(isset($_POST['flogout'])){
            if($_POST['flogout'] == 'NO'){
                AbspFunctions\put_db_item('ABS/FAP', 'FLO', 'NO');
            } else {
                AbspFunctions\put_db_item('ABS/FAP', 'FLO', 'YES');
            }
        }
    }

    if($_POST['function'] == 'pgreload'){ //ページリロード
    }

} // end of POST


//ユーザ新規追加
echo <<<EOT
<h3>ユーザ追加</h3>
<form action="" method="POST">
<input type="hidden" name="function" value="newadd">
<table border=0 class="pure-table">
<tr>
<thead>
<th>ユーザID</th>
<th>PIN</th>
<th>内線番号</th>
<th>規制値</th>
<th>発信者番号</th>
<th></th>
<th></th>
</thead>
</tr>
<tr>
</td>
<td nowrap>
<input type=\"txt\" size="6" name="uid" value="$p_uid">
</td>
<td>
<input type=\"txt\" size="6" name="pin" value="$p_pin">
</td>
<td>
<input type=\"txt\" size="6" name="ext" value="$p_ext">
</td>
<td>
<select name="limit">
 <option value="0" {$limit_selected['0']}>0</option>
 <option value="1" {$limit_selected['1']}>1</option>
 <option value="2" {$limit_selected['2']}>2</option>
 <option value="3" {$limit_selected['3']}>3</option>
</select>
</td>
<td>
<input type="txt" size="10" name="ogcid" value="$p_ogcid">
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
<br>
EOT;

//一覧表示
$num_ents = 0;

echo <<<EOT
<h3>フリーアドレスユーザ一覧</h3>
<table border=0 class="pure-table">
<tr>
<thead>
<th>ユーザID</th>
<th>PIN</th>
<th>内線番号</th>
<th>規制値</th>
<th>発信者番号</th>
<th>編集</th>
<th>削除</th>
<th>ログイン</th>
<th>操作</th>
</thead>
</tr>
EOT;

$entry = AbspFunctions\get_db_family('ABS/FAP/UID');

$d_list = array();

if(is_array($entry)){
  //各エントリのアレイ生成
  foreach($entry as $line){

    list($uid, $ent) = explode('/', $line, 2);
    $uid = trim($uid);
    list($cat, $val) = explode(':',$ent,2);
    $cat = trim($cat);
    $val = trim($val);

    $d_list[$uid]['UID'] = $uid;
    if($cat == 'EXT'){
      $d_list[$uid]['EXT'] = $val;
    }
    if($cat == 'PIN'){
      $d_list[$uid]['PIN'] = $val;
    }
    if($cat == 'OGCID'){
      $d_list[$uid]['OGCID'] = $val;
    }
    if($cat == 'LMT'){
      $d_list[$uid]['LMT'] = $val;
    }

  } /* end of foreach */

  //各エントリ表示
  foreach($d_list as $r_line){

    $uid = $r_line['UID'];
    $pin = $r_line['PIN'];
    $ext = $r_line['EXT'];
    if(isset($r_line['LMT'])) $limit = $r_line['LMT'];
    else $limit = '0';
    if(isset($r_line['OGCID'])) $ogcid = $r_line['OGCID'];
    else $ogcid = '';

    $lin_peer = AbspFunctions\get_db_item('ABS/EXT', $ext);

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
$uid
</td>
<td>
$pin
</td>
<td>
$ext
</td>
<td>
$limit
</td>
<td>
$ogcid
</td>
<td>
  <form action="" method="POST">
  <input type="hidden" name="function" value="entedi">
  <input type="hidden" name="e_uid" value="$uid">
  <input type="hidden" name="e_ext" value="$ext">
  <input type="hidden" name="e_pin" value="$pin">
  <input type="hidden" name="e_limit" value="$limit">
  <input type="hidden" name="e_ogcid" value="$ogcid">
  <input type="submit" class={$_(ABSPBUTTON)} value="編集">
  </form>
</td>
<td>
  <form action="" method="POST">
  <input type="hidden" name="function" value="entdel">
  <input type="hidden" name="d_uid" value="$uid">
  <input type="hidden" name="d_ext" value="$ext">
  <input type="hidden" name="d_peer" value="$lin_peer">
  <input type="checkbox" name="delcb" value="yes">
  <input type="submit" class={$_(ABSPBUTTON)} value="削除">
  </form>
</td>
<td>
$lin_peer
</td>
EOT;

if($lin_peer != ""){ //ログイン中ならログアウトボタン表示
echo <<<EOT
<td>
  <form action="" method="POST">
  <input type="hidden" name="function" value="tlogout">
  <input type="hidden" name="d_ext" value="$ext">
  <input type="hidden" name="d_peer" value="$lin_peer">
  <input type="submit" class={$_(ABSPBUTTON)} value="ログアウト">
  </form>
</td>
EOT;
} else {
echo <<<EOT
<td>
 
</td>
EOT;
}

echo <<<EOT
</tr>
EOT;
  } /* end of foreach */

} /* is_array  */
echo "</table>";

echo <<<EOT
<form action="" method="POST">
  <input type="hidden" name="function" value="pgreload">
  <input type="submit" class={$_(ABSPBUTTON)} value="更新">
</form>
<br>
EOT;

$flo = AbspFunctions\get_db_item('ABS/FAP', 'FLO');
if($flo == 'NO'){
  $sel1 = "";
  $sel2 = "selected";
} else {
  $sel1 = "selected";
  $sel2 = "";
}

echo <<<EOT
<hr>
他場所でログインしている場合の強制ログアウト
<form actiuon="" method="POST">
<select name="flogout">
<option value="YES" $sel1>する</option>
<option value="NO" $sel2>しない</option>
</select>
<input type="hidden" name="function" value="flogout">
<input type="submit" class={$_(ABSPBUTTON)} value="設定">
</form>
EOT;

?>