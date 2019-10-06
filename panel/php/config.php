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

//ページロケーション
define('PPREFIX', 'absp2');
define('PINDEX', 'index.php');

//プロビジョンファイルのロケーション
define('PROV_PATH', '/var/www/html');
define('PROV_PANA', 'prov/pana');
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
