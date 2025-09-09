<h2 id="qdlist">短縮(クィック)ダイヤル管理</h2>

<?php
$msg = "";
$qnum = "";
$pnum = "";
$pname = "";

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    if($_POST['function'] == 'newadd'){ //新規追加
        $go = 1;

        $p_qnum = trim($_POST['qnum']);
        $qnum = $p_qnum;
        $p_pnum = trim($_POST['pnum']);
        $pnum = $p_pnum;
        $p_pname = trim($_POST['pname']);
        $pname = $p_pname;
        if(isset($_POST['overd'])){
             $p_overd = trim($_POST['overd']);
        } else {
             $p_overd = "";
        }

        if(strlen($p_qnum) != 3){
            $msg = '短縮番号は3桁で指定してください';
            $go = 0;
        }
        if(intval($p_qnum)<0 | intval($p_qnum)>299) {
            $msg = '000～299で指定してください';
            $go = 0;
        }

        if($p_pnum != ""){
            $go = 1;
        } else {
            $go = 0;
            $msg = '電話番号を指定してください';
        }

        if($go == 1){
            $tmp_ent = trim(AbspFunctions\get_db_item('ABS/quickdial', $p_qnum));
            if($tmp_ent != ""){
                if($p_overd != "on"){
                    $go = 0;
                    $msg = '上書きしたい場合はチェックを入れてください';
                }
            }
        }

        if($go == 1){
            //CID名が指定されていない場合にはcidnameから削除する
            if($p_pname == ""){
                AbspFunctions\del_db_item('cidname', $p_pnum);
            } else {
                //cidname登録
                AbspFunctions\put_db_item('cidname', $p_pnum, $p_pname);
            }
            AbspFunctions\put_db_item('ABS/quickdial', $p_qnum, $p_pnum);
            $qnum = "";
            $pnum = "";
            $pname = "";
            $msg = "";
        }

    } //新規追加

    if($_POST['function'] == 'entdel'){ //一括削除
        $p_maxent = $_POST['numents'];
        for($i=1;$i<=$p_maxent;$i++){
            $index = 'delcb_' . $i;
            if(isset($_POST[$index])){
                $entry = $_POST[$index];
                AbspFunctions\del_db_item('ABS/quickdial', $entry);
            }
        }
    } //一括削除

    if($_POST['function'] == 'ogpset'){ //プレフィックス設定
        $p_qdogp = trim($_POST['qdogp']);
        if($p_qdogp == 1){
            AbspFunctions\put_db_item('ABS/quickdial', 'PFX', '1');
        }
        else if($p_qdogp == 2){
            AbspFunctions\put_db_item('ABS/quickdial', 'PFX', '2');
        }
        else {
            AbspFunctions\put_db_item('ABS/quickdial', 'PFX', '0');
        }
    } //プレフィクス設定
   
} // end of POST


//新規追加
$qdpfx = '';

$qdpfx = AbspFunctions\get_db_item('ABS/quickdial', 'PFX');
$qdpfx = trim($qdpfx);
if($qdpfx == 1){
  $ogp_selected[0] = '';
  $ogp_selected[1] = 'selected';
  $ogp_selected[2] = '';
}
else if($qdpfx == 2){
  $ogp_selected[0] = '';
  $ogp_selected[1] = '';
  $ogp_selected[2] = 'selected';
}
else {
  $ogp_selected[0] = 'selected';
  $ogp_selected[1] = '';
  $ogp_selected[2] = '';
}

echo <<<EOT
<h3 id="qdprefix">クイック時プレフィクス設定</h3>
<form action="" method="post">
<select name="qdogp">
  <option value="0" {$ogp_selected['0']}>未設定</option>
  <option value="1" {$ogp_selected['1']}>OGP1</option>
  <option value="2" {$ogp_selected['2']}>OGP2</option>
</select>
<input type="submit" class={$_(ABSPBUTTON)} value="設定">
<input type="hidden" name="function" value="ogpset">
</form>
<br>
クィックダイヤルで発信する場合のプレフィクスを指定。<br>
OGPが使用するトランクは発信設定で設定してください。</br>
<br>

<h3>クィックダイヤル追加登録</h3>
<form action="" method="POST">
  <input type="hidden" name="function" value="newadd">
<table border=0 class="pure-table">
  <tr>
    <thead>
      <th>短縮番号</th>
      <th>電話番号</th>
      <th>名前</th>
      <th>上書き</th>
      <th></th>
      <th></th>
    </thead>
  </tr>
  <tr>
    <td nowrap>
      <input type="txt" size="4" name="qnum" value=$qnum>
    </td>
    <td nowrap>
      <input type="txt" size="12" name="pnum" value=$pnum>
    </td>
    <td nowrap>
      <input type="txt" size="12" name="pname" value=$pname>
    </td>
    <td nowrap>
      <input type="checkbox" 2" name="overd">
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
注意：短縮番号は数字3桁で000～299で指定してください。<br>
　　　名前は電話機に表示されるので1行で短く簡潔に。<br>
EOT;

//一覧表示
$num_ents = 0;

echo <<<EOT
<h3>登録済み一覧</h3>
<form action="" method="POST">
  <input type="hidden" name="function" value="entdel">
<table border=0 class="pure-table">
  <tr>
    <thead>
      <th>短縮番号</th>
      <th>電話番号</th>
      <th>名前</th>
      <th>削除</th>
      <th></th>
    </thead>
  </tr>
EOT;

$entry = AbspFunctions\get_db_family('ABS/quickdial');

if(!empty($entry)){
  foreach($entry as $line){

    list($ent, $pnum) = explode(' : ', $line, 2);
    $ent = trim($ent);
    if(ctype_digit($ent)){

        $pnum = trim($pnum);

        $cidname = AbspFunctions\get_db_item('cidname', $pnum);

        $num_ents = $num_ents + 1;

        if($num_ents % 2 != 0){
            $tr_odd_class = '';
        } else {
            $tr_odd_class = 'class="pure-table-odd"';
        }

echo <<<EOT
  <tr $tr_odd_class>
    <td>
      $ent
    </td>
    <td nowrap>
      $pnum
    </td>
    <td>
      $cidname
    </td>
    <td>
      <input type="checkbox" name="delcb_$num_ents" value="$ent">
    </td>
    <td>
    </td>
  </tr>
EOT;
    }

  } /* end of for */
}

echo "</table>";

echo <<<EOT
<input type="submit" class={$_(ABSPBUTTON)} value="削除実行">
<input type="hidden" name="numents" value="$num_ents">
</form>
EOT;

?>
