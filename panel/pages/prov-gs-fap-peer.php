<?php
if (!defined('ABS_PANEL_INCLUDED')) {
    die("Direct access is not permitted.");
}

// POST時処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['function']) && $_POST['function'] === 'createpeer') {
    
    // --- 事前準備 ---
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
    $mc_target = AbspFunctions\get_db_item("ABS/MCAST1", "TARGET");
    $p_vpkin = trim($_POST["vpkin"] ?? '0');
    $p_ldsp = trim($_POST["ldsp"] ?? '0');
    $p_blfmode = trim($_POST["blfmode"] ?? '11');

    // --- ファイル生成処理 ---
    $generated_files = [];
    if (!empty($_POST['create_peers'])) {
        foreach ($_POST['create_peers'] as $peer_num) {
            $peer_name = "FAP" . sprintf("%03d", $peer_num);
            $p_password = $passwords[$peer_name] ?? '';
            $p_mac_raw = $_POST["mac_{$peer_num}"] ?? '';
            $p_mpuse = $_POST["mpuse_{$peer_num}"] ?? 'no';

            $p_mac = str_replace(':', '', $p_mac_raw);
            $p_mac_l = strtolower($p_mac);

            $filename_lower = "cfg{$p_mac_l}.xml";
            $filename_upper = "cfg" . strtoupper($p_mac) . '.xml';
            $target_path_lower = PROV_PATH . '/' . PROV_GS . '/' . $filename_lower;
            $target_path_upper = PROV_PATH . '/' . PROV_GS . '/' . $filename_upper;
            
            $d_peer = ($p_ldsp == 1) ? sprintf("%03d", $peer_num) : $peer_name;

            $vpk_list = [
                2 => "\n    <P1365>{$p_blfmode}</P1365>\n    <P1366>0</P1366>\n    <P1467>ログイン</P1467>\n    <P1468>$peer_name</P1468>",
                3 => "\n    <P1367>{$p_blfmode}</P1367>\n    <P1368>0</P1368>\n    <P1469>ログイン</P1469>\n    <P1470>$peer_name</P1470>",
                4 => "\n    <P1369>{$p_blfmode}</P1369>\n    <P1370>0</P1370>\n    <P1471>ログイン</P1471>\n    <P1472>$peer_name</P1472>"
            ];
            $vpk_add = $vpk_list[$p_vpkin] ?? "";
            
            $mcp_add = "";
            if ($mc_target !== "" && $p_mpuse === "yes") {
                $mcp_add = "\n    <P1569>{$mc_target}</P1569>\n    <P22208>1</P22208>";
            }

            $content = <<<EOT
<?xml version="1.0" encoding="UTF-8" ?>
<gs_provision version="1">
<mac>{$p_mac_l}</mac>
  <config version="1">
    <P4511>{$p_mac_raw}</P4511>
    <P8>0</P8>
    <P35>{$peer_name}</P35>
    <P36>{$peer_name}</P36>
    <P270>{$peer_name}</P270>
    <P3>{$peer_name}</P3>
    <P34>{$p_password}</P34>
    <P271>1</P271>
    <P1465>{$d_peer}</P1465>{$vpk_add}{$mcp_add}
  </config>
</gs_provision>
EOT;
            if (!is_dir(dirname($target_path_lower))) mkdir(dirname($target_path_lower), 0755, true);
            file_put_contents($target_path_lower, $content);
            file_put_contents($target_path_upper, $content);
            $generated_files[] = $filename_lower;
        }
    }
    
    if (!empty($generated_files)) {
        $_SESSION['flash_message'] = ['type' => 'success', 'text' => "ファイルを生成しました: " . implode(', ', $generated_files)];
    } else {
        $_SESSION['flash_message'] = ['type' => 'info', 'text' => '生成対象の端末が選択されていません。'];
    }
    header('Location: index.php?page=prov-gs-fap-peer');
    exit;
}

// GET時処理
$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

// GrandstreamのMACアドレスプレフィックスをJSONから読み込み
$gs_config_path = __DIR__ . '/provisioning/grandstream.json';
$mac_gs_vc = [];
if (is_readable($gs_config_path)) {
    $gs_json = json_decode(file_get_contents($gs_config_path), true);
    $mac_gs_vc = $gs_json['mac_prefixes'] ?? [];
}

// 生成対象となるFAP電話機のリストを作成
$provisionable_fap_phones = [];
for ($i = 1; $i <= $max_fap_phones; $i++) {
    $peer_name = 'FAP' . sprintf("%03d", $i);
    $mac_addr = AbspFunctions\get_db_item("ABS/FAP/MAC", $peer_name);

    if ($mac_addr !== '') {
        foreach ($mac_gs_vc as $prefix) {
            if (stripos($mac_addr, $prefix) === 0) {
                $provisionable_fap_phones[] = ['peer_num' => $i, 'peer_name' => $peer_name, 'mac' => $mac_addr];
                break;
            }
        }
    }
}

?>
<h2>Grandstream フリーアドレス用ピア設定ファイル生成</h2>
<p style="font-size: 0.9em; color: var(--secondary-text-color); margin-top: 0;">
    FAユーザ設定でMACアドレスが登録済みのGrandstream端末を検出し、個別の設定ファイルを一括で生成します。
</p>

<?php if ($flash_message): ?>
<div class="notice-message" style="color: <?= $flash_message['type'] === 'error' ? '#f44336' : '#4CAF50' ?>; margin-bottom: 1.5em; font-weight: bold;">
    <?= htmlspecialchars($flash_message['text'], ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>

<form action="" method="post">
    <input type="hidden" name="function" value="createpeer">
    
    <h3>共通オプション</h3>
    <div class="form-inline-group" style="margin-bottom: 1.5em; align-items: flex-end;">
        <div>
            <label for="ldsp">回線名表示</label>
            <select id="ldsp" name="ldsp">
                <option value="0">ピア名 (FAP001)</option>
                <option value="1">数字のみ (001)</option>
            </select>
        </div>
        <div>
            <label for="blfmode">VPK BLFモード</label>
            <select id="blfmode" name="blfmode">
                <option value="11">GXPシリーズ</option>
                <option value="1">GRPシリーズ</option>
            </select>
        </div>
        <div>
            <label for="vpkin">ログイン状態表示VPK</label>
            <select id="vpkin" name="vpkin">
                <option value="0">使用しない</option>
                <option value="2">VPK2</option>
                <option value="3">VPK3</option>
                <option value="4">VPK4</option>
            </select>
        </div>
    </div>

    <h3>生成対象端末</h3>
    <div class="table-container">
        <table class="absp-table">
            <thead>
                <tr>
                    <th>ピア名</th>
                    <th>MACアドレス</th>
                    <th>MCページング</th>
                    <th style="text-align: center;">生成対象</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($provisionable_fap_phones)): ?>
                    <tr><td colspan="4" style="text-align: center; padding: 20px;">設定可能なGrandstream端末(FA)が見つかりません。</td></tr>
                <?php else: ?>
                    <?php foreach($provisionable_fap_phones as $phone): ?>
                    <tr>
                        <td><?= htmlspecialchars($phone['peer_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($phone['mac'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <select name="mpuse_<?= $phone['peer_num'] ?>">
                                <option value="no">使わない</option>
                                <option value="yes">使う</option>
                            </select>
                        </td>
                        <td style="text-align: center;">
                            <input type="hidden" name="mac_<?= $phone['peer_num'] ?>" value="<?= htmlspecialchars($phone['mac'], ENT_QUOTES, 'UTF-8') ?>">
                            <input type="checkbox" name="create_peers[]" value="<?= $phone['peer_num'] ?>">
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
            ファイルは <strong><?= htmlspecialchars(PROV_PATH . '/' . PROV_GS, ENT_QUOTES, 'UTF-8') ?></strong> ディレクトリに作成されます。
        </p>
    </div>
</form>
