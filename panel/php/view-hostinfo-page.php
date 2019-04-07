<h3>ホスト情報</h3>

<?php

    $retval = array();
    $cmd = 'ip addr show eth0';
    exec($cmd, $retval);

    foreach($retval as $line){
        if(strpos($line, 'link/ether') !== false) $macaddr = $line;
        if(strpos($line, 'inet ') !== false) $ipaddr = $line;
    }
    $macaddr = str_replace(' brd ff:ff:ff:ff:ff:ff', '', $macaddr);
    $macaddr = str_replace('link/ether ', '', $macaddr);
    $macaddr = trim($macaddr);
    $ipaddr = preg_replace('/ brd .*$/', '', $ipaddr);
    $ipaddr = str_replace('inet ', '', $ipaddr);
    $ipaddr = trim($ipaddr);

    $uname = php_uname();
    $uptime = exec('uptime');

echo <<<EOT
<table border=0 class="pure-table">
  <tr>
    <thead>
      <th>項目</th>
      <th></th>
    </thead>
  </tr>
  <tr>
    <td>システム</td>
    <td>$uname</td>
  </tr>
  <tr class="pure-table-odd">
    <td>IPアドレス(eth0)</td>
    <td>$ipaddr</td>
  </tr>
  <tr>
    <td>物理アドレス</td>
    <td>$macaddr</td>
  </tr>
  <tr class="pure-table-odd">
    <td>uptime</td>
    <td>$uptime</td>
  </tr>
</table>
EOT;
?>
