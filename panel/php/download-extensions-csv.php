<?php
/**
 * download-extensions-csv.php
 * 内線設定CSVエクスポート (PJSIP対応)
 */

require_once __DIR__ . '/config_session.php';
session_start();

// ログインチェック
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header('HTTP/1.0 403 Forbidden');
    die("Access denied.");
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/AbspManager.php'; // クラスファイルの読み込み

use AbspFunctions\AbspManager;

// 文字エンコーディング設定
mb_internal_encoding('UTF-8');

// --- AMI接続の確立 (このスクリプト専用) ---
// config.php で定義されている定数を使用
$ami = new AbspManager(AMI_HOST, AMI_USER, AMI_PASS, AMI_PORT);

// --- CSV生成とダウンロード処理 ---

// 1. HTTPヘッダー送信
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="extensions_' . date('Ymd') . '.csv"');

// 2. 出力ストリームを開く
$output = fopen('php://output', 'w');

// 3. ヘッダー行 (順不同可だが、テンプレートとして使いやすい順序に)
fputcsv($output, ['endpoint', 'exten', 'limit', 'ogcid', 'pgrp', 'macadd']);

// 4. データ取得ループ
// config.php で定義されている $max_sip_phones を使用
for ($i = 1; $i <= $max_sip_phones; $i++) {
    // 表示上の名前 (phone1)
    $bare_endpoint = "phone{$i}";
    
    // DB検索用のキー (PJSIP/phone1)
    $endpoint_key = "PJSIP/" . $bare_endpoint;
    
    // 情報を取得
    $info = $ami->getEndpointInfo($endpoint_key);
    
    // MACアドレス取得 & フォーマット (AA:BB:...)
    $raw_mac = $ami->getDbItem("ABS/PINFO/{$bare_endpoint}", 'MAC');
    $formatted_mac = $ami->formatMacAddress($raw_mac);
    
    $row = [
        $bare_endpoint,             // endpoint (PJSIP/ は付けない)
        $info['exten'] ?? '',       // exten
        $info['limit'] ?? '0',      // limit
        $info['ogcid'] ?? '',       // ogcid
        $info['pgrp']  ?? '',       // pgrp
        $formatted_mac              // macadd
    ];
    
    fputcsv($output, $row);
}

// 5. 終了処理
fclose($output);
// $ami のデストラクタでログアウト処理が行われます
exit;
