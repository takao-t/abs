<h2>着信拒否履歴</h2>

<?php

    if($_SERVER['REQUEST_METHOD'] === 'POST'){

        if($_POST['function'] == 'bladd'){
          $p_cid = trim($_POST['blcid']);
          $p_yesno = trim($_POST['blchecked']);
          if($p_yesno == 'YES'){
            AbspFunctions\put_db_item('ABS/blocklist', $p_cid, '1');
          } else {
            AbspFunctions\del_db_item('ABS/blocklist', $p_cid);
          } 
        }

        //履歴ページ制御
        if($_POST['function'] == 'inpgctrl'){
            if(isset($_POST['CTVAL'])){
                $inofsn = (int)trim($_POST['CTVAL']);
            } else {
                $inofsn = 0;
            }
            if(isset($_POST['CTMAX'])){
                $inpmax = (int)trim($_POST['CTMAX']);
            } else {
                $inpmax = 0;
            }
            if(isset($_POST['CTRL'])){
                if($_POST['CTRL'] == 'prev'){
                    $inofsn = $inofsn - 20;
                    if($inofsn < 0) $inofsn = 0;
                }
                if($_POST['CTRL'] == 'next'){
                    $inofsn = $inofsn + 20;
                    if($inofsn > $inpmax) $inofsn = 0;
                }
            }
        }

        if($_POST['function'] == 'blpgctrl'){
        }

    } //POST

//
if(!isset($inofsn)){
    $inofsn = 0;
}

//DBの表示用バージョンを取得
$dbver = trim(AbspFunctions\get_db_item('ABS', 'CLOGVER'));
if($dbver != '') $dbver = '.' . $dbver;
//ログDBファイル名(config.phpで設定する)
$dbfile = CLOGDB . $dbver;

//ログDBはROで使用
try {
    $logdb = new SQLite3($dbfile,SQLITE3_OPEN_READONLY);
} catch (Exception $e) {
    echo "<B>ログDBファイルがありません</B><BR>";
    exit;
}

//件数取得
//拒否
$q = $logdb->prepare("SELECT count(*) FROM abslog WHERE KIND='BLOCKED'");
$res = $q->execute();

$res_ar = $res->fetchArray();
$count_blocked = (int)$res_ar['count(*)'];

if($inofsn < 0) $inofsn = 0;

//着信一覧
echo <<<EOT
<h3>着信拒否履歴(新しいものが上)</h3>
<table border=0 class="pure-table">
  <tr>
    <thead>
      <th>ID</th>
      <th>日時</th>
      <th>発信者番号</th>
      <th>着信先</th>
    </thead>
  </tr>
EOT;


$num_ents = 0;
$res_ar = array();
$qstr = "SELECT * FROM abslog WHERE KIND='BLOCKED' ORDER BY ID desc LIMIT 20" . " OFFSET " . $inofsn;

$q = $logdb->prepare($qstr);
$res = $q->execute();

while( $res_tmp = $res->fetchArray(1)){
    array_push($res_ar, $res_tmp);
}

foreach ($res_ar as $eent){
    $num_ents = $num_ents + 1;
    if($num_ents % 2 != 0){
        $tr_odd_class = '';
    } else {
        $tr_odd_class = 'class="pure-table-odd"';
    }

echo <<<EOT
    <tr $tr_odd_class>
      <td>
        {$eent["ID"]}
      </td>
      <td>
        {$eent["TIMESTAMP"]}
      </td>
      <td>
        <font color="black">
          {$eent["NUMBER"]}
        </font>
      </td>
      <td>
        {$eent["DESTNUM"]}
      </td>
    </tr>
EOT;

}

echo <<<EOT
    <tr>
      <td>
        <form action="" method="post">
          <input type="submit" value="<=">
          <input type="hidden" name="CTRL" value="prev">
          <input type="hidden" name="CTVAL" value=$inofsn>
          <input type="hidden" name="CTMAX" value=$count_blocked>
          <input type="hidden" name="function" value="inpgctrl">
        </form>
      </td>
      <td>
      </td>
      <td>
      </td>
      <td>
        <form action="" method="post">
          <input type="submit" value="=>">
          <input type="hidden" name="CTRL" value="next">
          <input type="hidden" name="CTVAL" value=$inofsn>
          <input type="hidden" name="CTMAX" value=$count_blocked>
          <input type="hidden" name="function" value="inpgctrl">
        </form>
      </td>
    </tr>
</table>
<BR>
<A HREF="index.php?page=call-log-page.php">戻る</A>
EOT;

//Close DB
$logdb->close();


?>
