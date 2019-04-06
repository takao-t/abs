<h3>端末(ピア)情報確認</h3>

<?php

    $tech = AbspFunctions\get_db_item('ABS', 'EXTTECH');

echo <<<EOT
注意：<br>
ABSの端末(電話機)情報は以下の内容で設定します。各端末の内線番号は内線情報設定で行います。<br>
端末のパスワードはファイル(pjsip_wizard.conf)を編集するか初期生成スクリプトで行います。
このページから変更は行えません。<br>
pjsip_wizard.confファイルを編集した場合にはAsteriskの再起動が必要となります。<br>
<font color="red"><b>このページの情報の取り扱いには注意してください。</b></font><br>
<br>
<br>
<table border=0 class="pure-table">
<tr>
<thead>
<th>端末(ピア)名</th>
<th>パスワード</th>
</thead>
</tr>
EOT;

if($tech == 'SIP'){
    //SIP
    $target = ASTDIR . '/' . 'sip_phones.debug';
    $wizard_file = file_get_contents($target);
} else {
    //PJSIP
    $target = ASTDIR . '/' . 'pjsip_wizard.conf';
    $wizard_file = file_get_contents($target);
}

$wizard_file = str_replace('(phone-defaults)', '', $wizard_file);
$wizard_file = str_replace('(phone)', '', $wizard_file);
$wizard_file = str_replace('(!)', '', $wizard_file);
$wizard_file = parse_ini_string($wizard_file, true);

for($i=1;$i<=$max_sip_phones;$i++){

    if($tech == 'SIP'){
        //SIP
        $p_password = $wizard_file["phone$i"]['secret'];
    } else {
        //PJSIP
        $p_password = $wizard_file["phone$i"]['inbound_auth/password'];
    }

    if($i % 2 != 0){
        $tr_odd_class = '';
    } else {
        $tr_odd_class = 'class="pure-table-odd"';
    }

echo <<<EOT
<tr $tr_odd_class>
<td align="right">
phone$i
</td>
<td nowrap>
$p_password
</td>
</tr>
EOT;

} /* end of for */

echo "</table>";
echo "<br>";

?>
