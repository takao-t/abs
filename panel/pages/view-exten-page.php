<?php
if (!defined('ABS_PANEL_INCLUDED')) {
    die("Direct access is not permitted.");
}

global $ami;

// 内線リストの取得とフィルタリング
$extension_list = [];
$db_endpoints = $ami->getDbFamily('ABS/EXT');
if (is_array($db_endpoints)) {
    foreach($db_endpoints as $endpoint_line) {
        list($exten, $endpoint) = explode(':', $endpoint_line, 2);
        // スラッシュや特定のキーワードを含むエントリは除外
        if(strpos($exten, '/') === false && strpos($exten, 'RGPT') === false && strpos($exten, 'TMO') === false){
            $extension_list[] = ['exten' => trim($exten), 'endpoint' => trim($endpoint)];
        }
    }
}

// 特番リストの取得
$feature_codes = [];
$file_handle = @fopen('internalexten.txt', 'r');
if ($file_handle) {
    while($line = fgetcsv($file_handle)) {
        if (!empty($line[0]) && !empty($line[1])) { // 空行は無視
            $feature_codes[] = [
                'code' => $line[0],
                'feature' => $line[1],
                'description' => $line[2] ?? ''
            ];
        }
    }
    fclose($file_handle);
}

?>
<h2>内線情報確認</h2>

<h3>内線登録一覧</h3>
<div class="table-container">
    <table class="absp-table">
        <thead>
            <tr>
                <th style="width: 150px;">内線番号</th>
                <th>端末(エンドポイント)名</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($extension_list)): ?>
                <tr><td colspan="2" style="text-align: center; padding: 20px;">登録されている内線はありません。</td></tr>
            <?php else: ?>
                <?php foreach($extension_list as $ext): ?>
                <tr>
                    <td><?= htmlspecialchars($ext['exten'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($ext['endpoint'], ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<h3>特番一覧</h3>
<div class="table-container">
    <table class="absp-table">
        <thead>
            <tr>
                <th style="width: 150px;">特番</th>
                <th>機能</th>
                <th>補足説明</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($feature_codes)): ?>
                 <tr><td colspan="3" style="text-align: center; padding: 20px;">特番情報ファイルが見つかりません。</td></tr>
            <?php else: ?>
                <?php foreach($feature_codes as $fc): ?>
                <tr>
                    <td><?= htmlspecialchars($fc['code'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($fc['feature'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($fc['description'], ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<a href="index.php?page=system-config-page" class="btn" style="margin-top: 1em;">戻る</a>
