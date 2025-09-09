<?php
if (!defined('ABS_PANEL_INCLUDED')) { die("Direct access is not permitted."); }

// POST時処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['function']) && $_POST['function'] === 'createpeer') {
    
    $type = $_POST['type'] ?? 'normal';

    // パスワード情報をウィザードファイルから読み込み
    $passwords = [];
    $target_file = ASTDIR . '/pjsip_wizard.conf';
    if (is_readable($target_file)) {
        $wizard_content = file_get_contents($target_file);
        $cleaned_content = preg_replace('/\((.*?)\)/', '', $wizard_content);
        $config_data = parse_ini_string($cleaned_content, true);
        if ($config_data) {
            foreach ($config_data as $peer_name => $settings) {
                if (isset($settings['inbound_auth/password'])) {
                    $passwords[$peer_name] = $settings['inbound_auth/password'];
                }
            }
        }
    }

    $generated_files = [];
    $max_phones = ($type === 'fap') ? $max_fap_phones : $max_sip_phones;

    for ($i = 1; $i <= $max_phones; $i++) {
        if (isset($_POST["create_{$i}"]) && $_POST["create_{$i}"] === 'yes') {
            $p_mac = str_replace(':', '', $_POST["mac_{$i}"]);
            $p_filename = "Config-{$p_mac}.cfg";
            $t_filename = PROV_PATH . '/' . PROV_PANA . '/' . $p_filename;
            
            $content = "# Panasonic SIP Phone Standard Format File # DO NOT CHANGE THIS LINE!\n\n";

            if ($type === 'fap') {
                $p_peer = $_POST["peer_{$i}"];
                $p_password = $passwords[$p_peer] ?? '';
                $content .= "## SIP Settings(LINE1)\n";
                $content .= "PHONE_NUMBER_1=\"{$p_peer}\"\n";
                $content .= "SIP_URI_1=\"{$p_peer}\"\n";
                $content .= "SIP_AUTHID_1=\"{$p_peer}\"\n";
                $content .= "SIP_PASS_1=\"{$p_password}\"\n";
            } else { // normal
                $p_peer = "phone{$i}";
                $p_password = $passwords[$p_peer] ?? '';
                $p_exten = $_POST["exten_{$i}"];
                $p_ohans = $_POST["ohans_{$i}"] ?? 'yes';
                $line_prefer = ($p_ohans === 'no') ? "LINE_PREFERENCE_INCOMING=\"NOLN\"\n" : '';
                $content .= "## SIP Settings(LINE1)\n";
                $content .= "PHONE_NUMBER_1=\"{$p_exten}\"\n";
                $content .= "SIP_URI_1=\"{$p_peer}\"\n";
                $content .= "SIP_AUTHID_1=\"{$p_peer}\"\n";
                $content .= "SIP_PASS_1=\"{$p_password}\"\n";
                $content .= $line_prefer;
            }
            
            if (!is_dir(dirname($t_filename))) mkdir(dirname($t_filename), 0755, true);
            file_put_contents($t_filename, $content);
            $generated_files[] = $p_filename;
        }
    }
    
    if (!empty($generated_files)) {
        $_SESSION['flash_message'] = ['type' => 'success', 'text' => "ファイルを生成しました: " . implode(', ', $generated_files)];
    } else {
        $_SESSION['flash_message'] = ['type' => 'info', 'text' => '生成対象の端末が選択されていません。'];
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// GET時処理
$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

$type = $_GET['type'] ?? 'normal'; // URLパラメータでモードを決定

// PanasonicのMACアドレスプレフィックスをJSONから読み込み
$pana_config_path = __DIR__ . '/provisioning/panasonic.json';
$mac_pana_vc = [];
if (is_readable($pana_config_path)) {
    $pana_json = json_decode(file_get_contents($pana_config_path), true);
    $mac_pana_vc = $pana_json['mac_prefixes'] ?? [];
}

// 生成対象となる電話機のリストを作成
$provisionable_phones = [];
if ($type === 'fap') {
    for ($i = 1; $i <= $max_fap_phones; $i++) {
        $peer_name = 'FAP' . sprintf("%03d", $i);
        $mac_addr = AbspFunctions\get_db_item("ABS/FAP/MAC", $peer_name);
        if ($mac_addr !== '') {
            foreach ($mac_pana_vc as $prefix) {
                if (stripos($mac_addr, $prefix) === 0) {
                    $provisionable_phones[] = ['peer_num' => $i, 'peer_name' => $peer_name, 'mac' => $mac_addr];
                    break;
                }
            }
        }
    }
} else { // normal
    for ($i = 1; $i <= $max_sip_phones; $i++) {
        $peer_name = "phone{$i}";
        $mac_addr = AbspFunctions\get_db_item("ABS/PINFO/{$peer_name}", 'MAC');
        $exten = AbspFunctions\get_db_item("ABS/ERV", $peer_name);
        if ($mac_addr !== '' && $exten !== '') {
            foreach ($mac_pana_vc as $prefix) {
                if (stripos($mac_addr, $prefix) === 0) {
                    $provisionable_phones[] = ['peer_num' => $i, 'peer_name' => $peer_name, 'mac' => $mac_addr, 'exten' => $exten];
                    break;
                }
            }
        }
    }
}


?>
<h2>パナソニック <?= ($type === 'fap') ? 'フリーアドレス用' : '' ?>ピア設定ファイル生成</h2>
<p style="font-size: 0.9em; color: var(--secondary-text-color); margin-top: 0;">
    内線設定でMACアドレスが設定済みのパナソニック端末を検出し、個別の設定ファイルを一括で生成します。
</p>

<?php if ($flash_message): ?>
<div class="notice-message" style="color: <?= $flash_message['type'] === 'error' ? '#f44336' : '#4CAF50' ?>; margin-bottom: 1.5em; font-weight: bold;">
    <?= htmlspecialchars($flash_message['text'], ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>

<form action="" method="post">
    <input type="hidden" name="function" value="createpeer">
    <input type="hidden" name="type" value="<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>">
    <div class="table-container">
        <table class="absp-table">
            <thead>
                <tr>
                    <th>ピア名</th>
                    <?php if ($type === 'normal'): ?><th>内線番号</th><?php endif; ?>
                    <th>MACアドレス</th>
                    <th>オフフック応答</th>
                    <th style="text-align: center;">生成対象</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($provisionable_phones)): ?>
                    <tr><td colspan="5" style="text-align: center; padding: 20px;">設定可能なパナソニック端末が見つかりません。</td></tr>
                <?php else: ?>
                    <?php foreach($provisionable_phones as $phone): ?>
                    <tr>
                        <td><?= htmlspecialchars($phone['peer_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <?php if ($type === 'normal'): ?><td><?= htmlspecialchars($phone['exten'], ENT_QUOTES, 'UTF-8') ?></td><?php endif; ?>
                        <td><?= htmlspecialchars($phone['mac'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <select name="ohans_<?= $phone['peer_num'] ?>">
                                <option value="yes" selected>する</option>
                                <option value="no">しない</option>
                            </select>
                        </td>
                        <td style="text-align: center;">
                            <input type="hidden" name="peer_<?= $phone['peer_num'] ?>" value="<?= htmlspecialchars($phone['peer_name'], ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="mac_<?= $phone['peer_num'] ?>" value="<?= htmlspecialchars($phone['mac'], ENT_QUOTES, 'UTF-8') ?>">
                            <?php if ($type === 'normal'): ?><input type="hidden" name="exten_<?= $phone['peer_num'] ?>" value="<?= htmlspecialchars($phone['exten'], ENT_QUOTES, 'UTF-8') ?>"><?php endif; ?>
                            <input type="checkbox" name="create_<?= $phone['peer_num'] ?>" value="yes">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div style="margin-top: 1em;">
        <button type="submit" class="btn btn-primary">選択した端末のファイルを一括生成</button>
        <p style="font-size: 0.9em; color: var(--secondary-text-color); margin-top: 0.5em;">
            ファイルは <strong><?= htmlspecialchars(PROV_PATH . '/' . PROV_PANA, ENT_QUOTES, 'UTF-8') ?></strong> ディレクトリに作成されます。
        </p>
    </div>
</form>
