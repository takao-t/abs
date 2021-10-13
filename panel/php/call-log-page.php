<h2>着信記録管理</h2>

<?php

$rot_msg = '';

    if($_SERVER['REQUEST_METHOD'] === 'POST'){

        if($_POST['function'] == 'cllog'){ //着信ログ設定
            if(isset($_POST['clogsw'])){
                $p_log = $_POST['clogsw'];
                if($p_log == 'on'){
                    AbspFunctions\put_db_item('ABS', 'ILOG', '1');
                } else {
                    AbspFunctions\del_db_item('ABS', 'ILOG');
                }
            } else {
                AbspFunctions\del_db_item('ABS', 'ILOG');
            }
        }

        if($_POST['function'] == 'bllog'){ //拒否ログ設定
            if(isset($_POST['blogsw'])){
                $p_log = $_POST['blogsw'];
                if($p_log == 'on'){
                    AbspFunctions\put_db_item('ABS/BLC', 'LOG', '1');
                } else {
                    AbspFunctions\del_db_item('ABS/BLC', 'LOG');
                }
            } else {
                AbspFunctions\del_db_item('ABS/BLC', 'LOG');
            }
        }

        if($_POST['function'] == 'dologrot'){ //ログ・ローテーション実行
            if(isset($_POST['logrchk'])){
                if(trim($_POST['logrchk']) == "YES"){
                    AbspFunctions\exec_cli_command('channel originate Local/s@create-logdb application NoCDR');
                    $rot_msg = "ローテーションを実行しました";
                }
            }
        }

        if($_POST['function'] == 'logverctl'){ //ログバージョン切り替え
            if(isset($_POST['dbver'])){
                $p_dbver = trim($_POST['dbver']);
                if(($p_dbver>0)&($p_dbver<10)){
                    AbspFunctions\put_db_item('ABS', 'CLOGVER', $p_dbver);
                } else {
                    AbspFunctions\del_db_item('ABS', 'CLOGVER');
                }
             }
        }

    } //POST

//
if(!isset($inofsn)){
    $inofsn = 0;
}

$clogver = AbspFunctions\get_db_item('ABS', 'CLOGVER');
if(($clogver < 1)|($clogver > 9)) $clogver = "";
$logver_sel = array();
for($i=0;$i<10;$i++){
    if($clogver == $i) $logver_sel[$i] = 'selected';
    else $logver_sel[$i] = '';
}

echo <<<EOT
<h3>記録表示</h3>
<table border="0" class="pure-table">
  <tr>
    <td>
      表示対象履歴
    </td>
    <td>
      <form action="" method="post">
        <input type="hidden" name="function" value="logverctl">
        <select name="dbver">
          <option value=""  $logver_sel[0]>現在</option>
          <option value="1" $logver_sel[1]>1</option>
          <option value="2" $logver_sel[2]>2</option>
          <option value="3" $logver_sel[3]>3</option>
          <option value="4" $logver_sel[4]>4</option>
          <option value="5" $logver_sel[5]>5</option>
          <option value="6" $logver_sel[6]>6</option>
          <option value="7" $logver_sel[7]>7</option>
          <option value="8" $logver_sel[8]>8</option>
          <option value="9" $logver_sel[9]>9</option>
        </select>
        <input type="submit" class={$_(ABSPBUTTON)} value="変更">
      </form>
    </td>
  </tr>
</table>

<br>

<table border="0" class="pure-table">
  <tr>
    <td>
      <A HREF="./index.php?page=call-log-disp.php">着信履歴表示</A>
    </td>
  </tr>
  <tr>
    <td>
      <A HREF="./index.php?page=block-log-disp.php">着信拒否履歴表示</A>
    </td>
  </tr>
</table>
<br>
EOT;

//ログ概要表示
echo <<<EOT
<h4>ローテーションした記録の概要</h4>
<table border="0" class="pure-table">
  <thead>
    <th>番号</th>
    <th>開始</th>
    <th>終了</th>
    <th>件数</th>
  </thead>
EOT;

//ログDBファイル名(config.phpで設定する)
$dbfile_base = CLOGDB;
//ログサマリ表示
for($i=0;$i<10;$i++){
    if($i == 0){
        $log_num = '現在';
        $dbfile = $dbfile_base;
    } else {
        $log_num = $i;
        $dbfile = $dbfile_base . ".$i";
    }

    try {
        //件数取得
        $logdb = new SQLite3($dbfile,SQLITE3_OPEN_READONLY);
        $q = $logdb->prepare("SELECT count(*) FROM abslog");
        $res = $q->execute();
        $res_ar = $res->fetchArray();
        $count_total = (int)$res_ar['count(*)'];
        if($count_total > 0){
            //最初のエントリのタイムスタンプ
            $q = $logdb->prepare("SELECT TIMESTAMP FROM abslog WHERE ID='1'");
            $res = $q->execute();
            $res_ar = $res->fetchArray();
            $tm_start = $res_ar['TIMESTAMP'];
            //最後のエントリのタイムスタンプ
            $q = $logdb->prepare("SELECT TIMESTAMP FROM abslog WHERE ID=" . $count_total);
            $res = $q->execute();
            $res_ar = $res->fetchArray();
            $tm_end = $res_ar['TIMESTAMP'];
        } else {
            $tm_start = "---";
            $tm_end = "---";
        }
        $logdb->close();
    } catch (Exception $e) {
        $count_total = 'ファイルなし';
        $tm_start = "---";
        $tm_end = "---";
    }

echo <<<EOT
  <tr>
    <td>
      $log_num
    </td>
    <td>
      $tm_start
    </td>
    <td>
      $tm_end
    </td>
    <td>
      $count_total
    </td>
  </tr>
EOT;

} //サマリforのおわり

echo "</table>";

//着信ログ現在値
    $clogsw = '';
    $clogckd = '';
    $clogsw = AbspFunctions\get_db_item('ABS', 'ILOG');
    if($clogsw == '1') $clogckd="checked=\"checked\"";
//着信拒否ログ現在値
    $blogsw = '';
    $blogckd = '';
    $blogsw = AbspFunctions\get_db_item('ABS/BLC', 'LOG');
    if($blogsw == '1') $blogckd="checked=\"checked\"";

echo <<<EOT
<hr>
<h3>記録設定</h3>
<table class="pure-table">
  <tr>
    <td width="200">
      <h3 id="bllog">着信記録</h3>
    </td>
    <td>
      <form action="" method="post">
      <input type="hidden" name="function" value="cllog">
      <input type="checkbox" name="clogsw" value="on" $clogckd>
      <input type="submit" class={$_(ABSPBUTTON)} value="設定">
      </form>
    </td>
  </tr>
</table>
<br>
<table class="pure-table">
  <tr>
    <td width="200">
      <h3 id="bllog">着信拒否記録</h3>
    </td>
    <td>
      <form action="" method="post">
      <input type="hidden" name="function" value="bllog">
      <input type="checkbox" name="blogsw" value="on" $blogckd>
      <input type="submit" class={$_(ABSPBUTTON)} value="設定">
      </form>
    </td>
  </tr>
</table>

<h3>ログ・ローテーションの実行</h3>
着信履歴用のDBを切り替えます。9世代まで保存されます。<br>
これを実行すると即時にログDBが切り替わりますので注意してください。<br>
<table border="0" class="pure-table">
<tr>
<form action="" method="post">
<td width="200">
    <input type="hidden" name="function" value="dologrot">
    注意を確認しました&nbsp;
    <input type="checkbox" name="logrchk" value="YES">
</td>
<td>
<input type="submit" class={$_(ABSPBUTTON)} value="実行">
</td>
<td>
$rot_msg
</td>
</tr>
</form>
</table>
<br>

注意：はじめて着信履歴を使用する場合にはログ・ローテーションを実行してください。履歴記録用の初期DBが作成されます。
EOT;

?>
