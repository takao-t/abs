<h3>Grandstreamマスターファイル</h3>

<?php
$content = '';
$content_dp = '';
$msg = '';
$msg_dp = '';

    if($_SERVER['REQUEST_METHOD'] === 'POST'){
        if($_POST['function'] == 'genconfig'){
$content = <<<EOT
<?xml version="1.0" encoding="UTF-8" ?>
<!-- Grandstream XML Provisioning Configuration -->
<gs_provision version="1">
  <config version="1">
    <P30>{$_POST['ntp_addr']}</P30>
    <P47>{$_POST['regi_addr']}:{$_POST['regi_port']}</P47>
    <P48>{$_POST['proxy_addr']}:{$_POST['proxy_port']}</P48>
    <P91>{$_POST['callwait']}</P91>
    <P104>{$_POST['ringtone']}</P104>
    <P122>1</P122>
    <P191>0</P191>
    <P192>{$_POST['prov_path']}</P192>
    <P212>1</P212>
    <P237>{$_POST['prov_path']}</P237>
    <P290>{\P\a\\r\kx|\k\\e\yx+| x+ | \+x+ | *x+ | *xx*x+ }</P290>
    <P331>{$_POST['prov_path']}</P331>
    <P2916>4</P2916>
    <P2918>0</P2918>
    <P6766>3</P6766>
    <P8369>2</P8369>
    <!-- End of exported configuration -->
  </config>
</gs_provision>
EOT;

$content_dp = <<<EOT
<?xml version="1.0" encoding="UTF-8" ?>
<!-- DP750 XML Provisioning Configuration -->
<gs_provision version="1">
  <config version="2">
<!--# Name: 30-->
<!--# Alias: maintenance.date_time.ntp.server.1 # Help: NTP Server-->
<!--# Attributes: Category=SERVERADDRESS Mandatory=0 -->
<!--# Default Value: pool.ntp.org-->
    <item name="maintenance">
        <part name="date_time.ntp.server.1">{$_POST['ntp_addr']}</part>
    </item>
<!--# Name: 47-->
<!--# Alias: profile.1.sip.server.1.address,account.1.sip_server.1.address # Help: Primary SIP Server-->
<!--# Attributes: Category=SERVERADDRESS Mandatory=0 -->
<!--# Default Value:-->
    <item name="profile.1">
        <part name="sip.server.1.address">{$_POST['regi_addr']}:{$_POST['regi_port']}</part>
    </item>
<!--# Name: 48-->
<!--# Alias: profile.1.outbound_proxy.1.address # Help: Outbound Proxy-->
<!--# Attributes: Category=SERVERADDRESS Mandatory=0 -->
<!--# Default Value:-->
    <item name="profile.1">
        <part name="outbound_proxy.1.address">{$_POST['regi_addr']}:{$_POST['regi_port']}</part>
    </item>
<!--# Name: 191-->
<!--# Alias: profile.1.call.features.enable # Help: Enable Call Features. 0 - No, 1 - Yes, 2 - Enable All-->
<!--# Attributes: Category=NUMBER Mandatory=1 Base=10 Range=[0-1] -->
<!--# Default Value: 1-->
    <item name="profile.1">
        <part name="call.features.enable">0</part>
    </item>
<!--# Name: 192-->
<!--# Alias: upgrade.server_path,firmware.url # Help: Firmware Server Path-->
<!--# Attributes: Category=SERVERADDRESS Mandatory=0 Max Length=256 -->
<!--# Default Value: fm.grandstream.com/gs-->
    <item name="upgrade">
        <part name="server_path">{$_POST['prov_path']}/dp</part>
    </item>
<!--# Name: 212-->
<!--# Alias: provisioning.protocol # Help: Firmware Upgrade and Provisioning. 0 - TFTP Upgrade, 1 - HTTP Upgrade, 2 - HTTPS Upgrade, 3 - FTP Upgrade, 4 - FTPS Upgrade.-->
<!--# Attributes: Category=NUMBER Mandatory=1 Base=10 Range=[0-4] -->
<!--# Default Value: 1-->
    <item name="provisioning">
        <part name="protocol">1</part>
    </item>
<!--# Name: 237-->
<!--# Alias: provisioning.server_path # Help: Config Server Path-->
<!--# Attributes: Category=SERVERADDRESS Mandatory=0 Max Length=256 -->
<!--# Default Value: fm.grandstream.com/gs-->
    <item name="provisioning">
        <part name="server_path">{$_POST['prov_path']}/dp</part>
    </item>
<!--# Name: 331-->
<!--# Alias: phonebook.global.xml.auto.server # Help: Phonebook XML Server Path. This is a string of up to 256 characters that should contain a path to the XML file. It MUST be in the host/path format.-->
<!--# Attributes: Category=SERVERADDRESS Mandatory=0 Max Length=256 -->
<!--# Default Value:-->
    <item name="phonebook">
        <part name="global.xml.auto.server">{$_POST['prov_path']}/dp</part>
    </item>
<!--####################-->
<!--##  P values End  ##-->
<!--####################-->
  </config>
</gs_provision>
EOT;

        }

        if($_POST['function'] == 'savetofile'){
            if(isset($_POST['savechecked'])){
                if($_POST['savechecked'] == 'yes'){
                    $p_filename = trim($_POST['master_file']);
                    if($p_filename != ''){
                        $content = $_POST['content'];
                        $content = str_replace("\r", '', $content);
                        file_put_contents($p_filename, $content);
                        $msg = '<font color="red">'. $p_filename . 'に保存しました' . '</font>';
                    }
                    //$p_filename = trim($_POST['master_file_dp']);
                    //if($p_filename != ''){
                    //    $content_dp = $_POST['content_dp'];
                    //    $content_dp = str_replace("\r", '', $content_dp);
                    //    file_put_contents($p_filename, $content_dp);
                    //    $msg_dp = '<font color="red">'. $p_filename . 'に保存しました' . '</font>';
                    //}
                }
            }
        } //savetofile

    } //End POST

    $retval = array();
    $cmd = 'ip addr show ' . NETDEV;
    exec($cmd, $retval);

    foreach($retval as $line){
        if(strpos($line, 'link/ether') !== false) $macaddr = $line;
        if(strpos($line, 'inet ') !== false) $ipaddr = $line;
    }
    $ipaddr = preg_replace('/ brd .*$/', '', $ipaddr);
    $ipaddr = str_replace('inet ', '', $ipaddr);
    list($ipaddr, $dummy) = explode('/', $ipaddr);
    $ipaddr = trim($ipaddr);

    $cfg_product = "http://" . $ipaddr . '/' . PROV_GS;
    $prov_path = $ipaddr . '/' . PROV_GS;
    $ntp_addr = $ipaddr;
    $regi_addr = $ipaddr;
    $proxy_addr = $ipaddr;
    $oproxy_addr = $ipaddr;
    $regi_port = "5070";
    $proxy_port = "5070";
    $oproxy_port = "5070";

    $master_file = PROV_PATH . '/' . PROV_GS . '/' . 'cfg.xml';
    //$master_file_dp = PROV_PATH . '/' . PROV_GS . '/dp/' . 'cfg.xml';

echo <<<EOT
<h3>電話機側設定の注意</h3>
メンテナンス->アップグレードとプロビジョニング<br>
設定を以下を介して更新 : HTTP<br>
設定サーバパス : $prov_path<br>
すべての利用可能な設定ファイルのダウンロードと処理 : YES
<br>
<!--
DP750の場合にはパスを以下に指定のこと
<br>
設定サーバパス : $prov_path/dp<br>
<br>
-->

$msg<br>
$msg_dp
<form action="" method="post">
<input type="hidden" name="prov_path" value=$prov_path>
<table border=0 class="pure-table">
  <tr>
    <td>
      マスター設定ファイル
    </td>
    <td>
      <input type="text" size="40" name="cfg_product" value=$cfg_product>
    </td>
    <td>
      /cfg.xml
    </td>
  </tr>
</table>
<br>
<br>
NTPサーバ：
<input type="text" name="ntp_addr" value=$ntp_addr>
<br>
<br>
デフォルト着信音
<select name="ringtone">
  <option value="0">0</option>
  <option value="1">1</option>
  <option value="2">2</option>
  <option value="3">3</option>
</select>
<br>
<br>
通話中着信
<select name="callwait">
  <option value="0">する</option>
  <option value="1">しない</option>
</select>
<br>
<br>
SIPサーバ設定
<table border=0 class="pure-table">
  <tr>
    <td>
      レジスタサーバ
    </td>
    <td>
      <input type="text" size="16" name="regi_addr" value=$regi_addr>
    </td>
    <td>
      : <input type="text" size="4" name="regi_port" value=$regi_port>
    </td>
  </tr>
  <tr>
    <td>
      プロキシサーバ
    </td>
    <td>
      <input type="text" size="16" name="proxy_addr" value=$proxy_addr>
    </td>
    <td>
      : <input type="text" size="4" name="proxy_port" value=$proxy_port>
    </td>
  </tr>
</table>
<br>
<input type="hidden" name="function" value="genconfig">
<input type="submit" class={$_(ABSPBUTTON)} value="生成実行">
</form>
<br>
<br>
生成結果<br>
<form action="" method="post">
通常電話機用<br>
<textarea cols="80" rows="30" name="content">
$content
</textarea>
<br>
<input type="hidden" name="function" value="savetofile">
<input type="hidden" name="master_file" value="$master_file">
<input type="hidden" name="master_file_dp" value="$master_file_dp">
内容を確認しました
<input type="checkbox" name="savechecked" value="yes">
$master_file として
<input type="submit" value="保存する">
<form>
<br>
<font color="red" size="-1">注意:同じ名前のファイルが存在すると上書きされます</font>
EOT;
?>
