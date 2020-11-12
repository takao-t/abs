# abs2

・ホスト情報が正しく表示されない

view-hostinfo-page.phpが決め打ちで情報を見ているためです。
IPアドレス等が正しく表示されない場合にはview-hostinfo-page.phpを編集してください。例えばethernetインタフェースがeth0ではなくeno1になる場合には以下のように編集します。
-----
<h3>ホスト情報</h3>

<?php

    $retval = array();
    $cmd = 'ip addr show eno1'; <=ここを修正する
    exec($cmd, $retval);

    foreach($retval as $line)
-----


・休日情報が取得できない場合のworkaround

OSのバージョンによってSSL routines:tls_process_ske_dhe:dh key too smallで休日情報は取得できない場合/etc/ssl/openssl.cnfの以下の個所をコメントアウトしてください。
-----
[system_default_sect]
MinProtocol = TLSv1.2
#CipherString = DEFAULT@SECLEVEL=2 ←コメントアウト
-----
コメントアウトした後、apacheを再起動してください。
