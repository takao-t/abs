<h3>GXP2135設定</h3>

<?php

    $vpk_use = array( 0, 1363, 1365, 1367, 1369, 1371, 1373,23800,23804,
                        23808,23812,23816,23820,23824,23828,23832,23836,
                        23840,23844,23848,23852,23856,23860,23864,23868);
    $vpk_acc = array( 0, 1364, 1366, 1368, 1370, 1372, 1374,23801,23805,
                        23809,23813,23817,23821,23825,23829,23833,23837,
                        23841,23845,23849,23853,23857,23861,23865,23869);
    $vpk_lbl = array( 0, 1465, 1467, 1469, 1471, 1473, 1475,23802,23806,
                        23810,23814,23818,23822,23826,23830,23834,23838,
                        23842,23846,23850,23854,23858,23862,23866,23870);
    $vpk_val = array( 0, 1466, 1468, 1470, 1472, 1474, 1476,23803,23807,
                        23811,23815,23819,23823,23827,23831,23835,23839,
                        23843,23847,23851,23855,23859,23863,23867,23871);

    $product_file = PROV_PATH . '/' . PROV_GS . '/' . 'cfggxp2135.xml';

    $msg = '';
    $content ='';
    $keyarg = array();
    $label = array();
    $pm_selected1 = '';
    $pm_selected2 = '';
    $pm_selected3 = '';

    for($i=1;$i<=24;$i++){
        $flex_keyarg[$i] = '';
        $flex_label[$i] = '';
        $flex_facil[$i] = '';
    }

    if($_SERVER['REQUEST_METHOD'] === 'POST'){

        if($_POST['function'] == 'keyset'){

            for($i=1;$i<=24;$i++){
                if(isset($_POST["flex_keyarg$i"])) $flex_keyarg[$i] = $_POST["flex_keyarg$i"];
                if(isset($_POST["flex_label$i"])) $flex_label[$i] = $_POST["flex_label$i"];
                if(isset($_POST["flex_facil$i"])) $flex_facil[$i] = $_POST["flex_facil$i"];
            }

$content = <<<EOT
<?xml version="1.0" encoding="UTF-8" ?>
<!-- Grandstream XML Provisioning Configuration -->
<gs_provision version="1">
  <config version="1">
    <P148>Grandstream GXP2135</P148>
EOT;
            $content .= "\n";

            for($i=1;$i<=24;$i++){
                if(isset($_POST["flex_facil$i"])){
                    $p_facil = $_POST["flex_facil$i"];
                    $content .= '    <!-- VPK' . $i  . " -->\n";
                    switch($p_facil){
                        case 'line':
                            if($i <= 8){
                              $content .= '    <P' . $vpk_use[$i] . '>0</P' . $vpk_use[$i] . ">\n";
                            } else {
                              $content .= '    <P' . $vpk_use[$i] . '>-1</P' . $vpk_use[$i] . ">\n";
                            }
                            break;
                        case 'blf':
                            if($i <= 8){
                              $content .= '    <P' . $vpk_use[$i] . '>11</P' . $vpk_use[$i] . ">\n";
                            } else {
                              $content .= '    <P' . $vpk_use[$i] . '>1</P' . $vpk_use[$i] . ">\n";
                            }
                            break;
                        case 'quick':
                            if($i <= 8){
                              $content .= '    <P' . $vpk_use[$i] . '>10</P' . $vpk_use[$i] . ">\n";
                            } else {
                              $content .= '    <P' . $vpk_use[$i] . '>0</P' . $vpk_use[$i] . ">\n";
                            }
                            break;
                        case 'park':
                            if($i <= 8){
                              $content .= '    <P' . $vpk_use[$i] . '>19</P' . $vpk_use[$i] . ">\n";
                            } else {
                              $content .= '    <P' . $vpk_use[$i] . '>9</P' . $vpk_use[$i] . ">\n";
                            }
                            break;
                        default :
                            if($i == 1 & $p_facil == "none"){
                              $content .= '    <P' . $vpk_use[$i] . '>0</P' . $vpk_use[$i] . ">\n";
                            } else {
                              $content .= '    <P' . $vpk_use[$i] . '>-1</P' . $vpk_use[$i] . ">\n";
                            }
                    }
                    $content .= '    <P' . $vpk_acc[$i] . '>0</P' . $vpk_acc[$i] . ">\n";

                    if(isset($_POST["flex_label$i"])){
                        $p_label = $_POST["flex_label$i"];
                        $content .= '    <P' . $vpk_lbl[$i] . '>' . $p_label . '</P' . $vpk_lbl[$i] . ">\n";
                    }
                    if(isset($_POST["flex_keyarg$i"])){
                        $p_keyarg = $_POST["flex_keyarg$i"];
                        $content .= '    <P' . $vpk_val[$i] . '>' . $p_keyarg . '</P' . $vpk_val[$i] . ">\n";
                    }
                }
            }

        }
$content .= <<<EOT
  </config>
</gs_provision>
EOT;

        if($_POST['function'] == 'savetofile'){
            if(isset($_POST['savechecked'])){
                if($_POST['savechecked'] == 'yes'){
                    $p_filename = trim($_POST['product_file']);
                    if($p_filename != ''){
                        $content = $_POST['content'];
                        $content = str_replace("\r", '', $content);
                        file_put_contents($p_filename, $content);
                        $msg = '<font color="red">'. $p_filename . 'に保存しました' . '</font>';
                    }
                }
            }
        } //savetofile

    } //END POST


echo <<<EOT
<h3>VPK設定</h3>
液晶左右,1ページあたり8キー表示(1～8が1ページ目,9～16が2ページ目,17～24が3ページ目)<br>
<br>
注：キー1は何も指定しない場合、デフォルトアカウントキーとなります<br>
$msg
<br>
<form action="" method="post">
<table border=0 class="pure-table">
  <tr>
    <thead>
      <th nowrap>キー番号</th>
      <th>ラベル</th>
      <th>機能</th>
      <th>値</th>
      <th></th>
      <th nowrap>キー番号</th>
      <th>ラベル</th>
      <th>機能</th>
      <th>値</th>
    </thead>
  </tr>
EOT;

     //液晶キー(VPK)
    $kstart = 1;
    $kend = 4;
    for($j=1;$j<4;$j++){

        for($i=$kstart;$i<=$kend;$i++){

            if($i % 2 != 0){ //even
                $tr_odd_class = '';
            } else { //odd
                $tr_odd_class = 'class="pure-table-odd"';
            }

            $key_left = $i;
            $key_right = $i+4;

            $sel_left1 = '';
            $sel_left2 = '';
            $sel_left3 = '';
            $sel_left4 = '';
            $sel_left5 = '';
            switch($flex_facil[$key_left]){
               case 'quick':
                 $sel_left2 = 'selected';
                 break;
               case 'line':
                 $sel_left3 = 'selected';
                 break;
               case 'blf':
                 $sel_left4 = 'selected';
                 break;
               case 'park':
                 $sel_left5 = 'selected';
                 break;
               default:
                 $sel_left1 = 'selected';
                 break;
            }

            $sel_right1 = '';
            $sel_right2 = '';
            $sel_right3 = '';
            $sel_right4 = '';
            $sel_right5 = '';
            switch($flex_facil[$key_right]){
               case 'quick':
                 $sel_right2 = 'selected';
                 break;
               case 'line':
                 $sel_right3 = 'selected';
                 break;
               case 'blf':
                 $sel_right4 = 'selected';
                 break;
               case 'park':
                 $sel_right5 = 'selected';
                 break;
               default:
                 $sel_right1 = 'selected';
                 break;
            }

echo <<<EOT
  <tr $tr_odd_class>
    <td>キー$key_left</td>
    <td>
      <input type="text" name="flex_label$key_left" size="8" value={$flex_label[$key_left]}>
    </td>
    <td>
      <select name="flex_facil$key_left">
        <option value="none" $sel_left1></option>
        <option value="quick" $sel_left2>クイック</option>
        <option value="line" $sel_left3>ライン</option>
        <option value="blf" $sel_left4>BLF</option>
        <option value="park" $sel_left5>パーク</option>
      </select>
    </td>
    <td>
      <input type="text" name="flex_keyarg$key_left" size="8" value={$flex_keyarg[$key_left]}>
    </td>
    <td></td>
    <td>キー$key_right</td>
    <td>
      <input type="text" name="flex_label$key_right" size="8" value={$flex_label[$key_right]}>
    </td>
    <td>
      <select name="flex_facil$key_right">
        <option value="" $sel_right1></option>
        <option value="quick" $sel_right2>クイック</option>
        <option value="line" $sel_right3>ライン</option>
        <option value="blf" $sel_right4>BLF</option>
        <option value="park" $sel_right5>パーク</option>
      </select>
    </td>
    <td>
      <input type="text" name="flex_keyarg$key_right" size="8" value={$flex_keyarg[$key_right]}>
    </td>
  </tr>
EOT;
        } //loop i

        $kstart += 8;
        $kend += 8;

        if($j != 3){
echo <<<EOT
  <tr>
    <td><hr></td>
    <td><hr></td>
    <td><hr></td>
    <td><hr></td>
    <td><hr></td>
    <td><hr></td>
    <td><hr></td>
    <td><hr></td>
    <td><hr></td>
  </tr>
EOT;
        }
    } //loop j

echo <<<EOT
</table>
<br>
EOT;


echo <<<EOT
</table>
<br>
<input type="hidden" name="function" value="keyset">
<input type="submit" class={$_(ABSPBUTTON)} value="生成実行">
</form>
EOT;

echo <<<EOT
<br>
<br>
生成結果<br>
<form action="" method="post">
<textarea cols="80" rows="30" name="content">
$content
</textarea>
<br>
<input type="hidden" name="function" value="savetofile">
<input type="hidden" name="product_file" value="$product_file">
内容を確認しました
<input type="checkbox" name="savechecked" value="yes">
$product_file として
<input type="submit" value="保存する">
<form>
<br>
<font color="red" size="-1">注意:同じ名前のファイルが存在すると上書きされます</font>
EOT;
?>

