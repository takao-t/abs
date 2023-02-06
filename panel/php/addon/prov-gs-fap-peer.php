<h3>Grandstreamフリーアドレス用ピア設定ファイル生成</h3>
注意：FAユーザ設定で端末のMACアドレスが設定されていないファイルは生成できません。<br>
<?php
include 'mac_vendor.php';

$msg = '';

    if($_SERVER['REQUEST_METHOD'] === 'POST'){
        if($_POST['function'] == 'createpeer'){

            $p_vpkin = trim($_POST["vpkin"]);
            if($p_vpkin == "") $p_vpkin = 0;
            $p_ldsp = trim($_POST["ldsp"]);
            $p_blfmode = trim($_POST["blfmode"]);

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
                            $p_peer = trim($_POST["phone_$i"]);
                            $p_password = $wizard_file[$p_peer]['inbound_auth/password'];
                            $p_mac = str_replace(':', '', $_POST["mac_$i"]);
                            $p_mac_l = strtolower($p_mac);
                            $p_filename = "cfg" . $p_mac_l . '.xml';
                            $q_filename = "cfg" . $p_mac . '.xml';
                            $t_filename = PROV_PATH . '/' . PROV_GS . '/' . $p_filename;
                            $u_filename = PROV_PATH . '/' . PROV_GS . '/' . $q_filename;

                            if($p_ldsp == 1) $d_peer = str_replace('FAP', '', $p_peer);
                            else $d_peer = $p_peer;
$vpk_list = array(
"",
"",
"    <P1365>{$p_blfmode}</P1365>\n<P1366>0</P1366>\n<P1467>ログイン</P1467>\n<P1468>$p_peer</P1468>\n",
"    <P1367>{$p_blfmode}</P1367>\n<P1368>0</P1368>\n<P1469>ログイン</P1469>\n<P1470>$p_peer</P1470>\n",
"    <P1369>{$p_blfmode}</P1369>\n<P1370>0</P1370>\n<P1471>ログイン</P1471>\n<P1472>$p_peer</P1472>\n"
);

$vpk_add = $vpk_list[$p_vpkin];

$content = <<<EOT
<?xml version="1.0" encoding="UTF-8" ?>
<!-- Grandstream XML Provisioning Configuration -->
<gs_provision version="1">
<mac>{$p_mac_l}</mac>
  <config version="1">
    <P4511>{$p_mac}</P4511>
    <P8>0</P8>
    <P35>{$p_peer}</P35>
    <P36>{$p_peer}</P36>
    <P270>{$p_peer}</P270>
    <P3>{$p_peer}</P3>
    <P34>{$p_password}</P34>
    <P271>1</P271>
    <P1465>{$d_peer}</P1465>
{$vpk_add}
  </config>
</gs_provision>
EOT;
                            file_put_contents($t_filename, $content);
                            file_put_contents($u_filename, $content);
                            $msg .= $p_filename . '<BR>';
                        }
                    }
                }
            }
        }
    } //End POST

echo <<<EOT
<form action="" method="post">
<table border=0 class="pure-table">
  <tr>
    <thead>
      <th>ピア名</th>
      <th>MACアドレス</th>
      <th>生成</th>
    </thead>
  </tr>
EOT;

    $j = 0;
    $n_macs = count($mac_gs_vc);

    for($i=1;$i<=$max_sip_phones;$i++){
        $f_peer = 'FAP' . sprintf("%03d",$i);
        $mac_addr = AbspFunctions\get_db_item("ABS/FAP/MAC", $f_peer);
        for($nm=0;$nm<$n_macs;$nm++){
            if(strpos($mac_addr,$mac_gs_vc[$nm])===0){
                if($mac_addr != ''){
                    if($j % 2 == 0){
                        $tr_odd_class = '';
                    } else {
                        $tr_odd_class = 'class="pure-table-odd"';
                    }
                    $j++;

echo <<<EOT
  <tr $tr_odd_class>
    <td>
      $f_peer 
    </td>
    <td>
      $mac_addr
    </td>
    <td>
      <input type="hidden" name="phone_$i" value=$f_peer>
      <input type="hidden" name="mac_$i" value=$mac_addr>
      <input type="checkbox" name="create_$i" value="yes">
    </td>
  </tr>
EOT;
                }
            }
        }
    }
echo <<<EOT
</table>
<br>
回線名表示：
<select name="ldsp">
 <option value="0">ピア名</option>
 <option value="1">数字のみ</option>
</select>
<br>
<br>
<select name="blfmode">
 <option value="11">GXP</option>
 <option value="1">GRP</option>
</select>
の
<select name="vpkin">
 <option value="0"> </option>
 <option value="2">VPK2</option>
 <option value="3">VPK3</option>
 <option value="4">VPK4</option>
</select>
をログイン状態表示/制御に使用する(VPK対応機種のみ)。
<br>
<br>
{$_(PROV_PATH)}/{$_(PROV_GS)} ディレクトリに作成されます<br>
<input type="hidden" name="function" value="createpeer">
<input type="submit" class={$_(ABSPBUTTON)} value="一括生成">
</form>
<br>
$msg
EOT;
?>
