<h2>キーシステム設定</h2>

<?php

$msg = '';

//登録処理
if($_SERVER['REQUEST_METHOD'] === 'POST'){

    if($_POST['function'] == 'eachkey'){
      if(isset($_POST['key_num'])){
          $p_key = $_POST['key_num'];
          $p_label = $_POST['label'];
          $p_tech = $_POST['tech'];
          $p_trunk = $_POST['trunk'];
          $p_type = $_POST['key_type'];
          $p_ogcid = $_POST['ogcid'];
          $p_rgrp = $_POST['rgrp'];
          $p_rgpt = $_POST['rgpt'];
          $p_bpin = $_POST['bpin'];
          $p_mmd = $_POST['mmd'];

          $key_info_set = array();

          $key_info_set['key'] = $p_key;
          $key_info_set['label'] = $p_label;
          $key_info_set['tech'] = $p_tech;
          $key_info_set['trunk'] = $p_trunk;
          $key_info_set['type'] = $p_type;
          $key_info_set['ogcid'] = $p_ogcid;
          $key_info_set['rgrp'] = $p_rgrp;
          $key_info_set['rgpt'] = $p_rgpt;
          $key_info_set['bpin'] = $p_bpin;
          $key_info_set['mmd'] = $p_mmd;

          $notice_msg[$p_key] = AbspFunctions\set_key_info($key_info_set);
      }
    }

    if($_POST['function'] == 'trunkadd'){
        if(isset($_POST['trunk'])){
            $p_trunk = trim($_POST['trunk']);
            $p_key = $_POST['keynum'];
            AbspFunctions\put_db_item("KEYTEL/KEYSYS$p_key", 'TRUNK', $p_trunk);
        }
    }
} /* */


echo <<<EOT
<table class="pure-table" border=0>
  <tr>
    <thead>
      <th>番号</th>
      <th>ラベル</th>
      <th>TECH</th>
      <th nowrap>トランク</th>
      <th>種別</th>
      <th>発信CID</th>
      <th nowrap>着信</th>
      <th>RING</th>
      <th>割込PIN</th>
      <th nowrap>割込MD</th>
      <th>操作</th>
      <th></th>
    </thead>
  </tr>
EOT;

for($i=1;$i<=$max_keys;$i++){

    // デフォルト値
    $label = '';
    $tech = 'SIP';
    $ktype = '';
    $ogcid = '';
    $rgrp = '';
    $bpin = '';

    if($i % 2 != 0){
        $tr_odd_class = '';
    } else {
        $tr_odd_class = 'class="pure-table-odd"';
    }

    $key_info = AbspFunctions\get_key_info($i);

    $trunk = $key_info['trunk'];

    $label = $key_info['label'];

    $tech_selected = array('SIP'=>'', 'PJSIP'=>'');
    $tech = $key_info['tech'];
    $tech_selected["$tech"] = "selected";

    $type_selected = array('none'=>'', 'NTTE'=>'', 'NTTW'=>'', 'BASIX'=>'', 'UAREA'=>'');
    $ktype = $key_info['type'];
    if($ktype == '') $type_selected['none'] = "selected";
      else $type_selected["$ktype"] = "selected";

    $ogcid = $key_info['ogcid'];

    $rgrp = $key_info['rgrp'];
    $selectors = AbspFunctions\create_target_list('group', $rgrp);

    $bpin = $key_info['bpin'];

    $rgpt_selected = array('1'=>'', '2'=>'', '3'=>'', '4'=>'', '5'=>'');
    $rgpt = $key_info['rgpt'];
    $rgpt_selected["$rgpt"] = "selected";

    $mmd_selected = array('1'=>'', '2'=>'');
    $mmd = $key_info['mmd'];
    if($mmd == 'S') $mmd_selected['2'] = "selected";
      else $mmd_selected['1'] = "selected";

    if(isset($notice_msg[$i])){
        $msg = $notice_msg[$i];
    } else {
        $msg = '';
    }
 
    $trunk_list = AbspFunctions\create_trunk_list('', $trunk);
    if($trunk == ''){
        $trunk_list = "<select name=\"trunk\">" . $trunk_list . "</select>";
    } else if(strpos($trunk_list, $trunk) === false){
        $trunk_list = "<input type=\"text\" size=\"8\" name=\"trunk\" value=\"$trunk\">";
    } else {
        $trunk_list = "<select name=\"trunk\">" . $trunk_list . "</select>";
    }

echo <<<EOT
  <form action="" method="post">
    <input type="hidden" name="key_num" value="$i">
    <tr $tr_odd_class>
      <td align="right">
        KEY$i 
      </td>
      <td nowrap>
        <input type="txt" size="6" name="label" value="$label">
      </td>
      <td>
        <select name="tech">
          <option value="SIP" {$tech_selected['SIP']}>SIP</option>
          <option value="PJSIP" {$tech_selected['PJSIP']}>PJSIP</option>
        </select>
      </td>
      <td nowrap>
        $trunk_list
      </td>
      <td>
        <select name="key_type">
          <option value=""  {$type_selected['none']}>なし</option>
          <option value="NTTE"  {$type_selected['NTTE']}>NTT東</option>
          <option value="NTTW"  {$type_selected['NTTW']}>NTT西</option>
          <option value="BASIX" {$type_selected['BASIX']}>BASIX</option>
          <option value="UAREA" {$type_selected['UAREA']}>ユーザ定義</option>
        </select>
      </td>
      <td>
        <input type="txt" size="10" name="ogcid" value="$ogcid">
      </td>
      <td>
        <select name="rgrp">
          $selectors
        </select>
      </td>
      <td>
        <select name="rgpt">
          <option value="1"  {$rgpt_selected['1']}>1</option>
          <option value="2"  {$rgpt_selected['2']}>2</option>
          <option value="3"  {$rgpt_selected['3']}>3</option>
          <option value="4"  {$rgpt_selected['4']}>4</option>
          <option value="5"  {$rgpt_selected['5']}>5</option>
        </select>
      </td>
      <td>
        <input type="txt" size="4" name="bpin" value="$bpin">
      </td>
      <td>
        <select name="mmd">
          <option value="B" {$mmd_selected['1']}>Bin</option>
          <option value="S" {$mmd_selected['2']}>Spy</option>
        </select>
      </td>
      <td>
        <input type="hidden" name="function" value="eachkey">
        <input id="key_$i" type="submit" class={$_(ABSPBUTTON)} value="設定">
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

echo "</tr>";
echo "</table>";
echo "<br>";

$key_select_list ='';
for($i=1;$i<=32;$i++){
    $key_select_list .= "<option value=\"$i\">KEY$i";
}

echo  <<<EOT
<h3>手動トランク設定</h3>
<form action="" method="post">
<table border=0 class="pure-table">
  <tr>
    <thead>
      <th>キー</th>
      <th>トランク名</th>
      <th></th>
    </thead>
  </tr>
    <td>
      <select name="keynum">
        $key_select_list
      </select>
    </td>
    <td>
      <input type=text name="trunk" size="8">
    </td>
    <td>
      <input type="hidden" name="function" value="trunkadd">
      <input type="submit" class={$_(ABSPBUTTON)} value="設定">
    </td>
  </tr>
</table>
</form>
EOT;


?>
