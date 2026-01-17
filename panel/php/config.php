<?php

//ブラウザUIで扱うキーの最大数
$max_keys = 16;
//通常電話機とブラウザフォンの合計数
$max_sip_phones = 64;
//フリーアドレス電話機
$max_fap_phones = 32;
//最大グループ数
$max_group = 16;
//最大ピックアップグループ数
$max_pgroup = 8;
//ブラウザフォンが使う範囲
$brphone_min = 33;
$brphone_max = 64;

// AMI接続情報
define('AMI_HOST', 'localhost');
define('AMI_USER', 'abspadmin');
define('AMI_PASS', 'amipass1234');
define('AMI_PORT', '5038');


//プロビジョンファイルのrootロケーション
define('PROV_PATH', '/var/www/html');
//パナソニックプロビジョンファイルのロケーション
define('PROV_PANA', 'prov/pana');
//Grandstreamプロビジョンファイルのロケーション
define('PROV_GS', 'prov/gs');

//Asterisk設定ファイルロケーション
define('ASTDIR', '/etc/asterisk');
//ABSが生成するトランクファイルのロケーション(注:Webサーバがアクセスできない位置に)
define('ABSTRUNKS', '/var/www/abs');
//ログファイルの作成箇所
define('LOGDIR', '/var/log/asterisk');
//着信履歴(着信,拒否)DBのファイル
define('CLOGDB', '/var/log/asterisk/abslog.sqlite3');

// --- バックアップ設定 ---
// バックアップ対象のAstDBファミリーを定義
// ここにリストアップされたファミリーがバックアップの対象
define('BACKUP_FAMILIES', [
    'ABS',
    'HOLIDAYS',
    'KEYTEL',
    'cidname'
]);

// キャッシュ設定 現在のところセッション情報保存にのみ使用
// 設定可能値はfileまたはmemcached
// memcached使用時はmemcachedのインストールとPHP拡張が必要
define('CACHE_MODE', 'file');
// memcachedを使用する場合は以下の設定が必要
//define('CACHE_MODE', 'memcached'); 
define('MEMCACHED_HOST', '127.0.0.1');
define('MEMCACHED_PORT', 11211);

//以下は修正しないこと
$_ = function($str){return $str;};
