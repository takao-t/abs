<h2>内線グループ設定</h2>

<?php

$msg = '';
$bnl_selected = array('0'=>'', '1'=>'');
$ovr_selected = array('0'=>'', '1'=>'');

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    if(isset($_POST['section'])){
        $section = $_POST['section'];
    }

    if($section == 'grp_update'){ //グループ情報更新
        $p_member = $_POST['grp_member'];
        $p_grp   = $_POST['grp'];
        $p_mode   = $_POST['mode'];
        $p_exten  = $_POST['exten'];
        $p_timeout= $_POST['timeout'];
        $p_ovr    = $_POST['ovr'];
        $p_bnl    = $_POST['bnl'];
        $p_bnt    = $_POST['bnt'];

        $group_info_set = array();

        $group_info_set['member'] = $p_member;
        $group_info_set['group'] = $p_grp;
        $group_info_set['mode'] = $p_mode;
        $group_info_set['exten'] = $p_exten;
        $group_info_set['timeout'] = $p_timeout;
        $group_info_set['ovr'] = $p_ovr;
        $group_info_set['bnl'] = $p_bnl;
        $group_info_set['bnt'] = $p_bnt;

        $notice_msg_g[$p_grp] = '';
        $notice_msg_g[$p_grp] = AbspFunctions\set_group_info($group_info_set);

    } //グループ更新

    if($section == 'pgrp_update'){ //ピックアップグループ更新
        $p_member = $_POST['pgrp_member'];
        $p_pgrp   = $_POST['pgrp'];

        $notice_msg_p[$p_pgrp] = '';
        $notice_msg_p[$p_pgrp] = AbspFunctions\set_pgrp_member($p_pgrp, $p_member);
    } //ピックアップグループ更新
} /* end of update */

echo <<<EOT
<table class="pure-table" border=0>
  <tr>
    <thead>
      <th>番号</th>
      <th><font color="red">*</font>所属内線(カンマ区切り)</th>
      <th>内線番号</th>
      <th>モード</th>
      <th>タイムアウト</th>
      <th>話中検出</th>
      <th>キュー</th>
      <th>待機</th>
      <th>操作</th>
      <th></th>
    </thead>
  </tr>
EOT;

for($i=1;$i<=$max_group;$i++){

    // デフォルト値
    $member = '';
    $timeout = '';
    $mode = '';
      $mode_selected0 = "selected";
      $mode_selected1 = "";
      $mode_selected2 = "";
    $exten = '';
      $bnl_selected0 = "selected";
      $bnl_selected1 = "";
    $ovr = '';
      //$ovr_selected0 = "selected";
      //$ovr_selected1 = "";
      $ovr_selected0 = "checked=\"checked\"";
      $ovr_selected1 = "";
    $bnt = '';
    $bnl = '';

    if($i % 2 != 0){
        $tr_odd_class ='';
    } else {
        $tr_odd_class ='class="pure-table-odd"';
    }

    // グループ情報取得
    $group_info = AbspFunctions\get_group_info($i);
    $member = $group_info['member'];
    // グループメンバが存在しない場合には意味がないので他の項目を処理しない
    $mode_selected = array('RA'=>'', 'RR'=>'', 'RM'=>'');
    if($member != ''){
        $timeout = $group_info['timeout'];
        $mode = $group_info['mode'];
        $mode_selected["$mode"] = "selected";

        $exten = $group_info['exten'];

        $ovr_selected = array('0'=>'', '1'=>'');
        $ovr = $group_info['ovr'];
        $ovr_selected["$ovr"] = "selected";

        $bnl_selected = array('0'=>'', '1'=>'');
        $bnl = $group_info['bnl'];
        $bnl_selected["$bnl"] = "selected";

        $bnt = $group_info['bnt'];
    }

    if(isset($notice_msg_g[$i])){
        $msg = $notice_msg_g[$i];
    } else {
        $msg = '';
    }

echo <<<EOT
  <form action="" method="post">
    <input type="hidden" name="section" value="grp_update">
    <tr $tr_odd_class>
      <td align="right">
        G$i 
      </td>
      <td nowrap>
        <input type="hidden" name="grp" value="$i">
        <input type=\"txt\" size="24" name="grp_member" value="$member">
      </td>
      <td nowrap>
        <input type=\"txt\" size="5" name="exten" value="$exten">
      </td>
      <td>
        <select name="mode">
          <option value="RA" {$mode_selected['RA']}>RA</option>
          <option value="RR" {$mode_selected['RR']}>RR</option>
          <option value="RM" {$mode_selected['RM']}>RM</option>
        </select>
      </td>
      <td>
        <input type="txt" size="2" name="timeout" value="$timeout">
      </td>
      <td>
        <select name="ovr">
          <option value="0" {$ovr_selected['0']}>有効</option>
          <option value="1" {$ovr_selected['1']}>無効</option>
      </td>
      <td>
      <select name="bnl">
        <option value="0" {$bnl_selected['0']}>いいえ</option>
        <option value="1" {$bnl_selected['1']}>はい</option>
      </td>
      <td>
        <input type="txt" size="2" name="bnt" value="$bnt">
      </td>
      <td>
        <input id="group$i" type="submit" class={$_(ABSPBUTTON)} value="設定">
      </td>
      <td nowrap>
        <font size="-1" color="red">
          $msg
        </font>
      </td>
    </tr>
  </form>
EOT;

} /* end of for */

echo "</table>";
echo "<br>";


echo <<<EOT
<h3>ピックアップグループ設定</h3>
<table class="pure-table" border=0>
  <tr>
    <thead>
      <th>番号</th>
      <th><font color="red">*</font>所属内線</th>
      <th>操作</th>
      <th></th>
    </thead>
  </tr>
EOT;

for($i=1;$i<=$max_pgroup;$i++){

    if($i % 2 == 0){
        $tr_odd_class = '';
    } else {
        $tr_odd_class = 'class="pure-table-odd"';
    }

    $pgrp_member = AbspFunctions\get_pgrp_member($i);

    if(isset($notice_msg_p[$i])){
        $msg = $notice_msg_p[$i];
    } else {
        $msg = '';
    }

echo <<<EOT
  <form action="#pgrp$i" method="POST">
    <input type="hidden" name="section" value="pgrp_update">
    <tr $tr_odd_class>
      <td align="right">
        $i
      </td>
      <td>
        <input type="hidden" name="pgrp" value="$i">
        <input type="txt" size="24" name="pgrp_member" value="$pgrp_member">
      </td>
      <td>
        <input id="pgrp$i" type="submit" class={$_(ABSPBUTTON)} value="設定">
      </td>
      <td nowrap>
        <font size="-1" color="red">
          $msg
        </font>
      </td>
    </tr>
  </form>
EOT;
} /* end of pgrp for */

echo "</table>";

?>

