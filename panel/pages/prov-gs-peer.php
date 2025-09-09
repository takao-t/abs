<?php
if (!defined('ABS_PANEL_INCLUDED')) {
    die("Direct access is not permitted.");
}

// POST時処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['function']) && $_POST['function'] === 'createpeer') {
    
    // --- 事前準備 ---
    // パスワード情報をウィザードファイルから読み込む
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
    // マルチキャストターゲットを取得
    $mc_target = AbspFunctions\get_db_item("ABS/MCAST1", "TARGET");

    // --- ファイル生成処理 ---
    $generated_files = [];
    for ($i = 1; $i <= $max_sip_phones; $i++) {
        $peer_name = "phone{$i}";
        // チェックボックスがオンになっているピアのみを処理
        if (isset($_POST["create_{$i}"]) && $_POST["create_{$i}"] === 'yes') {
            $p_password = $passwords[$peer_name] ?? '';
            $p_exten = $_POST["exten_{$i}"] ?? '';
            $p_mac_raw = $_POST["mac_{$i}"] ?? '';
            $p_mpuse = $_POST["mpuse_{$i}"] ?? 'no';
            
            $p_mac = str_replace(':', '', $p_mac_raw);
            $p_mac_l = strtolower($p_mac);

            // ファイル名 (大文字/小文字両方に対応)
            $filename_lower = "cfg{$p_mac_l}.xml";
            $filename_upper = "cfg" . strtoupper($p_mac) . '.xml';
            $target_path_lower = PROV_PATH . '/' . PROV_GS . '/' . $filename_lower;
            $target_path_upper = PROV_PATH . '/' . PROV_GS . '/' . $filename_upper;

            // マルチキャスト用設定の追加
            $mcp_add = "";
            if ($mc_target !== "" && $p_mpuse === "yes") {
                $mcp_add = "\n    <P1569>{$mc_target}</P1569>\n    <P22208>1</P22208>";
            }

            // XMLコンテンツ生成
            $content = <<<EOT
<?xml version="1.0" encoding="UTF-8" ?>
<gs_provision version="1">
<mac>{$p_mac_l}</mac>
  <config version="1">
    <P4511>{$p_mac_raw}</P4511>
    <P8>0</P8>
    <P35>{$peer_name}</P35>
    <P36>{$peer_name}</P36>
    <P270>{$p_exten}</P270>
    <P3>{$p_exten}</P3>
    <P34>{$p_password}</P34>
    <P271>1</P271>{$mcp_add}
  </config>
</gs_provision>
EOT;
            // ディレクトリがなければ作成
            if (!is_dir(dirname($target_path_lower))) mkdir(dirname($target_path_lower), 0755, true);
            
            file_put_contents($target_path_lower, $content);
            file_put_contents($target_path_upper, $content);
            $generated_files[] = $filename_lower;
        }
    }
    
    // フラッシュメッセージを設定してリダイレクト
    if (!empty($generated_files)) {
        $file_list_str = implode(', ', $generated_files);
        $_SESSION['flash_message'] = ['type' => 'success', 'text' => "ファイルを生成しました: {$file_list_str}"];
    } else {
        $_SESSION['flash_message'] = ['type' => 'info', 'text' => '生成対象の端末が選択されていません。'];
    }
    header('Location: index.php?page=prov-gs-peer');
    exit;
}

// GET時処理
$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

// GrandstreamのMACアドレスプレフィックスを読み込み
$gs_config_path = __DIR__ . '/provisioning/grandstream.json';
$mac_gs_vc = [];
if (is_readable($gs_config_path)) {
    $gs_json = json_decode(file_get_contents($gs_config_path), true);
    $mac_gs_vc = $gs_json['mac_prefixes'] ?? [];
}

// 生成対象となる電話機のリストを作成
$provisionable_phones = [];
for ($i = 1; $i <= $max_sip_phones; $i++) {
    $peer_name = "phone{$i}";
    $mac_addr = AbspFunctions\get_db_item("ABS/PINFO/{$peer_name}", 'MAC');
    $exten = AbspFunctions\get_db_item("ABS/ERV", $peer_name);

    if ($mac_addr !== '' && $exten !== '') {
        foreach ($mac_gs_vc as $prefix) {
            if (stripos($mac_addr, $prefix) === 0) {
                $provisionable_phones[] = [
                    'peer_num' => $i,
                    'peer_name' => $peer_name,
                    'exten' => $exten,
                    'mac' => $mac_addr
                ];
                break; // 一致したら次の電話機へ
            }
        }
    }
}

?>
<h2>Grandstream ピア設定ファイル生成</h2>
<p style="font-size: 0.9em; color: var(--secondary-text-color); margin-top: 0;">
    内線設定で内線番号とMACアドレスが設定済みのGrandstream端末を検出し、個別の設定ファイルを一括で生成します。
</p>

<?php if ($flash_message): ?>
<div class="notice-message" style="color: <?= $flash_message['type'] === 'error' ? '#f44336' : '#4CAF50' ?>; margin-bottom: 1.5em; font-weight: bold;">
    <?= htmlspecialchars($flash_message['text'], ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>

<form action="" method="post">
    <input type="hidden" name="function" value="createpeer">
    <div class="table-container">
        <table class="absp-table">
            <thead>
                <tr>
                    <th>ピア名</th>
                    <th>内線番号</th>
                    <th>MACアドレス</th>
                    <th>MCページング</th>
                    <th style="text-align: center;">生成対象</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($provisionable_phones)): ?>
                    <tr><td colspan="5" style="text-align: center; padding: 20px;">設定可能なGrandstream端末が見つかりません。</td></tr>
                <?php else: ?>
                    <?php foreach($provisionable_phones as $phone): ?>
                    <tr>
                        <td><?= htmlspecialchars($phone['peer_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($phone['exten'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($phone['mac'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <select name="mpuse_<?= $phone['peer_num'] ?>">
                                <option value="no">使わない</option>
                                <option value="yes">使う</option>
                            </select>
                        </td>
                        <td style="text-align: center;">
                            <input type="hidden" name="exten_<?= $phone['peer_num'] ?>" value="<?= htmlspecialchars($phone['exten'], ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="mac_<?= $phone['peer_num'] ?>" value="<?= htmlspecialchars($phone['mac'], ENT_QUOTES, 'UTF-8') ?>">
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
            ファイルは <strong><?= htmlspecialchars(PROV_PATH . '/' . PROV_GS, ENT_QUOTES, 'UTF-8') ?></strong> ディレクトリに作成されます。
        </p>
    </div>
</form>
