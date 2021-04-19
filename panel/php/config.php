<?php

//ブラウザUIで扱う最大値
$max_keys = 16;
$max_sip_phones = 32;
$max_group = 16;
$max_pgroup = 8;

//ユーザ情報の場所(Webからアクセスできない位置へ)
$uinfolocation = '/var/www/absp/';

//ファイルエディタを使用する場合はYESを設定
//セキュリティに注意
$use_file_editor = 'YES';

//ネットワークデバイスの定義
//使用するネットワークデバイスがeth0ではない場合には変更のこと
//一部ページでIPアドレスの判定等に使用しているため
define('NETDEV', 'eth0');
//define('NETDEV', 'enp4s0');

//ページロケーション
define('PPREFIX', 'absp');
define('PINDEX', 'index.php');

//プロビジョンファイルのロケーション
define('PROV_PATH', '/var/www/html');
//パナソニックプロビジョンファイルのロケーション
define('PROV_PANA', 'prov/pana');
//Grandstreamプロビジョンファイルのロケーション
define('PROV_GS', 'prov/gs');

//Asterisk設定ファイルロケーション
define('ASTDIR', '/etc/asterisk');
//バックアップファイルの作成箇所
define('BACKUPDIR', '/var/www/html/backup');
//ログファイルの作成箇所
define('LOGDIR', '/var/log/asterisk');

//以下は修正しないこと
$_ = function($str){return $str;};
define('ABSPBUTTON', '"absp-button1 pure-button"');
?>
