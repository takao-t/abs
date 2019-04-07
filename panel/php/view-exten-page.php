<h3>内線登録情報確認</h3>

<?php

echo <<<EOT
<br>
<table border="0" class="pure-table">
<tr>
<thead>
<th>内線番号</th>
<th>端末情報</th>
</thead>
</tr>
EOT;

    $peers = AbspFunctions\get_db_family('ABS/EXT');
    $num_counts = count($peers);

//要素から関係ないものを除く
$j = 0;
for($i=0;$i<$num_counts;$i++){
    list($exten, $peer) = explode(':', $peers[$i]);
    if(strpos($exten, '/') === false){ //スラッシュを含むのは別のパラメータ
        if(strpos($exten, 'RGPT') === false){
            if(strpos($exten, 'TMO') === false){
                $p_peers[$j] = $exten . ':' . $peer;
                $j++;
            }
        }
    }
}

for($i=0;$i<$j;$i++){
list($exten, $peer) = explode(':', $p_peers[$i]);

    if($i % 2 == 0){
        $tr_odd_class = '';
    } else {
        $tr_odd_class = 'class="pure-table-odd"';
    }

echo <<<EOT
<tr $tr_odd_class>
<td align="right">
$exten
</td>
<td nowrap>
$peer
</td>
</tr>
EOT;

} /* end of for */

echo "</tr>";
echo "</table>";
echo "<br>";

    $ext_handle = fopen('internalexten.txt', 'r');

echo <<<EOT
<br>
<table border="0" class="pure-table">
<tr>
<thead>
<th>特番</th>
<th>機能</th>
<th>補足説明</th>
</thead>
</tr>
EOT;

    $i = 0;
    while($line = fgetcsv($ext_handle)){

    if($i % 2 == 0){
        $tr_odd_class = '';
    } else {
        $tr_odd_class = 'class="pure-table-odd"';
    }
    $i++;

echo <<<EOT
<tr $tr_odd_class>
<td>
$line[0]
</td>
<td>
$line[1]
</td>
<td>
$line[2]
</td>
</tr>
EOT;

    } /* end of while */
    fclose($ext_handle);
?>

