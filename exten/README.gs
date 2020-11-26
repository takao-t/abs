Grandstream対応

Grandstream電話機はasteriskから"認証なし"のSIP Notifyを送っても受け付けないため、対電話機向け認証ありの設定を使えるようにしました。

初期でpjsip_wizard.confを生成する場合にはpj_phonegen2.shを使用してください。

既存のpjsip_wizard.conを使用する場合には

[phone1](phone-defaults)
inbound_auth/username = phone1
inbound_auth/password = mypassword1234
outbound_auth/username = phone1
outbound_auth/password = mypassword1234

この例のようにoutbound_authのエントリを追加してください。指定値はinbound_authと同じものです。

pjsip_notify.conf を /etc/asterisk へコピーし、Asteriskを再起動するとNotifyを送ることでGrandstream電話機を再起動することができます。

*CLI> pjsip send notify gsreboot endpoint phone1

この例のようにendpointにGS電話機を指定すれば、その電話機は再起動します。

