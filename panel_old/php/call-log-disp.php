<h2>着信履歴</h2>

<?php

$bl_addmsg = '';

    if($_SERVER['REQUEST_METHOD'] === 'POST'){

         //拒否リストへ登録
        if($_POST['function'] == 'bladd'){
          $p_cid = trim($_POST['blcid']);
          if(isset($_POST['blchecked'])) $p_yesno = trim($_POST['blchecked']);
          else $p_yesno = 'no';
          if($p_yesno == 'YES'){
            $nowdt = new DateTime('NOW');
            $tmpdt = $nowdt->format('Y-m-d/H:i:s');
            AbspFunctions\put_db_item('ABS/blocklist', $p_cid, $tmpdt);
            $bl_addmsg = $p_cid . " を着信拒否リストに登録しました";
          }
        }

        //着信履歴ページ制御
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
//着信
$q = $logdb->prepare("SELECT count(*) FROM abslog WHERE KIND='INCOMING'");
$res = $q->execute();
$res_ar = $res->fetchArray();
$count_incoming = (int)$res_ar['count(*)'];

if($inofsn < 0) $inofsn = 0;

//着信一覧
echo <<<EOT
<h3>着信履歴(新しいものが上)</h3>
<font color="red">
$bl_addmsg
</font>
<table border=0 class="pure-table">
  <tr>
    <thead>
      <th>ID</th>
      <th>日時</th>
      <th>発信者番号</th>
      <th>発信者名</th>
      <th>着信先</th>
      <th>拒否登録</th>
    </thead>
  </tr>
EOT;

$num_ents = 0;
$res_ar = array();
$qstr = "SELECT * FROM abslog WHERE KIND='INCOMING' ORDER BY ID desc LIMIT 20" . " OFFSET " . $inofsn;

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

    //落ち先チェック
    $in_dest = '';
    $in_destc = trim(AbspFunctions\get_db_item('ABS/TRUNK/' . $eent['DESTNUM'] , 'KEY'));
    if($in_destc != ""){//キー着該当あり
        $in_dest = '(K' . $in_destc . ')';
    }
    $in_destc = trim(AbspFunctions\get_db_item('ABS/DID' , $eent['DESTNUM']));
    if($in_destc != ""){//DID着信
        $in_dest = "(DIN)";
    }
    $in_destc = trim(AbspFunctions\get_db_item('ABS/IVR/NUM' , $eent['DESTNUM']));
    if($in_destc != ""){//IVR着信
        $in_dest = "(IVR)";
    }
    $in_destc = trim(AbspFunctions\get_db_item('ABS/IVR/DIR/' . $eent['DESTNUM'], 'CTX'));
    if($in_destc != ""){//IVRダイレクト
        $in_dest = "(IVR-D)";
    }


    //拒否リストにあるかどうかチェック
    $f_color = 'black';
    $bl_tmp = trim(AbspFunctions\get_db_item('ABS/blocklist', $eent['NUMBER']));
    if($bl_tmp != ""){
        $f_color = 'red';
    }
    //CID名取得
    $t_cidname = trim(AbspFunctions\get_db_item('cidname', $eent['NUMBER']));


echo <<<EOT
    <tr $tr_odd_class>
      <td>
        {$eent['ID']}
      </td>
      <td>
        {$eent['TIMESTAMP']}
      </td>
      <td>
          <a href="index.php?page=cid-config-page.php&post_pnum={$eent['NUMBER']}">
            <font color=$f_color>
              {$eent['NUMBER']}
            </font>
          </a>
      </td>
      <td>
        $t_cidname
      </td>
      <td>
        {$eent['DESTNUM']}
        $in_dest
      </td>
      <td>
        <form action="" method="POST">
          <input type="hidden" name="function" value="bladd">
          <input type="checkbox" name="blchecked" value="YES">
          <input type="hidden" name="blcid" value="{$eent['NUMBER']}">
          <input type="submit" class={$_(ABSPBUTTON)} value="登録">
        </form>
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
          <input type="hidden" name="CTMAX" value=$count_incoming>
          <input type="hidden" name="function" value="inpgctrl">
        </form>
      </td>
      <td>
      </td>
      <td>
      <td>
      <td>
      </td>
      </td>
      </td>
      <td>
        <form action="" method="post">
          <input type="submit" value="=>">
          <input type="hidden" name="CTRL" value="next">
          <input type="hidden" name="CTVAL" value=$inofsn>
          <input type="hidden" name="CTMAX" value=$count_incoming>
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
