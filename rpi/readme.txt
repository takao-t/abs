1点だけ自動化できてない箇所があります。Webサーバ(apache2)の設定で/var/www/html下に対するAllowOverrideだけは手動設定してください。
 /index.php で開くので問題ないという場合には設定変更せずともかまいませんが、/ で開いた時にABS Panelが表示されるようにするためにはAllowOverrideを設定する必要があります。

<Directory /var/www/>
        Options Indexes FollowSymLinks
        AllowOverride All　　　　　　　　　　　<==Allに変更する
        Require all granted
</Directory>
