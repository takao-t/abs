<?php
if (!defined('ABS_PANEL_INCLUDED')) {
    die("Direct access is not permitted.");
}

// GET時処理
$provisioning_vendors = [];
// このファイルと同じ階層にあるprovisioningディレクトリを指す
$config_dir = __DIR__ . '/provisioning/';

// ディレクトリ内の.jsonファイルをスキャン
$json_files = glob($config_dir . '*.json');

if ($json_files) {
    foreach ($json_files as $file) {
        $json_content = file_get_contents($file);
        $vendor_data = json_decode($json_content, true);
        // JSONが正しくパースできた場合のみリストに追加
        if ($vendor_data && isset($vendor_data['vendor']) && isset($vendor_data['links'])) {
            $provisioning_vendors[] = $vendor_data;
        }
    }
}

?>
<h2>電話機設定ファイル生成</h2>
<p style="font-size: 0.9em; color: var(--secondary-text-color); margin-top: 0;">
    端末用プロビジョニングファイルを、ベンダーと機種を選択して生成します。<br>
    注：シングルアカウント用のファイルのみ生成可能です。複数アカウントを設定する場合は、生成されたファイルを元に手動で編集してください。
</p>

<?php if (empty($provisioning_vendors)): ?>
    <div class="notice-message" style="color: #f44336;">
        プロビジョニング用の設定ファイル（<code>pages/provisioning/</code>内のJSONファイル）が見つかりません。
    </div>
<?php else: ?>
    <?php foreach ($provisioning_vendors as $vendor_info): ?>
        <h3><?= htmlspecialchars($vendor_info['vendor'], ENT_QUOTES, 'UTF-8') ?></h3>
        <div class="table-container">
            <table class="absp-table">
                <tbody>
                    <?php foreach ($vendor_info['links'] as $link): ?>
                    <tr>
                        <td style="width: 250px;">
                            <?php
                            // model,type パラメータがある場合はURLに追加
                            $url = 'index.php?page=' . htmlspecialchars($link['page'], ENT_QUOTES, 'UTF-8');
                            if (isset($link['model'])) {
                                $url .= '&model=' . htmlspecialchars($link['model'], ENT_QUOTES, 'UTF-8');
                            }
                            elseif (isset($link['type'])) {
                                $url .= '&type=' . htmlspecialchars($link['type'], ENT_QUOTES, 'UTF-8');
                            }
                            ?>
                            <a href="<?= $url ?>">
                                <?= htmlspecialchars($link['title'], ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($link['desc'], ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<h3 style="font-size: 1em; color: var(--secondary-text-color);">MACアドレス ベンダーコードについて</h3>
<p style="font-size: 0.9em; color: var(--secondary-text-color); margin-top: 0;">
    端末(ピア)情報を生成する際、対象ベンダーの一覧に電話機が表示されない場合は、<code>pages/provisioning/</code>内の各JSONファイルにある<code>mac_prefixes</code>のリストを編集してください。
</p>
