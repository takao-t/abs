<?php
/**
 * ext-io-page.php
 * 内線情報 一括インポート/エクスポート
 * Refactored for AbspManager Class & PJSIP support
 */

if (!defined('ABS_PANEL_INCLUDED')) {
    die("Direct access is not permitted.");
}

// index.php で生成済みの $ami を使用
global $ami;

// 初期化
$import_results = $_SESSION['import_results'] ?? null;
unset($_SESSION['import_results']);

// ===================================
// POSTリクエスト処理
// ===================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- インポート処理 ---
    if (isset($_POST['import_from_csv'])) {
        $results = [
            'summary' => '',
            'type' => 'error',
            'details' => []
        ];

        if (isset($_FILES['import_csv_file']) && $_FILES['import_csv_file']['error'] === UPLOAD_ERR_OK) {
            $file_path = $_FILES['import_csv_file']['tmp_name'];

            // --- 1. ファイル読み込み ---
            $csv_data = [];
            $header_map = [];
            $file_handle = fopen($file_path, 'r');

            if ($file_handle === false) {
                $results['summary'] = 'ファイルを開けませんでした。';
            } else {
                // ヘッダー行読み込み
                $header = fgetcsv($file_handle);

                // BOM除去
                if (isset($header[0]) && strpos($header[0], "\xEF\xBB\xBF") === 0) {
                    $header[0] = substr($header[0], 3);
                }

                $header_map = array_flip($header);

                if (!isset($header_map['endpoint'])) {
                    $results['summary'] = 'CSVヘッダーに必須項目 "endpoint" が見つかりません。';
                } else {
                    while (($row = fgetcsv($file_handle)) !== false) {
                        $csv_data[] = $row;
                    }
                }
                fclose($file_handle);
            }

            // --- 2. データ検証と更新処理 ---
            if (!empty($csv_data)) {
                
                // FD内線リストを取得 (重複チェック用)
                $fd_extens = [];
                $entry = $ami->getFamilyDB('ABS/FAP/UID');
                if (is_array($entry)) {
                    foreach ($entry as $line) {
                        // "UID/KEY : VALUE" 形式をパース
                        $parts = explode('/', $line, 2);
                        if (count($parts) < 2) continue;
                        
                        list($cat_part, $val_part) = explode(':', $parts[1], 2);
                        if (trim($cat_part) == 'EXT') {
                            $fd_extens[] = trim($val_part);
                        }
                    }
                }

                // CSVファイル内での重複チェック
                $extens_in_csv = [];
                if (isset($header_map['exten'])) {
                    foreach ($csv_data as $row_data) {
                        // 行データが不足している場合の対策
                        if (!isset($row_data[$header_map['exten']])) continue;

                        $exten = trim($row_data[$header_map['exten']]);
                        if ($exten !== '') {
                            $extens_in_csv[] = $exten;
                        }
                    }
                }
                $self_duplicates = array_keys(array_filter(array_count_values($extens_in_csv), fn($c) => $c > 1));

                // 1行ずつ処理
                $processed_count = 0;
                foreach ($csv_data as $row_index => $row) {
                    $line_num = $row_index + 2; // ヘッダー分+1
                    
                    // endpointカラムがない、あるいは空の場合はスキップ
                    if (!isset($row[$header_map['endpoint']])) continue;
                    $bare_endpoint = trim($row[$header_map['endpoint']]);

                    if (empty($bare_endpoint)) {
                        $results['details'][] = "{$line_num}行目: endpointが空のためスキップしました。";
                        continue;
                    }

                    // PJSIPプレフィックス付与 (DBキー用)
                    $endpoint_key = "PJSIP/" . $bare_endpoint;

                    $exten = isset($header_map['exten']) ? trim($row[$header_map['exten']]) : null;

                    // 重複チェック
                    if ($exten !== null && $exten !== '') {
                        if (in_array($exten, $self_duplicates)) {
                            $results['details'][] = "{$line_num}行目 ({$bare_endpoint}): 内線番号「{$exten}」がCSV内で重複しているためスキップ。";
                            continue;
                        }
                        if (in_array($exten, $fd_extens)) {
                            $results['details'][] = "{$line_num}行目 ({$bare_endpoint}): 内線番号「{$exten}」がFAユーザと重複しているためスキップ。";
                            continue;
                        }
                    }

                    // --- 保存処理 ---
                    
                    // 現在の情報を取得 (以前の内線番号を知るため)
                    $current_info = $ami->getEndpointInfo($endpoint_key);
                    $p_exten_before = $current_info['exten'] ?? '';

                    // セット用の配列作成
                    $endpoint_info_set = [
                        'endpoint' => $endpoint_key,    // PJSIP/phoneX
                        'exten'    => $exten,
                        'p_exten'  => $p_exten_before,
                        'limit'    => isset($header_map['limit']) ? ($row[$header_map['limit']] ?? '0') : '0',
                        'ogcid'    => isset($header_map['ogcid']) ? ($row[$header_map['ogcid']] ?? '') : '',
                        'pgrp'     => isset($header_map['pgrp'])  ? ($row[$header_map['pgrp']]  ?? '') : ''
                    ];

                    // Endpoint情報の保存
                    $res_msg = $ami->setEndpointInfo($endpoint_info_set);

                    // MACアドレス処理 (物理名 phoneX に対して保存)
                    if (isset($header_map['macadd'])) {
                        $raw_mac_input = trim($row[$header_map['macadd']]);
                        if ($raw_mac_input != '') {
                            // 正規化メソッドを使用 (AA:BB... -> AABB...)
                            $valid_mac = $ami->normalizeMacAddress($raw_mac_input);
                            
                            if ($valid_mac) {
                                $ami->putDbItem("ABS/PINFO/{$bare_endpoint}", 'MAC', $valid_mac);
                            } else {
                                $results['details'][] = "{$line_num}行目: MACアドレス形式不正によりMACのみスキップ ({$raw_mac_input})";
                            }
                        } else {
                            $ami->delDbItem("ABS/PINFO/{$bare_endpoint}", 'MAC');
                        }
                    }

                    $results['details'][] = "{$line_num}行目 ({$bare_endpoint}): 正常に処理されました。[$res_msg]";
                    $processed_count++;
                }

                $results['summary'] = "インポート処理が完了しました。（{$processed_count}件処理）";
                $results['type'] = 'success';
            } else {
                $results['summary'] = 'CSVデータが空、または読み込めませんでした。';
            }

        } else {
            $results['summary'] = 'ファイルのアップロードに失敗しました。エラーコード: ' . ($_FILES['import_csv_file']['error'] ?? '不明');
        }

        $_SESSION['import_results'] = $results;
        header('Location: index.php?page=ext-io-page');
        exit;
    }
}
?>

<h2>内線情報の一括処理</h2>

<h3>エクスポート</h3>
<div style="padding: 15px; border: 1px solid var(--border-color); border-radius: 6px; margin-bottom: 2em;">
    <p style="margin-top: 0;">現在の内線設定情報をすべてCSVファイルとしてダウンロードします。<br>このファイルはインポート時のテンプレートとしても利用できます。</p>
    <a href="php/download-extensions-csv.php" class="btn btn-primary">CSV形式でエクスポート</a>
</div>


<h3>インポート</h3>
<div style="padding: 15px; border: 1px solid var(--border-color); border-radius: 6px;">
    <p style="margin-top: 0;">エクスポートしたCSVファイルを編集し、アップロードすることで内線情報を一括で更新できます。</p>
    
    <h4>CSVファイルの形式</h4>
    <ul style="font-size: 0.9em;">
        <li>1行目はヘッダー行として、<strong>endpoint, exten, limit, ogcid, pgrp, macadd</strong> を含めてください（順不同可）。</li>
        <li>更新対象を特定するため、<strong>endpoint</strong>列は必須です (例: phone1)。</li>
        <li>文字コードは<strong>UTF-8</strong>で保存してください。</li>
    </ul>

    <form action="index.php?page=ext-io-page" method="post" enctype="multipart/form-data">
        <div class="form-inline-group">
            <input type="file" id="csv_file" name="import_csv_file" accept=".csv" required>
            <button type="submit" name="import_from_csv" class="btn btn-primary">CSVをインポートして更新</button>
        </div>
    </form>
</div>

<?php if (isset($import_results)): ?>
<h3 style="margin-top: 1.5em;">インポート処理結果</h3>
<div class="notice-message" style="
    padding: 12px; 
    border: 1px solid; 
    border-radius: 4px; 
    margin-bottom: 1em; 
    color: <?= $import_results['type'] === 'error' ? '#f87171' : '#4ade80' ?>; 
    background-color: <?= $import_results['type'] === 'error' ? 'rgba(248, 113, 113, 0.1)' : 'rgba(74, 222, 128, 0.1)' ?>;
    border-color: currentcolor;">
    <strong><?= htmlspecialchars($import_results['summary'], ENT_QUOTES, 'UTF-8') ?></strong>
</div>

    <?php if (!empty($import_results['details'])): ?>
        <h4>詳細ログ</h4>
        <div class="command-output" style="max-height: 300px; overflow-y: auto;">
            <pre style="margin: 0;"><?= htmlspecialchars(implode("\n", $import_results['details']), ENT_QUOTES, 'UTF-8') ?></pre>
        </div>
    <?php endif; ?>
<?php endif; ?>
