<h3>祝日・休日管理</h3>

<?php

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    if($_POST['function'] == 'modh'){ //祝・休設定
        if($_POST['day']){
            //更新前一旦全削除
            AbspFunctions\exec_cli_command('database deltree HOLIDAYS/JAPAN');
            $p_days = $_POST['day'];
            foreach($p_days as $d_line){
                $d_line = trim($d_line);
                AbspFunctions\put_db_item('HOLIDAYS/JAPAN', $d_line, '1');
            }
        }
    }

    if($_POST['function'] == 'addh'){ //独自データ追加
        $p_ndate = $_POST['ndate'];
        list($p_y, $p_m, $p_d) = explode('-', $p_ndate, 3);
        $p_ndate = sprintf("%4d-%02d-%02d", $p_y, $p_m, $p_d);
        $p_nname = $_POST['nname'];
        AbspFunctions\put_db_item('HOLIDAYS/JAPANBASE', $p_ndate, $p_nname);
    }

    if($_POST['function'] == 'deldate'){ //データ削除
        $p_ddate = $_POST['ddate'];
        $p_ddate = trim($p_ddate);
echo $p_ddate;
        list($p_y, $p_m, $p_d) = explode('-', $p_ddate, 3);
        $p_ddate = sprintf("%4d-%02d-%02d", $p_y, $p_m, $p_d);
        AbspFunctions\del_db_item('HOLIDAYS/JAPAN', $p_ddate);
        AbspFunctions\del_db_item('HOLIDAYS/JAPANBASE', $p_ddate);
    }

    if($_POST['function'] == 'gethbase'){ //基本データ更新
        $url = $_POST['turl'];
        $page = file_get_contents($url);
        $page = mb_convert_encoding($page, "UTF-8", "SJIS");
        $page = str_replace('/', '-', $page);
        $holidays = explode("\n", $page);
        $holidays = array_map('trim', $holidays);
        $numar = count($holidays);
        $lastd = $numar - 1;
        unset($holidays[0]);
        unset($holidays[$lastd]);

        //更新前全削除
        AbspFunctions\exec_cli_command('database deltree HOLIDAYS');

        //更新
        foreach($holidays as $line){
            $line = str_replace('"', '', $line);
            list($day, $name) = explode(',', $line, 2);
            list($p_y, $p_m, $p_d) = explode('-', $day, 3);
            $day = sprintf("%4d-%02d-%02d", $p_y, $p_m, $p_d);
            AbspFunctions\put_db_item('HOLIDAYS/JAPANBASE', $day, $name);
            AbspFunctions\put_db_item('HOLIDAYS/JAPAN', $day, '1');
        }
    }

}

echo <<<EOT
<form action="" method="post">
<table border="0" class="pure-table">
<tr>
<thead>
<th>休業日</th>
<th>日付</th>
<th>名称</th>
</thead>
</tr>
EOT;

    $i = 1;
    $ret = AbspFunctions\get_db_family('HOLIDAYS/JAPANBASE');
    $cret = AbspFunctions\get_db_family('HOLIDAYS/JAPAN');
    $cholidays = array();
    if($cret){
        foreach($cret as $cline){
            list($cday, $cname) = explode(' : ', $cline, 2);
            $cday = trim($cday);
            $cname = trim($cname);
            $cholidays += array("$cday"=>"$cname");
        }
    } 

    foreach($ret as $line){
        list($day, $name) = explode(' : ', $line, 2);
        $day = trim($day);
        $name = trim($name);

        $ischecked="";
        if(isset($cholidays[$day])){
            if($cholidays[$day] != '') $ischecked="checked";
        }


        if($i % 2 != 0){
            $tr_odd_class ='';
        } else {
            $tr_odd_class ='class="pure-table-odd"';
        }
        $i++;

echo <<<EOT
<tr $tr_odd_class>
<td>
<input type="checkbox" name="day[]" value="$day" $ischecked>
</td>
<td>
$day
</td>
<td>
$name
</td>
</tr>
EOT;

    } /* end foreach */

    if($i % 2 != 0){
        $tr_odd_class ='';
    } else {
        $tr_odd_class ='class="pure-table-odd"';
    }

echo <<<EOT
<tr $tr_odd_class>
<td>
</td>
<td>
</td>
<td>
<input type="submit" class={$_(ABSPBUTTON)} value="設定">
<input type="hidden" name="function" value="modh">
</td>
</tr>
</form>
</table>
<br>
EOT;

echo <<<EOT
<h3>独自データ追加</h3>
日付はハイフン区切り(YYYY-MM-DD)で入力してください。<br>
すでに存在する日付を指定すると名称が上書きされます。
<table border=0 class="pure-table">
<form action="" method="post">
<tr>
<thead>
<th>日付</th>
<th>名称</th>
<th></th>
</thead>
</tr>
<tr>
<td>
<input type="text" size="10" name="ndate">
</td>
<td>
<input type="text" size="10" name="nname">
</td>
<td>
<input type="submit" class={$_(ABSPBUTTON)} value="追加">
<input type="hidden" name="function" value="addh">
</td>
</tr>
</form>
</table>
<br>
EOT;

echo <<<EOT
<h3>個別データ削除</h3>
日付はハイフン区切り(YYYY-MM-DD)で入力してください。<br>
<table border=0 class="pure-table">
<form action="" method="post">
<tr>
<thead>
<th>日付</th>
<th></th>
</thead>
</tr>
<tr>
<td>
<input type="text" size="10" name="ddate">
</td>
<td>
<input type="submit" class={$_(ABSPBUTTON)} value="削除">
<input type="hidden" name="function" value="deldate">
</td>
</tr>
</form>
</table>
<br>
EOT;
?>

<hr>
<h3>祝日・休日情報再取得</h3>
内閣府の公開情報から取得します。URLは変更になる可能性があります。<br>
再取得を行うと追加した日付、設定情報も削除されますので注意してください。<br>
(全データが初期化されます)<br>
<form action="" method="post">
<input type="hidden" name="function" value="gethbase">
URL : <input type="text" size="60" name="turl" value="https://www8.cao.go.jp/chosei/shukujitsu/syukujitsu.csv">
<input type="submit" value="再取得">
</form>
