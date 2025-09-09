<h2 id="bllist">内線一括管理</h2>

<?php
if(!isset($peer_info)){
    $peer_info = array();
}
if(!isset($exten)){
    $exten = '';
}

$msg ='';

$tmp_csv_dir  = BACKUPDIR;
$tmp_csv_file = $tmp_csv_dir . '/extconf-tmpfile.csv';

//peer_infoの初期化(読み込まない位置もあるので)
function init_peerinfo(){
    global $peer_info;
    global $max_sip_phones;
    for($i=1;$i<=$max_sip_phones;$i++){
        $peer_info[$i]['exten'] = '';
        $peer_info[$i]['p_exten'] = '';
        $peer_info[$i]['cidn'] = '';
        $peer_info[$i]['limit'] = '';
        $peer_info[$i]['ogcid'] = '';
        $peer_info[$i]['pgrp'] = '';
        $peer_info[$i]['macadd'] = '';
        $peer_info[$i]['peer'] = '';
    }
}

//CSVファイルをpeer_infoに読み込む
function csv_to_peerinfo($file){
    global $peer_info;

    init_peerinfo();

    $extcsv = file($file);
    $icnt = 0;

    foreach($extcsv as $line){
        //1行目は捨てる
        if($icnt != 0){
            list($peer, $exten, $cidn, $limit, $ogcid, $pgrp, $macadd) = explode(',', $line, 7);
            $peer = str_replace('"', '', $peer);
            $peer_num = str_replace('phone', '', $peer);
            $exten = str_replace('"', '', $exten);
            $cidn = str_replace('"', '', $cidn);
            $cidn = mb_convert_encoding($cidn, 'UTF-8', 'SJIS');
            $limit = str_replace('"', '', $limit);
            $ogcid = str_replace('"', '', $ogcid);
            $pgrp = str_replace('"', '', $pgrp);
            $macadd = str_replace('"', '', $macadd);
            $peer_info[$peer_num]['peer'] = $peer;
            $peer_info[$peer_num]['exten'] = $exten;
            $peer_info[$peer_num]['cidn'] = $cidn;
            $peer_info[$peer_num]['limit'] = $limit;
            $peer_info[$peer_num]['ogcid'] = $ogcid;
            $peer_info[$peer_num]['pgrp'] = $pgrp;
            $peer_info[$peer_num]['macadd'] = $macadd;
        }
        $icnt = $icnt +1;
    }
}


// POST処理
if($_SERVER['REQUEST_METHOD'] === 'POST'){

    //現在の設定情報(ABSの)を読み込み
    //それぞれの内線情報とCID名、MACアドレスも
    if($_POST['function'] == 'readexten'){
        @mkdir($tmp_csv_dir);
        $csv_line = '"ピア名","内線番号","発信者名","規制値","発信CID","PickUp","MACアドレス"' . "\r\n";
        $csv_line = mb_convert_encoding($csv_line, "SJIS");

        for($i=1;$i<=$max_sip_phones;$i++){
            $peer = "phone" . $i;
            $peer_info[$i] = AbspFunctions\get_peer_info($peer);
            if($peer_info[$i]['exten'] != ''){
                $macadd = AbspFunctions\get_db_item("ABS/PINFO/$peer", 'MAC');
                $peer_info[$i]['macadd'] = $macadd;
                $cidn = AbspFunctions\get_db_item("cidname", $peer_info[$i]['exten']);
                $peer_info[$i]['cidn'] = $cidn;
                $cidn = mb_convert_encoding($cidn, "SJIS");
                $csv_line .= '"' . $peer . '"';
                $csv_line .= ',"' . $peer_info[$i]['exten'] . '"';
                $csv_line .= ',"' . $cidn . '"';
                $csv_line .= ',"' . $peer_info[$i]['limit'] . '"';
                $csv_line .= ',"' . $peer_info[$i]['ogcid'] . '"';
                $csv_line .= ',"' . $peer_info[$i]['pgrp'] . '"';
                $csv_line .= ',"' . $macadd . '"';
                $csv_line .= "\r\n";
            }

        }
        file_put_contents($tmp_csv_file, $csv_line);
    }

    //現在の情報(画面上)を保存
    //実体は一時CSVを保存用にコピーするだけ
    if($_POST['function'] == 'saveexten'){

        if(isset($_POST['confirm'])){
            if($_POST['confirm'] == "yes"){
                $current =  date("Ymd-His");
                if(fopen($tmp_csv_file,'r') !== false){
                    $target = BACKUPDIR . "/extconf-" . $current . '.csv';
                    copy($tmp_csv_file, $target);
                    $msg = '<font color=#ff0000>' . $target . " に保存しました</font>";
                }
            }
        }
        csv_to_peerinfo($tmp_csv_file);
    }

    //ファイルの情報を画面に反映
    //ファイルを一時CSVにコピーして反映
    if($_POST['function'] == 'readfile'){
        if($_POST['file']){
            $p_file = trim($_POST['file']);
            $target = BACKUPDIR . '/' . $p_file;
            if(fopen($target, 'r') !== false){
                copy($target, $tmp_csv_file);
            }
        }
        csv_to_peerinfo($tmp_csv_file);
    }

    //内線情報更新
    if($_POST['function'] == 'ovrexten'){
        csv_to_peerinfo($tmp_csv_file);
        if($_POST['confirm'] == "yes"){
            //CID上書きあり
            if(isset($_POST['cidovr'])){
                if($_POST['cidovr'] == "yes") $p_cidovr = 'yes';
                else $p_cidovr = 'no';
            } else {
                $p_cidovr = 'no';
            }
            //MACアドレス上書きあり
            if(isset($_POST['macovr'])){
                if($_POST['macovr'] == "yes") $p_macovr = 'yes';
                else $p_macovr = 'no';
            } else {
                $p_macovr = 'no';
            }

            //内線情報を削除
            AbspFunctions\del_db_tree('ABS/EXT');
            AbspFunctions\del_db_tree('ABS/ERV');
            //規制値も削除
            AbspFunctions\del_db_tree('ABS/LMT');

            //内線情報を登録
            for($i=1;$i<=$max_sip_phones;$i++){
                //MACアドレス上書きありなら更新
                if($p_macovr == 'yes'){
                    $p_dbitem = 'ABS/PINFO/phone' .$i;
                    if(trim($peer_info[$i]['macadd']) == ''){
                        AbspFunctions\del_db_item($p_dbitem, 'MAC');
                    } else {
                        AbspFunctions\put_db_item($p_dbitem, 'MAC', $peer_info[$i]['macadd']);
                    }
                }
                //内線番号のあるものは登録
                if($peer_info[$i]['exten'] != ''){
                    AbspFunctions\set_peer_info($peer_info[$i]);
                    //CID上書きありなら更新
                    if($p_cidovr == 'yes'){
                        $p_dbitem = 'cidname';
                        if(trim($peer_info[$i]['cidn']) == ''){
                            AbspFunctions\del_db_item($p_dbitem, $peer_info[$i]['exten']);
                        } else {
                            AbspFunctions\put_db_item($p_dbitem, $peer_info[$i]['exten'], $peer_info[$i]['cidn']);
                        }
                    }
                }
            }
        }
    }

}

echo <<<EOT
<table class="pure-table"  border=0>
  <tr>
    <thead>
      <font size="-1">
        <th>ピア名</th>
        <th>内線番号</th>
        <th>発信者名</th>
        <th>規制値</th>
        <th>発信CID</th>
        <th>PickUp</th>
        <th>MACアドレス</th>
      </font>
    </thead>
  </tr>
EOT;

// 一覧表示部
$j = 1;

if(count($peer_info) > 0){
    for($i=1;$i<=$max_sip_phones;$i++){

        // デフォルト値
        $limit_val = '';
        $ogcid = '';
        $pgrp = '';
        $rgpt = '';
        $macadd = '';
        $p_exten = '';

        if($j % 2 != 0){ //even
            $tr_odd_class = '';
        } else { //odd
            $tr_odd_class = 'class="pure-table-odd"';
        }

        if(!isset($peer_info[$i]['exten'])) continue;

        $exten = $peer_info[$i]['exten'];
        // 内線番号が割り当てられているピアのみ処理
        if($exten != ''){
            $limit_val= $peer_info[$i]['limit'];
            $exten = $peer_info[$i]['exten'];
            $p_exten = $exten;
            $ogcid = $peer_info[$i]['ogcid'];
            $pgrp  = $peer_info[$i]['pgrp'];
            $peer = $peer_info[$i]['peer'];
            $macadd = $peer_info[$i]['macadd'];
            $cidn = $peer_info[$i]['cidn'];
            $j++;
        }

//内線番号登録のあるもののみ表示
        if($exten != ''){
echo <<<EOT
  <form action="" method="post">
    <tr $tr_odd_class>
      <td>
        phone$i
      </td>
      <td>
        $exten
      </td>
      <td>
        $cidn
      </td>
      <td>
        $limit_val
      </td>
      <td>
        $ogcid
      </td>
      <td>
        $pgrp
      </td>
      <td>
        $macadd
      </td>
    </tr>
  </form>
EOT;
        }

    } /* end of for */
}

echo "</table>";

echo <<<EOT
<br>
<hr>
<h3>現在情報読み込み</h3>
<form action="" method="post">
<input type="hidden" name="function" value="readexten">
現在の内線情報を
<input type="submit" class={$_(ABSPBUTTON)} value="読み込む">
</form>
<hr>
<h3>内線情報保存</h3>
上の情報をファイルに保存します。保存したファイルは [バックアップ] ページからダウンロードできます。
<form action="" method="post">
<input type="hidden" name="function" value="saveexten">
この内線情報を
<input type="checkbox" name="confirm" value="yes">
<input type="submit" class={$_(ABSPBUTTON)} value="保存する">
</form>
$msg
<br>
<hr>
<h3>内線設定更新</h3>
上の情報で現在の内線設定を書き換えます。
<form action="" method="post">
<input type="hidden" name="function" value="ovrexten">
この内線情報で設定を
<input type="checkbox" name="cidovr" value="yes">
CIDを書き換え
<input type="checkbox" name="macovr" value="yes">
MACアドレスも書き換え
<input type="checkbox" name="confirm" value="yes">
内線情報を上書きを
<input type="submit" class={$_(ABSPBUTTON)} value="実行する">
</form>
<hr>
EOT;

    $dir = opendir(BACKUPDIR);
    if($dir !== FALSE){
        while (false !== ($file_list[] = readdir($dir)));
    } else {
        $file_list = '';
    }
    closedir($dir);
    sort($file_list);

echo <<<EOT
<h3>設定ファイル</h3>
設定ファイルの内容を読み込みます。ファイルは [リストア] ページがらアップロードできます。<br>
注: extconf-tmpfile.csv は"この画面"の内容の一時ファイルです。
<table border=0 class="pure-table">
  <tr>
    <thead>
      <th>ファイル名</th>
      <th>読み込み</th>
    </thead>
  </tr>
EOT;

    $i = 0;

    foreach($file_list as $key=>$file){
       if($file == '.htaccess') continue;
       $target = BACKUPDIR . '/' . $file;
       if(is_file($target)) {
           if(preg_match('/extconf-/', $file) && preg_match('/.csv/', $file)){

               if($i % 2 == 0){
                   $tr_odd_class = '';
               } else {
                   $tr_odd_class = 'class="pure-table-odd"';
               }
               $i++;

               $f_path = BACKUPDIR . '/' . $file;
echo <<<EOT
  <tr $tr_odd_class>
    <td>
      $file
    </td>
    <td>
     <form action="" method="post">
     <input type="hidden" name="file" value="$file">
     <input type="hidden" name="function" value="readfile">
     <input type="submit" class={$_(ABSPBUTTON)} value="読み込み">
     </form>
    </td>
  </tr>
EOT;
           }
       }
    }

?>
