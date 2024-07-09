<?php

//ブラウザUIで扱う最大値
$max_keys = 16;
//通常電話機とブラウザフォンの合計数
$max_sip_phones = 64;
//フリーアドレス電話機
$max_fap_phones = 32;
$max_group = 16;
$max_pgroup = 8;
//ブラウザフォンが使う範囲
$brphone_min = 33;
$brphone_max = 64;

//ユーザ情報の場所(Webからアクセスできない位置へ)
$uinfolocation = '/var/www/absp/';

//ファイルエディタを使用する場合はYESを設定
//セキュリティに注意
$use_file_editor = 'YES';

//ネットワークデバイスの定義
//使用するネットワークデバイスがeth0ではない場合には変更のこと
//一部ページでIPアドレスの判定等に使用しているため
//define('NETDEV', 'eth0');
define('NETDEV', 'enp4s0');

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
//define('BACKUPDIR', '/var/www/html/backup');
//ブラウザからアクセスできない場所におくならば下記例
//ディレクトリの権限に注意
define('BACKUPDIR', '/var/www/absp/backup');
//ログファイルの作成箇所
define('LOGDIR', '/var/log/asterisk');
//着信履歴(着信,拒否)DBのファイル
define('CLOGDB', '/var/log/asterisk/abslog.sqlite3');

//以下は修正しないこと
$_ = function($str){return $str;};
define('ABSPBUTTON', '"absp-button1 pure-button"');
?>
