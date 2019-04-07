<h2>内線情報設定</h2>
<?php

//
// extension update
//
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    if($_POST['function'] == 'extset'){
        $p_peer = $_POST['peer']; //処理対象のピア
        $p_exten = $_POST['exten']; //設定するexten
        $p_p_exten = $_POST['p_exten']; // 以前のexten
        $p_limit = $_POST['limit'];
        $p_ogcid = $_POST['ogcid'];
        $p_pgrp = $_POST['pgrp'];

        $peer_info_set = array();

        $peer_info_set['peer'] = $p_peer;
        $peer_info_set['exten'] = $p_exten;
        $peer_info_set['p_exten'] = $p_p_exten;
        $peer_info_set['limit'] = $p_limit;
        $peer_info_set['ogcid'] = $p_ogcid;
        $peer_info_set['pgrp'] = $p_pgrp;

        $notice_msg[$p_peer] = AbspFunctions\set_peer_info($peer_info_set);

        $p_macadd = trim($_POST['macadd']);
        if($p_macadd != ''){
            if(strpos($p_macadd, ':') === false){
                $tmp_str = str_split($p_macadd, 2);
                if(count($tmp_str) == 6){
                    $p_macadd = sprintf("%2s:%2s:%2s:%2s:%2s:%2s", $tmp_str[0],$tmp_str[1],$tmp_str[2],$tmp_str[3],$tmp_str[4],$tmp_str[5]);
                }
            } else {
                $tmp_str = explode(':', $p_macadd);
                if(count($tmp_str) != 6) $p_macadd = '';
            }
            if($p_macadd != ''){
                $p_macadd = strtoupper($p_macadd);
                AbspFunctions\put_db_item("ABS/PINFO/$p_peer", 'MAC', $p_macadd);
            } else {
                AbspFunctions\del_db_item("ABS/PINFO/$p_peer", 'MAC');
                $notice_msg[$p_peer] = '';
            }
        } else {
            AbspFunctions\del_db_item("ABS/PINFO/$p_peer", 'MAC');
            $notice_msg[$p_peer] = '';
        }
    }


    if($_POST['function'] == 'rgptset'){
        $p_rgpt = $_POST['rgpt'];
        AbspFunctions\put_db_item('ABS/EXT', 'RGPT', $p_rgpt);
    }

} /* end of update */


//
// ページ表示
//
echo <<<EOT
<table class="pure-table"  border=0>
  <tr>
    <thead>
      <font size="-1">
        <th>ピア名</th>
        <th><font color="red">*</font>内線番号</th>
        <th nowrap>規制値</th>
        <th>発信CID</th>
        <th>PickUp</th>
        <th>MACアドレス</th>
        <th>操作</th>
        <th></th>
      </font>
    </thead>
  </tr>
EOT;

for($i=1;$i<=$max_sip_phones;$i++){

    // デフォルト値
    $limit = '';
    $ogcid = '';
    $pgrp = '';
    $rgpt = '';
    $macadd = '';
    $p_exten = '';

    if($i % 2 != 0){ //even
        $tr_odd_class = '';
    } else { //odd
        $tr_odd_class = 'class="pure-table-odd"';
    }

    $peer_info = AbspFunctions\get_peer_info("phone$i");
    $exten = $peer_info['exten'];
    // 内線番号が割り当てられているピアのみ処理
    $limit_selected = array('0'=>'', '1'=>'', '2'=>'', '3'=>'', '4'=>'');
    if($exten != ''){
        $limit_val= $peer_info['limit'];
        $limit_selected["$limit_val"] = "selected";

        $exten = $peer_info['exten'];
        $p_exten = $exten;
        $ogcid = $peer_info['ogcid'];
        $pgrp  = $peer_info['pgrp'];
    } 

    $peer = $peer_info['peer'];

    //MACアドレスに関しては内線番号の有無にかかわらず処理
    $macadd = AbspFunctions\get_db_item("ABS/PINFO/$peer", 'MAC');

    if(isset($notice_msg[$peer])){
        $n_msg = $notice_msg[$peer];
    } else {
        $n_msg = '';
    }

echo <<<EOT
  <form action="" method="post">
    <tr $tr_odd_class>
      <td>
        phone$i 
        <input type="hidden" name="p_exten" value="$p_exten">
        <input type="hidden" name="peer" value="$peer">
      </td>
      <td>
        <input type="txt" size="5" name="exten" value="$exten">
      </td>
      <td nowrap>
        <select name="limit">
          <option value="0" {$limit_selected['0']}>0</option>
          <option value="1" {$limit_selected['1']}>1</option>
          <option value="2" {$limit_selected['2']}>2</option>
          <option value="3" {$limit_selected['3']}>3</option>
        </select>
      </td>
      <td>
        <input type="txt" size="12" name="ogcid" value="$ogcid">
      </td>
      <td>
        <input type="txt" size="2" name="pgrp" value="$pgrp">
      </td>
      <td>
        <input type="txt" size="14" name="macadd" value="$macadd">
      </td>
      <td>
        <input type="hidden" name="function" value="extset">
        <input id="$peer" type="submit" class={$_(ABSPBUTTON)} value="設定">
      </td>
      <td nowrap>
        <font size="-1" color="red">
          $n_msg
        </font>
      </td>
    </tr>
  </form>
EOT;

} /* end of for */

echo "</table>";
echo '<font size="-1">MACアドレスは電話機設定ファイル自動生成に使用されます</font>';


//鳴動パターン

    $rgpt_selected = array('1'=>'', '2'=>'', '3'=>'', '4'=>'', '5'=>'');
    $rgpt = AbspFunctions\get_db_item('ABS/EXT', 'RGPT');
    if($rgpt == '') $rgpt = '1';
    $rgpt_selected["$rgpt"] = "selected";

echo <<<EOT
<br>
<h3>内線時鳴動パターン</h3>
<form action="" method="post">
  <select name="rgpt" >
    <option value="1"  {$rgpt_selected['1']}>1</option>
    <option value="2"  {$rgpt_selected['2']}>2</option>
    <option value="3"  {$rgpt_selected['3']}>3</option>
    <option value="4"  {$rgpt_selected['4']}>4</option>
    <option value="5"  {$rgpt_selected['5']}>5</option>
  </select>
  <input type="hidden" name="function" value="rgptset">
  <input type="submit" class={$_(ABSPBUTTON)} value="設定">
</form>
EOT;
?>
