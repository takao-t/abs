<h3>Grandstreamピア設定ファイル生成</h3>
注意：内線設定で内線番号とMACアドレスが設定されていない端末のファイルは生成できません。<br>
<?php
include 'mac_vendor.php';

$msg = '';

    if($_SERVER['REQUEST_METHOD'] === 'POST'){
        if($_POST['function'] == 'createpeer'){

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
                            $p_mac = str_replace(':', '', $_POST["mac_$i"]);
                            $p_mac_l = strtolower($p_mac);
                            $p_filename = "cfg" . $p_mac_l . '.xml';
                            $q_filename = "cfg" . $p_mac . '.xml';
                            $t_filename = PROV_PATH . '/' . PROV_GS . '/' . $p_filename;
                            $u_filename = PROV_PATH . '/' . PROV_GS . '/' . $q_filename;
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
    <P270>{$p_exten}</P270>
    <P3>{$p_exten}</P3>
    <P34>{$p_password}</P34>
    <P271>1</P271>
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
      <th>内線番号</th>
      <th>MACアドレス</th>
      <th>生成</th>
    </thead>
  </tr>
EOT;

    $j = 0;
    $n_macs = count($mac_gs_vc);

    for($i=1;$i<=$max_sip_phones;$i++){
        $mac_addr = AbspFunctions\get_db_item("ABS/PINFO/phone$i", 'MAC');
        $exten = AbspFunctions\get_db_item("ABS/ERV", "phone$i");
        for($nm=0;$nm<$n_macs;$nm++){
            if(strpos($mac_addr,$mac_gs_vc[$nm])===0){
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
      <input type="hidden" name="phone_$i" value=$i>
      <input type="hidden" name="exten_$i" value=$exten>
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
<br>
{$_(PROV_PATH)}/{$_(PROV_GS)} ディレクトリに作成されます<br>
<input type="hidden" name="function" value="createpeer">
<input type="submit" class={$_(ABSPBUTTON)} value="一括生成">
</form>
<br>
$msg
EOT;
?>
