<?php
// --- セキュリティと初期設定 ---
session_start();
define('ABS_PANEL_INCLUDED', true);

// ログインしていない場合は処理を中断
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header('HTTP/1.0 403 Forbidden');
    die("Access denied.");
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/astman.php';
require_once __DIR__ . '/functions.php';

// 文字エンコーディングをUTF-8に設定
mb_internal_encoding('UTF-8');

// --- CSV生成とダウンロード処理 ---

// 1. HTTPヘッダーを送信してダウンロードを指示
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="extensions_' . date('Ymd') . '.csv"');

// 2. 出力ストリームを開く
$output = fopen('php://output', 'w');

// 3. ヘッダー行を書き込む
fputcsv($output, ['endpoint', 'exten', 'limit', 'ogcid', 'pgrp', 'macadd']);

// 4. config.phpで定義されている$max_sip_phonesを使い、全ピアの情報を取得して書き込む
for ($i = 1; $i <= $max_sip_phones; $i++) {
    $peer = "phone{$i}";
    $peer_info = AbspFunctions\get_peer_info($peer);
    $macadd = AbspFunctions\get_db_item("ABS/PINFO/{$peer}", 'MAC');
    
    $row = [
        $peer,
        $peer_info['exten'] ?? '',
        $peer_info['limit'] ?? '0',
        $peer_info['ogcid'] ?? '',
        $peer_info['pgrp'] ?? '',
        $macadd ?? ''
    ];
    fputcsv($output, $row);
}

// 5. ストリームを閉じる
fclose($output);

// 6. スクリプトを終了
exit;
