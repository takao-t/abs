<h3>KX-HDV330N設定</h3>

<?php

    $product_file = PROV_PATH . '/' . PROV_PANA . '/' . 'Config-KX-HDV330N.cfg';

    $msg = '';
    $content ='';
    $keyarg = array();
    $label = array();
    $pm_selected1 = '';
    $pm_selected2 = '';
    $pm_selected3 = '';
    $dss_checkd = '';

    for($i=1;$i<=40;$i++){
        $dss_keyarg[$i] = '';
        $dss_label[$i] = '';
        $dss_facil[$i] = '';
    }
    for($i=1;$i<=24;$i++){
        $flex_keyarg[$i] = '';
        $flex_label[$i] = '';
        $flex_facil[$i] = '';
    }

    if($_SERVER['REQUEST_METHOD'] === 'POST'){

        $dss_checkd = '';

        if(isset($_POST['phonemodel'])){
            $pm_selected1 = '';
            $pm_selected2 = '';
            $pm_selected3 = '';
            switch($_POST['phonemodel']){
              case 'hdv230':
                $pmodel = '230';
                $pm_selected1 = 'selected';
                break; 
              case 'hdv330':
                $pmodel = '330';
                $pm_selected2 = 'selected';
                break; 
              case 'hdv430':
                $pmodel = '430';
                $pm_selected3 = 'selected';
                break; 
              default:
                $pmodel = '330';
                $pm_selected1 = 'selected';
                break; 
            }
            $product_file = PROV_PATH . '/' . PROV_PANA . '/' . 'Config-KX-HDV' . $pmodel . 'N.cfg';
        }

        if($_POST['function'] == 'keyset'){

            for($i=1;$i<=24;$i++){
                if(isset($_POST["flex_keyarg$i"])) $flex_keyarg[$i] = $_POST["flex_keyarg$i"];
                if(isset($_POST["flex_label$i"])) $flex_label[$i] = $_POST["flex_label$i"];
                if(isset($_POST["flex_facil$i"])) $flex_facil[$i] = $_POST["flex_facil$i"];
            }
            for($i=1;$i<=40;$i++){
                if(isset($_POST["dss_keyarg$i"])) $dss_keyarg[$i] = $_POST["dss_keyarg$i"];
                if(isset($_POST["dss_label$i"])) $dss_label[$i] = $_POST["dss_label$i"];
                if(isset($_POST["dss_facil$i"])) $dss_facil[$i] = $_POST["dss_facil$i"];
            }

$content = <<<EOT
# Panasonic SIP Phone Standard Format File # DO NOT CHANGE THIS LINE!

#
EOT;

            $content .= "\n\n";
            //ACT
            $content .= "# Flex button sample (Phone LCD)\n";
            for($i=1;$i<=24;$i++){
                $key_def = "FLEX_BUTTON_FACILITY_ACT$i=";
                if(isset($_POST["flex_facil$i"])){
                    $p_facil = $_POST["flex_facil$i"];
                    switch($p_facil){
                        case 'line':
                            $content .= $key_def . "\"X_PANASONIC_IPTEL_LINE\"\n";
                            break;
                        case 'blf':
                            $content .= $key_def . "\"X_PANASONIC_IPTEL_BLF\"\n";
                            break;
                        case 'quick':
                            $content .= $key_def . "\"X_PANASONIC_IPTEL_ONETOUCH\"\n";
                            break;
                        default :
                            $content .= $key_def . "\"\"\n";
                    }
                }
            }
            //ARG
            $content .= "#\n";
            for($i=1;$i<=24;$i++){
                $key_def = "FLEX_BUTTON_FACILITY_ARG$i=";
                if(isset($_POST["flex_keyarg$i"])){
                    $p_keyarg = $_POST["flex_keyarg$i"];
                    $p_facil = '';
                    if(isset($_POST["flex_facil$i"])) $p_facil = $_POST["flex_facil$i"];
                    if($p_facil != 'quick'){
                        $content .= $key_def . "\"$p_keyarg\"\n";
                    } else {
                        $content .= $key_def . "\"\"\n";
                    }
                }
            }
            //Quick Dial
            $content .= "#\n";
            for($i=1;$i<=24;$i++){
                $key_def = "FLEX_BUTTON_QUICK_DIAL$i=";
                if(isset($_POST["flex_keyarg$i"])){
                    $p_keyarg = $_POST["flex_keyarg$i"];
                    $p_facil = '';
                    if(isset($_POST["flex_facil$i"])) $p_facil = $_POST["flex_facil$i"];
                    if($p_facil == 'quick'){
                        $content .= $key_def . "\"$p_keyarg\"\n";
                    } else {
                        $content .= $key_def . "\"\"\n";
                    }
                }
            }
            //LABEL
            $content .= "#\n";
            for($i=1;$i<=24;$i++){
                $key_def = "FLEX_BUTTON_LABEL$i=";
                if(isset($_POST["flex_label$i"])){
                    $p_label = $_POST["flex_label$i"];
                    $content .= $key_def . "\"$p_label\"\n";
                }
            }

            if(isset($_POST['usedss'])){
                if($_POST['usedss'] == 'yes'){
                    $dss_checked = 'checked';
                    $content .= "\n";

                    // DSS Keys
                    $content .= "# DSS button sample (KX-HDV20)\n";
                    for($i=1;$i<=40;$i++){
                        $key_def = "DSS_BUTTON_FACILITY_ACT$i=";
                        if(isset($_POST["dss_facil$i"])){
                            $p_facil = $_POST["dss_facil$i"];
                            switch($p_facil){
                                case 'line':
                                    $content .= $key_def . "\"X_PANASONIC_IPTEL_LINE\"\n";
                                    break;
                                case 'blf':
                                    $content .= $key_def . "\"X_PANASONIC_IPTEL_BLF\"\n";
                                    break;
                                case 'quick':
                                    $content .= $key_def . "\"X_PANASONIC_IPTEL_ONETOUCH\"\n";
                                    break;
                                default :
                                    $content .= $key_def . "\"\"\n";
                            }
                        }
                    }
                    //ARG
                    $content .= "#\n";
                    for($i=1;$i<=40;$i++){
                        $key_def = "DSS_BUTTON_FACILITY_ARG$i=";
                        if(isset($_POST["dss_keyarg$i"])){
                            $p_keyarg = $_POST["dss_keyarg$i"];
                            $p_facil = '';
                            if(isset($_POST["dss_facil$i"])) $p_facil = $_POST["dss_facil$i"];
                            if($p_facil != 'quick'){
                                $content .= $key_def . "\"$p_keyarg\"\n";
                            } else {
                                $content .= $key_def . "\"\"\n";
                            }
                        }
                    }
                    //Quick Dial
                    $content .= "#\n";
                    for($i=1;$i<=40;$i++){
                        $key_def = "DSS_BUTTON_QUICK_DIAL$i=";
                        if(isset($_POST["dss_keyarg$i"])){
                            $p_keyarg = $_POST["dss_keyarg$i"];
                            $p_facil = '';
                            if(isset($_POST["dss_facil$i"])) $p_facil = $_POST["dss_facil$i"];
                            if($p_facil == 'quick'){
                                $content .= $key_def . "\"$p_keyarg\"\n";
                            } else {
                                $content .= $key_def . "\"\"\n";
                            }
                        }
                    }
                    //LABEL
                    $content .= "#\n";
                    for($i=1;$i<=40;$i++){
                        $key_def = "DSS_BUTTON_LABEL$i=";
                        if(isset($_POST["dss_label$i"])){
                            $p_label = $_POST["dss_label$i"];
                            $content .= $key_def . "\"$p_label\"\n";
                        }
                    }
                }
            }
        }

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
<h3>KX-HDV230/330/430(FLEXキー)</h3>
$msg
<br>
HDV-230:物理FLEXキー 1～12が1ページ目、13～24が2ページ目(下から上に1->12,13->24なので注意)<br>
HDV-330:LCD FLEXキー 1～8が1ページ目、9～16が2ページ目、17～24が3ページ目<br>
HDV-430:LCD FLEXキー 1～8が1ページ目、9～16が2ページ目、17～24が3ページ目<br>
<form action="" method="post">
<br>
生成するファイル：
<select name="phonemodel">
  <option value="hdv230" $pm_selected1>KX-HDV230N</option>
  <option value="hdv330" $pm_selected2>KX-HDV330N</option>
  <option value="hdv430" $pm_selected3>KX-HDV430N</option>
</select>
<br>
<br>
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

     //液晶キー(FLEX)
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
               default:
                 $sel_left1 = 'selected';
                 break;
            }

            $sel_right1 = '';
            $sel_right2 = '';
            $sel_right3 = '';
            $sel_right4 = '';
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
        <option value="" $sel_left1></option>
        <option value="quick" $sel_left2>クイック</option>
        <option value="line" $sel_left3>ライン</option>
        <option value="blf" $sel_left4>BLF</option>
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
<h3>KX-HDV20(DSSコンソール)</h3>
DSSを使用 <input type="checkbox" name="usedss" value="yes" $dss_checked> する
<br>
<br>
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

     //DSS ページ
    $kstart = 10;
    $kend = 1;
    for($j=1;$j<3;$j++){

        for($i=$kstart;$i>=$kend;$i--){

            if($i % 2 == 0){ //even
                $tr_odd_class = '';
            } else { //odd
                $tr_odd_class = 'class="pure-table-odd"';
            }

            $key_left = $i;
            $key_right = $i+10;

            $dss_left1 = '';
            $dss_left2 = '';
            $dss_left3 = '';
            $dss_left4 = '';
            switch($dss_facil[$key_left]){
               case 'quick':
                 $dss_left2 = 'selected';
                 break;
               case 'line':
                 $dss_left3 = 'selected';
                 break;
               case 'blf':
                 $dss_left4 = 'selected';
                 break;
               default:
                 $dss_left1 = 'selected';
                 break;
            }

            $dss_right1 = '';
            $dss_right2 = '';
            $dss_right3 = '';
            $dss_right4 = '';
            switch($dss_facil[$key_right]){
               case 'quick':
                 $dss_right2 = 'selected';
                 break;
               case 'line':
                 $dss_right3 = 'selected';
                 break;
               case 'blf':
                 $dss_right4 = 'selected';
                 break;
               default:
                 $dss_right1 = 'selected';
                 break;
            }

echo <<<EOT
  <tr $tr_odd_class>
    <td>キー$key_left</td>
    <td>
      <input type="text" name="dss_label$key_left" size="8" value={$dss_label[$key_left]}>
    </td>
    <td>
      <select name="dss_facil$key_left">
        <option value="" $dss_left1></option>
        <option value="quick" $dss_left2>クイック</option>
        <option value="line" $dss_left3>ライン</option>
        <option value="blf" $dss_left4>BLF</option>
      </select>
    </td>
    <td>
      <input type="text" name="dss_keyarg$key_left" size="8" value={$dss_keyarg[$key_left]}>
    </td>
    <td></td>
    <td>キー$key_right</td>
    <td>
      <input type="text" name="dss_label$key_right" size="8" value={$dss_label[$key_right]}>
    </td>
    <td>
      <select name="dss_facil$key_right">
        <option value="" $dss_right1></option>
        <option value="quick" $dss_right2>クイック</option>
        <option value="line" $dss_right3>ライン</option>
        <option value="blf" $dss_right4>BLF</option>
      </select>
    </td>
    <td>
      <input type="text" name="dss_keyarg$key_right" size="8" value={$dss_keyarg[$key_right]}>
    </td>
  </tr>
EOT;
        } //loop i

        $kstart += 20;
        $kend += 20;

        if($j != 2){
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

