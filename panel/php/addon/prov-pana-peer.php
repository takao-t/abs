<h3>パナソニック・ピア設定ファイル生成</h3>
注意：内線設定で内線番号とMACアドレスが設定されていない端末のファイルは生成できません。<br>
<?php
$msg = '';

    if($_SERVER['REQUEST_METHOD'] === 'POST'){
        if($_POST['function'] == 'createpeer'){
            $p_server = $_POST['server_addr'];

            $target = ASTDIR . '/' . 'pjsip_wizard.conf';
            $wizard_file = file_get_contents($target);

            $wizard_file = str_replace('(phone-defaults)', '', $wizard_file);
            $wizard_file = str_replace('(phone)', '', $wizard_file);
            $wizard_file = str_replace('(!)', '', $wizard_file);
            $wizard_file = parse_ini_string($wizard_file, true);

            $msg = '生成したファイル : <BR>';
            for($i=1;$i<=$max_sip_phones;$i++){
                if(isset($_POST["phone_$i"])){
                    if(isset($_POST["create_$i"])){
                        if($_POST["create_$i"] == 'yes'){
                            $p_password = $wizard_file["phone$i"]['inbound_auth/password'];
                            $p_peer = "phone$i";
                            $p_exten = $_POST["exten_$i"];
                            $p_ohans = $_POST["ohans_$i"];
                            if($p_ohans == 'no') $line_prefer = "LINE_PREFERENCE_INCOMING=\"NOLN\"";
                              else $line_prefer = ''; 
                            $p_mac = str_replace(':', '', $_POST["mac_$i"]);
                            $p_filename = "Config-" . $p_mac . '.cfg';
                            $t_filename = PROV_PATH . '/' . PROV_PANA . '/' . $p_filename;
$content = <<<EOT
# Panasonic SIP Phone Standard Format File # DO NOT CHANGE THIS LINE!

## SIP Settings(LINE1)
PHONE_NUMBER_1="$p_exten"
SIP_URI_1="$p_peer"
SIP_AUTHID_1="$p_peer"
SIP_PASS_1="$p_password"
$line_prefer
EOT;
                            file_put_contents($t_filename, $content);
                            $msg .= $p_filename . '<BR>';
                        }
                    }
                }
            }
        }
    } //End POST

    $retval = array();
    $cmd = 'ip addr show eth0';
    exec($cmd, $retval);

    foreach($retval as $line){
        if(strpos($line, 'link/ether') !== false) $macaddr = $line;
        if(strpos($line, 'inet ') !== false) $ipaddr = $line;
    }
    $ipaddr = preg_replace('/ brd .*$/', '', $ipaddr);
    $ipaddr = str_replace('inet ', '', $ipaddr);
    list($ipaddr, $dummy) = explode('/', $ipaddr);
    $ipaddr = trim($ipaddr);

echo <<<EOT
<form action="" method="post">
<table border=0 class="pure-table">
  <tr>
    <thead>
      <th>ピア名</th>
      <th>内線番号</th>
      <th>MACアドレス</th>
      <th>オフフックで応答</th>
      <th>生成</th>
    </thead>
  </tr>
EOT;

    $j = 0;

    for($i=1;$i<=$max_sip_phones;$i++){
        $mac_addr = AbspFunctions\get_db_item("ABS/PINFO/phone$i", 'MAC');
        $exten = AbspFunctions\get_db_item("ABS/ERV", "phone$i");

        if($mac_addr != '' & $exten != ''){
            if($j % 2 == 0){
                $tr_odd_class = '';
            } else {
                $tr_odd_class = 'class="pure-table-odd"';
            }
            $j++;

echo <<<EOT
  <tr $tr_odd_class>
    <td>
      phone$i
    </td>
    <td>
      $exten
    </td>
    <td>
      $mac_addr
    </td>
    <td>
      <select name="ohans_$i">
        <option value="yes" selected>する</option>
        <option value="no">しない</option>
      </select>
    </td>
    <td>
      <input type="hidden" name="phone_$i" value=$i>
      <input type="hidden" name="exten_$i" value=$exten>
      <input type="hidden" name="mac_$i" value=$mac_addr>
      <input type="checkbox" name="create_$i" value="yes">
    </td>
  </tr>
EOT;
        }
    }
echo <<<EOT
</table>
オフフック応答: 着信時、通常は受話器を上げると応答しますがキーを押すまで応答させたくない場合に"しない"設定します。
<br>
<br>
サーバアドレス：
<input type="text" name="server_addr" value=$ipaddr>
<br>
<br>
{$_(PROV_PATH)}/{$_(PROV_PANA)} ディレクトリに作成されます<br>
<input type="hidden" name="function" value="createpeer">
<input type="submit" class={$_(ABSPBUTTON)} value="一括生成">
</form>
<br>
$msg
EOT;
?>
