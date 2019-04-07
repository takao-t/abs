<h3>パナソニック・マスターファイル</h3>

<?php
$content = '';
$msg = '';

    if($_SERVER['REQUEST_METHOD'] === 'POST'){
        if($_POST['function'] == 'genconfig'){
$content = <<<EOT
# Panasonic SIP Phone Standard Format File # DO NOT CHANGE THIS LINE!

## Provisioning Settings
CFG_STANDARD_FILE_PATH="{$_POST['cfg_standard']}/Config-{MAC}.cfg"
CFG_PRODUCT_FILE_PATH="{$_POST['cfg_product']}/Config-{MODEL}.cfg"

#HTTPD Settings
HTTPD_PORTOPEN_AUTO="{$_POST['httpport']}"

#NTP Settings
NTP_ADDR="192.168.254.175"
TIME_SYNC_INTVL="60"
TIME_QUERY_INTVL="43200"
LOCAL_TIME_ZONE_POSIX=""
TIME_ZONE="540"
DST_ENABLE="N"

## SIP Settings
SIP_RGSTR_ADDR_1="{$_POST['regi_addr']}"
SIP_RGSTR_PORT_1="{$_POST['regi_port']}"
SIP_PRXY_ADDR_1="{$_POST['proxy_addr']}"
SIP_PRXY_PORT_1="{$_POST['proxy_port']}"
SIP_OUTPROXY_ADDR_1="{$_POST['oproxy_addr']}"
SIP_OUTPROXY_PORT_1="{$_POST['oproxy_port']}"
SIP_SVCDOMAIN_1="{$_POST['svc_addr']}"
SIP_PRSNC_PORT_1="{$_POST['svc_port']}"
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
                }
            }
        } //savetofile

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

    $cfg_standard = "http://" . $ipaddr . '/' . PROV_PANA;
    $cfg_product = "http://" . $ipaddr . '/' . PROV_PANA;
    $ntp_addr = $ipaddr;
    $regi_addr = $ipaddr;
    $proxy_addr = $ipaddr;
    $oproxy_addr = $ipaddr;
    $svc_addr = $ipaddr;
    $regi_port = "5070";
    $proxy_port = "5070";
    $oproxy_port = "5070";
    $svc_port = "5070";

    $master_file = PROV_PATH . '/' . PROV_PANA . '/' . 'master.cfg';

echo <<<EOT
$msg
<form action="" method="post">
<table border=0 class="pure-table">
  <tr>
    <td>
      スタンダードファイル
    </td>
    <td>
      <input type="text" size="40" name="cfg_standard" value=$cfg_standard>
    </td>
    <td>
      /Config-{MAC}.cfg
    </td>
  </tr>
  <tr>
    <td>
      プロダクトファイル
    </td>
    <td>
      <input type="text" size="40" name="cfg_product" value=$cfg_product>
    </td>
    <td>
      /Config-{MODEL}.cfg
    </td>
  </tr>
</table>
<br>
電話機のWebポート(デフォルト)：
<select name="httpport">
  <option value="Y">オープン</option>
  <option value="N">クローズ</option>
</select>
<br>
<br>
NTPサーバ：
<input type="text" name="ntp_addr" value=$ntp_addr>
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
  <tr>
    <td>
      アウトバウンドプロキシ
    </td>
    <td>
      <input type="text" size="16" name="oproxy_addr" value=$oproxy_addr>
    </td>
    <td>
      : <input type="text" size="4" name="oproxy_port" value=$oproxy_port>
    </td>
  </tr>
  <tr>
    <td>
      サービスドメイン
    </td>
    <td>
      <input type="text" size="16" name="svc_addr" value=$svc_addr>
    </td>
    <td>
      : <input type="text" size="4" name="svc_port" value=$svc_port>
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
<textarea cols="80" rows="30" name="content">
$content
</textarea>
<br>
<input type="hidden" name="function" value="savetofile">
<input type="hidden" name="master_file" value="$master_file">
内容を確認しました
<input type="checkbox" name="savechecked" value="yes">
$master_file として
<input type="submit" value="保存する">
<form>
<br>
<font color="red" size="-1">注意:同じ名前のファイルが存在すると上書きされます</font>
EOT;
?>
