<h3>GXP2130設定</h3>

<?php

    $mpk_use = array( 0, 323,324,325,326,327,328,329,353);
    $mpk_acc = array( 0, 301,304,307,310,313,316,319,354);
    $mpk_lbl = array( 0, 302,305,308,311,314,317,320,355);
    $mpk_val = array( 0, 303,306,309,312,315,318,321,356);

    $product_file = PROV_PATH . '/' . PROV_GS . '/' . 'cfggxp2130.xml';

    $msg = '';
    $content ='';
    $keyarg = array();
    $label = array();
    $pm_selected1 = '';
    $pm_selected2 = '';
    $pm_selected3 = '';

    for($i=1;$i<=8;$i++){
        $flex_keyarg[$i] = '';
        $flex_label[$i] = '';
        $flex_facil[$i] = '';
    }

    if($_SERVER['REQUEST_METHOD'] === 'POST'){


        if($_POST['function'] == 'keyset'){

            for($i=1;$i<=8;$i++){
                if(isset($_POST["flex_keyarg$i"])) $flex_keyarg[$i] = $_POST["flex_keyarg$i"];
                if(isset($_POST["flex_label$i"])) $flex_label[$i] = $_POST["flex_label$i"];
                if(isset($_POST["flex_facil$i"])) $flex_facil[$i] = $_POST["flex_facil$i"];
            }

$content = <<<EOT
<?xml version="1.0" encoding="UTF-8" ?>
<!-- Grandstream XML Provisioning Configuration -->
<gs_provision version="1">
  <config version="1">
    <P148>Grandstream GXP2130</P148>
EOT;
            $content .= "\n";

            for($i=1;$i<=8;$i++){
                if(isset($_POST["flex_facil$i"])){
                    $p_facil = $_POST["flex_facil$i"];
                    $content .= '    <!-- MPK' . $i  . " -->\n";
                    switch($p_facil){
                        case 'blf':
                            $content .= '    <P' . $mpk_use[$i] . '>1</P' . $mpk_use[$i] . ">\n";
                            break;
                        case 'quick':
                            $content .= '    <P' . $mpk_use[$i] . '>0</P' . $mpk_use[$i] . ">\n";
                            break;
                        case 'park':
                            $content .= '    <P' . $mpk_use[$i] . '>9</P' . $mpk_use[$i] . ">\n";
                            break;
                        default :
                            $content .= '    <P' . $mpk_use[$i] . '>-1</P' . $mpk_use[$i] . ">\n";
                            break;
                    }
                    $content .= '    <P' . $mpk_acc[$i] . '>0</P' . $mpk_acc[$i] . ">\n";

                    if(isset($_POST["flex_label$i"])){
                        $p_label = $_POST["flex_label$i"];
                        $content .= '    <P' . $mpk_lbl[$i] . '>' . $p_label . '</P' . $mpk_lbl[$i] . ">\n";
                    }
                    if(isset($_POST["flex_keyarg$i"])){
                        $p_keyarg = $_POST["flex_keyarg$i"];
                        $content .= '    <P' . $mpk_val[$i] . '>' . $p_keyarg . '</P' . $mpk_val[$i] . ">\n";
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
<h3>MPK設定</h3>
右下物理キー(左上が1で右下が8)<br>
ラベルは物理のため意味はないが参照用に設定しても可<br>
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

     //物理キー(MPK)
    $kstart = 1;
    $kend = 4;

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

