<h2 id="bllist">QPM管理</h2>

<?php

include 'qpm-config.php';

$add_msg = "";
$p_login_name = "";
$p_disp_name = "";
$p_ext_num = "";
$import_result = "";


echo "DBファイル : ";
echo QPMDB;
echo "<br>";
echo "<br>";

//パスワード取得(tokenだけど)
function get_db_password($user){

    if($user == "") return FALSE;

    $db = new \SQLite3(QPMDB);

    $query = "SELECT password  FROM qpm_users WHERE login='" . $user  . "'";
    $res = $db->querySingle($query);

    return $res;
}


//全ユーザ取得
function get_db_alluser(){

    $ret = [];

    $db = new \SQLite3(QPMDB);

    $query = "SELECT * FROM qpm_users";
    $res = $db->query($query);

    while($tmp = $res->fetchArray()){
        array_push($ret, $tmp);
    }

    return $ret;

} //get_db_alluser

//全カテゴリ取得
function get_db_allcat(){

    $ret = [];

    $db = new \SQLite3(QPMDB);

    $query = "SELECT * FROM qpm_cats";
    $res = $db->query($query);

    while($tmp = $res->fetchArray()){
        array_push($ret, $tmp);
    }

    return $ret;

} //get_db_alluser


//新規登録
function add_db_user($login='',$token='',$disp='',$ext=''){

    if($login == "") return FALSE;
    if($token == "") return FALSE;


    $db = new \SQLite3(QPMDB);

    $query = "INSERT INTO qpm_users VALUES(";
    $query .= "'" . $login ."', ";
    $query .= "'" . $token ."', ";
    $query .= "'" . $disp ."', ";
    $query .= "'" . $ext ."' ";
    $query .= ")";

    $res = $db->querySingle($query);

    return $res;
}

//ユーザー削除
function del_db_user($login){

    if($login == "") return FALSE;


    $db = new \SQLite3(QPMDB);

    $query = "DELETE FROM qpm_users WHERE login=";
    $query .= "'" . $login ."'";

    $res = $db->query($query);

    return $res;
}

//カテゴリ削除
function del_db_cat($cat){

    if($cat == "") return FALSE;


    $db = new \SQLite3(QPMDB);

    $query = "DELETE FROM qpm_cats WHERE cat=";
    $query .= "'" . $cat ."'";

    $res = $db->query($query);

    return $res;
}

//全データ取得
function get_db_allqpm(){

    $ret = [];

    $db = new \SQLite3(QPMDB);

    $query = "SELECT * FROM qpm";
    $res = $db->query($query);

    while($tmp = $res->fetchArray()){
        array_push($ret, $tmp);
    }

    return $ret;

} //get_db_allqpm

//セル内改行(CSV出力用)
function format_cell($line){

    if($line == "") return $line;

    $tmp_ar = [];
    $tmp_line = str_replace("\r","\n",$line);
    $tmp_line = str_replace("\n\n","\n",$tmp_line);

    $tmp_line = '"' . $tmp_line . '"';

    return $tmp_line;
}

//CSV出力
function qpm_to_csv(){

    $csv_data = "";

    $current =  date("Ymd-His");
    $csvfile = CSVDIR . '/' . 'qpm-' . $current . '.csv';

    $csv_f = fopen($csvfile, "w");

    $ret = get_db_allqpm();

    foreach($ret as $ar_each){

        $p_num = $ar_each['num'];
        $p_cname = $ar_each['cname'];
        $p_cat = $ar_each['cat'];
        $p_pname = $ar_each['pname'];
        $p_zip = $ar_each['zip'];
        $p_addr = $ar_each['addr'];
        $p_pn1 = $ar_each['pn1'];
        $p_pn2 = $ar_each['pn2'];
        $p_pn3 = $ar_each['pn3'];
        $p_pn4 = $ar_each['pn4'];
        $p_memo1 = format_cell($ar_each['memo1']);
        $p_memo2 = format_cell($ar_each['memo2']);
        $p_fpfx = $ar_each['fpfx'];
        $p_attend = $ar_each['attend'];
        $p_last = $ar_each['last'];

        $csv_data = sprintf("=\"%s\",%s,%s,=\"%s\",%s,%s,%s,%s,%s,%s,%s,%s,%s,%s",
                         $p_num,$p_cname,$p_cat,$p_pname,$p_zip,$p_addr,$p_pn1,$p_pn2,$p_pn3,$p_pn4,
                         $p_memo1,$p_memo2,$p_fpfx,$p_attend,$p_last);
        $csv_data .= "\r\n";

        fwrite($csv_f, $csv_data);

     } // foreach

     fclose($csv_f);

     return  "done";

} //qpm_to_csv


//エントリの更新,存在しなければ追加
function update_qpm_db($num, $cname, $cat='', $pname='' , $zip='' ,$addr='' ,$pn1='' ,$pn2='' ,$pn3='' ,$pn4='' ,$memo1='' ,$memo2='', $fpfx='', $attend='', $last=''){

    if($num == "") return FALSE;
    if($cname == "") return FALSE;

    $db = new \SQLite3(QPMDB);
    // Check exsiting entry
    $query = "SELECT * FROM qpm WHERE num='". $num  . "'";
    $res = $db->query($query);

    if($res->fetchArray() == FALSE){
        $update_cmd = "INSERT INTO qpm values("
            .  "'" . $num
            . "','"  . $cname
            . "','" . $cat
            . "','" . $pname
            . "','" . $zip
            . "','" . $addr
            . "','" . $pn1
            . "','"  . $pn2
            . "','" . $pn3
            . "','" . $pn4
            . "','" . $memo1
            . "','" . $memo2
            . "','" . $fpfx
            . "','" . $attend
            . "','" . $last
            . "')";
        //echo $update_cmd;
        //echo "\n";
    } else {
        $update_cmd = "UPDATE qpm SET"
            .  " cname='" . $cname
            . "' ,cat='" . $cat
            . "' ,pname='" . $pname
            . "' ,zip='" . $zip
            . "' ,addr='" . $addr
            . "' ,pn1='" . $pn1
            . "' ,pn2='"  . $pn2
            . "' ,pn3='" . $pn3
            . "' ,pn4='" . $pn4
            . "' ,memo1='" . $memo1
            . "' ,memo2='" . $memo2
            . "' ,fpfx='" . $fpfx
            . "' ,attend='" .$attend
            . "' ,last='" . $last
            . "'"
            . " WHERE num='" . $num . "'";
        //echo $update_cmd;
        //echo "\n";
    }

    $res = $db->query($update_cmd);

    return TRUE;

} //update_db



// ここから実処理
//
$tr_odd_class = "";

if($_SERVER['REQUEST_METHOD'] === 'POST'){


    if(isset($_POST['newreg'])){ //新規追加
        if(isset($_POST['login_name'])) $p_login_name = $_POST['login_name'];
        if(isset($_POST['disp_name'])) $p_disp_name = $_POST['disp_name'];
        if(isset($_POST['ext_num'])) $p_ext_num = $_POST['ext_num'];
        if(isset($_POST['pass1'])) $p_pass1 = $_POST['pass1'];
        if(isset($_POST['pass2'])) $p_pass2 = $_POST['pass2'];
        if($p_login_name == ""){
            $add_msg = "<font color=red>ログイン名を指定してください</font>";
        } else {
            $ret = get_db_password($p_login_name);
            if($ret === FALSE | $ret != ""){
                $add_msg = "<font color=red>ログイン名重複</font>";
            } else {
                if($p_pass1 == ""){
                    $add_msg = "<font color=red>パスワードエラー</font>";
                } else {
                    if($p_pass1 == $p_pass2){ //実際の登録処理はここ
                        $ukey = $p_login_name . ':abspanel:' . $p_pass1;
                        $utoken = trim(md5($ukey));
                        $ret = add_db_user($p_login_name,$utoken,$p_disp_name,$p_ext_num);
                        if($ret !== FALSE) $add_msg = "完了";
                        else  $add_msg = "<font color=red>失敗</font>";
                    } else {
                        $add_msg = "<font color=red>パスワード不一致</font>";
                    }
                }
            }
        }
         
    } //新規追加

    if(isset($_POST['delmulti'])){ //ユーザー一括削除
        $p_maxent = $_POST['numents'];
        for($i=0;$i<$p_maxent;$i++){
            $index = 'chk_' . $i;
            if(isset($_POST[$index])){
                $entry = $_POST[$index];
                echo $entry;
                del_db_user($entry);
            }
        }
    }

    if(isset($_POST['delcat'])){ //業種削除
        $p_maxent = $_POST['numents'];
        for($i=0;$i<$p_maxent;$i++){
            $index = 'chkcat_' . $i;
            if(isset($_POST[$index])){
                $entry = $_POST[$index];
                echo $entry;
                del_db_cat($entry);
            }
        }
    }

    if(isset($_POST['gencsv'])){ //CSVファイル作成
        echo "GENCSV";
        qpm_to_csv();
    }


    if(isset($_POST['downloadcsv'])){ //CSVダウンロード
        $f_name = trim($_POST['filename']);
        $f_path = CSVDIR . '/' . $f_name;

        echo $f_path;

        header('Content-Type: text/csv');
        header('Content-Length: '. filesize($f_path));
        header('Content-Disposition: attachment; filename*=UTF-8\'\'' . rawurlencode($f_name)); 
        readfile($f_path);
    }


    if(isset($_POST['function'])){
        if($_POST['function'] == 'delete'){

            if(isset($_POST['filename'])){
                if(isset($_POST['confirm'])){
                    if($_POST['confirm'] == 'yes'){
                        $p_filename = $_POST['filename'];
                        $delpath = CSVDIR . '/' . $p_filename;
                        if(is_file($delpath)){
                            unlink($delpath);
                        }
                    }
                }
            }
        }
    }

    if(isset($_POST['function'])){
        if($_POST['function'] == 'import'){
            if(isset($_POST['confirm'])){
                if($_POST['confirm'] == 'yes'){
                    $target = trim($_POST['file']);
                    $target = CSVDIR . '/' . $target;
                    $import_result = '';
                    $next_line = '';
                    $fp = fopen($target, "r");
                    if($fp){
                        while($line = fgets($fp)){
                            //1行内のダブルクォートの数を数える
                            $count = mb_substr_count($line, '"');
                            //奇数個なら行は継続(セル内改行あり)
                            if( $count % 2 != 0){
                                //1行追加で読んで繋ぐ
                                $next_line = fgets($fp);
                                $line = $line . $next_line;
                            }
                            list ($r_num, $r_cname, $r_cat, $r_pname, $r_zip, $r_addr, $r_pn1, $r_pn2, $r_pn3, $r_pn4, $r_memo1, $r_memo2, $r_fpfx, $r_attend, $r_last) = explode(",", $line);
                            // BOMがあったら削除する
                            $r_num = preg_replace('/[\xef\xbb\x80\xbf]/', '', $r_num);
                            $r_num = str_replace(['=','"'], "", $r_num);
                            $r_zip = str_replace(['=','"'], "", $r_zip);
                            $r_memo1 = str_replace('"', "", $r_memo1);
                            $r_memo2 = str_replace('"', "", $r_memo2);
                            $r_fpfx = str_replace(['=','"'], "", $r_fpfx);
                            $import_result .= sprintf("%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s | ", $r_num, $r_cname, $r_cat, $r_pname, $r_zip, $r_addr, $r_pn1, $r_pn2, $r_pn3, $r_pn4, $r_memo1, $r_memo2, $r_fpfx, $r_attend, $r_last);
                            $res = update_qpm_db($r_num, $r_cname, $r_cat, $r_pname, $r_zip, $r_addr, $r_pn1, $r_pn2, $r_pn3, $r_pn4, $r_memo1, $r_memo2, $r_fpfx, $r_attend, $r_last);
                            if($res == 1) $res_str = 'OK';
                            else $res_str = 'NG';
                            $import_result .= $res_str . "<BR>";
                        }
                     }
                     fclose($fp);
                }
            }
        }
    }

    if(isset($_POST['function'])){
        if($_POST['function'] == 'c2cfunc'){ //クリックコール機能設定
            $p_qpmc2c = $_POST['qpmc2c'];
            if($p_qpmc2c == '1') AbspFunctions\put_db_item('ABS', 'QPMC2C', '1');
            else  AbspFunctions\put_db_item('ABS', 'QPMC2C', '0');
            $p_qpmc2c_pfx = trim($_POST['qpmc2c_pfx']);
            AbspFunctions\put_db_item('ABS', 'QPMC2CPFX', $p_qpmc2c_pfx);
        }
    }


} // end of POST



$i = 0;

// 一覧表示

$ret = get_db_alluser();
$num_ents = 0;

echo <<<EOT
<form action="" method="POST">
<table border=0 class="pure-table">
  <tr>
    <thead>
      <th>ログイン名</th>
      <th>表示名</th>
      <th>内線番号</th>
      <th>削除</th>
    </thead>
  </tr>
EOT;

foreach($ret as $ar_each){

    $p_login = $ar_each['login'];
    $p_dname = $ar_each['dname'];
    $p_ext = $ar_each['ext'];

   if($num_ents % 2 == 0){
       $tr_odd_class = '';
   } else {
       $tr_odd_class = 'class="pure-table-odd"';
   }

echo <<<EOT
  <tr $tr_odd_class>
    <td nowrap>
      $p_login
    </td>
    <td>
      $p_dname
    </td>
    <td>
      $p_ext
    </td>
    <td>
      <input type="checkbox" name="chk_$num_ents" value="$p_login">
    </td>
  </tr>
EOT;

    $num_ents += 1;

} // foreach

echo "</table>";
echo "<input type=\"hidden\" name=\"numents\" value=\"$num_ents\">";
echo "<input type=\"submit\" name=\"delmulti\" class={$_(ABSPBUTTON)} value=\"削除実行\">";
echo "</form>";
echo "<br>";

$numents = 0;

echo "ユーザ追加";
echo <<<EOT
<form action="" method="POST">
  <input type="hidden" name="function" value="entdel">
<table border=0 class="pure-table">
  <tr>
    <thead>
      <th>ログイン名(英字のみ)</th>
      <th>表示ユーザ名</th>
      <th>内線番号</th>
      <th>パスワード</th>
      <th>パスワード(確認)</th>
      <th></th>
      <th></th>
    </thead>
  </tr>

  <tr $tr_odd_class>
    <td nowrap>
        <input type="txt" size="8" name="login_name" value=$p_login_name>
    </td>
    <td>
        <input type="text" size="8" name="disp_name" value=$p_disp_name>
    </td>
    <td>
        <input type="text" size="4" name="ext_num" value=$p_ext_num>
    </td>
    <td>
        <input type="password" size="8" name="pass1">
    </td>
    <td>
        <input type="password" size="8" name="pass2">
    </td>
    <td>
      <input type="submit" name="newreg" class={$_(ABSPBUTTON)} value="登録">
    </td>
    <td>
      $add_msg
    </td>
  </tr>
EOT;


echo "</table>";

?>

<?php
//クリックコール

    $qpmc2c_selected = array('0'=>'','1'=>'');
    $qpmc2c = AbspFunctions\get_db_item('ABS', 'QPMC2C');
    $qpmc2c_selected["$qpmc2c"] = "selected";
    $qpmc2c_pfx = AbspFunctions\get_db_item('ABS', 'QPMC2CPFX');

echo <<<EOT
<br>
<h3 id="tdis">クリックコール機能</h3>
<form action="" method="POST">
<input type="hidden" name="function" value="c2cfunc">
<select name="qpmc2c">
<option value="0" {$qpmc2c_selected['0']}>使わない</option>
<option value="1" {$qpmc2c_selected['1']}>使う</option>
</select>
発信時プレフィクス
<input type="text" size="2" name="qpmc2c_pfx" value="$qpmc2c_pfx">
<input type="submit" class={$_(ABSPBUTTON)} value="設定">
</form>
<br>
EOT;
?>

<hr>
<h2>業種管理</h2>
業種はユーザが自由登録できます。多すぎる場合には削除してください。<br>
<?php
// 一覧表示

$ret = get_db_allcat();
$num_ents = 0;

echo <<<EOT
<form action="" method="POST">
<table border=0 class="pure-table">
  <tr>
    <thead>
      <th>業種名</th>
      <th>削除</th>
    </thead>
  </tr>
EOT;


foreach($ret as $ar_each){

    $p_cat = $ar_each[0];


   if($num_ents % 2 == 0){
       $tr_odd_class = '';
   } else {
       $tr_odd_class = 'class="pure-table-odd"';
   }
echo <<<EOT
  <tr $tr_odd_class>
    <td nowrap>
      $p_cat
    </td>
    <td>
      <input type="checkbox" name="chkcat_$num_ents" value="$p_cat">
    </td>
  </tr>
EOT;

    $num_ents += 1;
}
echo "</table>";
echo "<input type=\"hidden\" name=\"numents\" value=\"$num_ents\">";
echo "<input type=\"submit\" name=\"delcat\" class={$_(ABSPBUTTON)} value=\"削除実行\">";
echo "</form>";
echo "<br>";
?>

<hr>
<h2>データ管理</h2>
<?php
echo "<FORM ACTION=\"\" METHOD=\"POST\">";
echo "<input type=\"submit\" name=\"gencsv\" class={$_(ABSPBUTTON)} value=\"CSVファイル作成\">";
echo "</FORM>";

    $dir = opendir(CSVDIR);
    if($dir !== FALSE){
        while (false !== ($file_list[] = readdir($dir)));
        closedir($dir);
        sort($file_list);
    }

echo <<<EOT
<br>
<h3>CSVファイル一覧</h3>
<table border=0 class="pure-table">
  <tr>
    <thead>
      <th>ファイル</th>
      <th>削除</th>
      <th></th>
      <th></th>
    </thead>
  </tr>
EOT;

    $i = 0;

    foreach($file_list as $key=>$file){
        if($file == '.htaccess') continue;
        $target = CSVDIR . '/' . $file;
       if(is_file($target)) {

       if($i % 2 == 0){
           $tr_odd_class = '';
       } else {
           $tr_odd_class = 'class="pure-table-odd"';
       }
       $i++;

echo <<<EOT
  <tr $tr_odd_class>
    <td>
      $file
    </td>
    <td>
      <form action="" method="post">
      <input type="hidden" name="filename" value="$file">
      <input type="hidden" name="function" value="delete">
      <input type="checkbox" name="confirm" value="yes">
      <input type="submit" name="delcsv" class={$_(ABSPBUTTON)} value="削除">
      </form>
    </td>
    <td>
      <form action="php/addon/qpm-download.php" method="get">
      <input type="hidden" name="file" value="$file">
      <input type="submit" class={$_(ABSPBUTTON)} value="ダウンロード">
      </form>
    </td>
    <td>
      <form action="" method="post">
      <input type="hidden" name="function" value="import">
      <input type="hidden" name="file" value="$file">
      <input type="checkbox" name="confirm" value="yes">
      <input type="submit" class={$_(ABSPBUTTON)} value="インポート">
      </form>
    </td>
  </tr>
EOT;
       }
    }
echo "</table>";

echo <<<EOT
<hr>
<h3>ファイルアップロード</h3>
QPMデータベースにインポートするには一旦アップロードしてください。<br>
ファイル名の先頭はqpm-にしてください。<br>
同じ名前のファイルがあると上書きされます。<br>
<form method="post" action="php/addon/qpm-upload.php" enctype="multipart/form-data">
  <table border=0 class="pure-table">
    <tr>
      <td>
        ファイル : <input type="file" name="upload_file">
      </td>
      <td>
        <input type="submit" class={$_(ABSPBUTTON)} value="upload">
      </td>
  </table>
</form>
<HR>
インポート結果：<br>
$import_result
EOT;
?>
