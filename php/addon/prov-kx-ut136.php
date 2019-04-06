<h3>KX-UT136Nキー設定</h3>

<?php

    $product_file = PROV_PATH . '/' . PROV_PANA . '/' . 'Config-KX-UT136N.cfg';

    $msg = '';
    $content ='';
    $keyarg = array();
    $label = array();
    for($i=1;$i<=24;$i++){
        $keyarg[$i] = '';
        $label[$i] = '';
        $facil[$i] = '';
    }

    if($_SERVER['REQUEST_METHOD'] === 'POST'){

        if($_POST['function'] == 'keyset'){

            for($i=1;$i<=24;$i++){
                if(isset($_POST["keyarg$i"])) $keyarg[$i] = $_POST["keyarg$i"];
                if(isset($_POST["label$i"])) $label[$i] = $_POST["label$i"];
                if(isset($_POST["facil$i"])) $facil[$i] = $_POST["facil$i"];
            }

$content = <<<EOT
# Panasonic SIP Phone Standard Format File # DO NOT CHANGE THIS LINE!

#
EOT;

            $content .= "\n\n";
            //ACT
            $content .= "# Flex button sample (Phone Physical)\n";
            for($i=1;$i<=24;$i++){
                $key_def = "FLEX_BUTTON_FACILITY_ACT$i=";
                if(isset($_POST["facil$i"])){
                    $p_facil = $_POST["facil$i"];
                    switch($p_facil){
                        case 'line':
                            $content .= $key_def . "\"X_PANASONIC_IPTEL_DN\"\n";
                            break;
                        case 'blf':
                            $content .= $key_def . "\"X_PANASONIC_IPTEL_CONTACT\"\n";
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
                if(isset($_POST["keyarg$i"])){
                    $p_keyarg = $_POST["keyarg$i"];
                    $p_facil = '';
                    if(isset($_POST["facil$i"])) $p_facil = $_POST["facil$i"];
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
                if(isset($_POST["keyarg$i"])){
                    $p_keyarg = $_POST["keyarg$i"];
                    $p_facil = '';
                    if(isset($_POST["facil$i"])) $p_facil = $_POST["facil$i"];
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
                $key_def = "FLEX_BUTTON_FACILITY_LABEL$i=";
                if(isset($_POST["label$i"])){
                    $p_label = $_POST["label$i"];
                    $content .= $key_def . "\"$p_label\"\n";
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


//キーを左右振り分け12ずつ表示
echo <<<EOT
$msg
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

for($i=12;$i>=1;$i--){

    if($i % 2 == 0){ //even
        $tr_odd_class = '';
    } else { //odd
        $tr_odd_class = 'class="pure-table-odd"';
    }

    $key_left = $i;
    $key_right = $i+12;

     $sel_left1 = '';
     $sel_left2 = '';
     $sel_left3 = '';
     $sel_left4 = '';
     switch($facil[$key_left]){
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
     switch($facil[$key_right]){
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
      <input type="text" name="label$key_left" size="8" value={$label[$key_left]}>
    </td>
    <td>
      <select name="facil$key_left">
        <option value="" $sel_left1></option>
        <option value="quick" $sel_left2>クイック</option>
        <option value="line" $sel_left3>ライン</option>
        <option value="blf" $sel_left4>BLF</option>
      </select>
    </td>
    <td>
      <input type="text" name="keyarg$key_left" size="8" value={$keyarg[$key_left]}>
    </td>
    <td></td>
    <td>キー$key_right</td>
    <td>
      <input type="text" name="label$key_right" size="8" value={$label[$key_right]}>
    </td>
    <td>
      <select name="facil$key_right">
        <option value="" $sel_right1></option>
        <option value="quick" $sel_right2>クイック</option>
        <option value="line" $sel_right3>ライン</option>
        <option value="blf" $sel_right4>BLF</option>>
      </select>
    </td>
    <td>
      <input type="text" name="keyarg$key_right" size="8" value={$keyarg[$key_right]}>
    </td>
  </tr>
EOT;

}

echo <<<EOT
</table>
<font color="red" size="-1">
KX-UT系の電話機の場合、最低でも２キーを"ライン"に設定してください。ラインがないと発信できなくなります。
</font>
<br>
<input type="hidden" name="function" value="keyset">
<input type="submit" class={$_(ABSPBUTTON)} value="生成実行">
</form>
EOT;

echo <<<EOT
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

