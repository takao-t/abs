<?php
if (!defined('ABS_PANEL_INCLUDED')) {
    die("Direct access is not permitted.");
}

// パスの定数定義がない場合のフォールバック（動作確認用）
if (!defined('PROV_PATH')) define('PROV_PATH', __DIR__ . '/provisioning');
if (!defined('PROV_PANA')) define('PROV_PANA', 'pana');

$config_path = __DIR__ . '/provisioning/panasonic-master.json';
$config = is_readable($config_path) ? json_decode(file_get_contents($config_path), true) : null;

// GET時処理: クリアアクション
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'clear') {
    unset($_SESSION['pana_master_generator']);
    header('Location: index.php?page=prov-pana-master');
    exit;
}

// POST時処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $flash_message = ['type' => 'success', 'text' => '処理が完了しました。'];
    $function = $_POST['function'] ?? '';

    if ($config) {
        switch ($function) {
            case 'genconfig':
                // フォーム入力値の保存
                $inputs = $_POST;
                
                // 設定値の取得ヘルパー
                $getVal = function($key, $default = '') use ($inputs) {
                    return $inputs[$key] ?? $default;
                };

                // プロビジョニングサーバーのIP（URL生成用）
                $prov_ip = $getVal('PROV_SERVER_IP', $_SERVER['SERVER_ADDR'] ?? '192.168.1.1');
                
                // コンテンツ生成 (ヒアドキュメントで可読性を向上)
                $content = "# Panasonic SIP Phone Standard Format File # DO NOT CHANGE THIS LINE!\n\n";
                
                $content .= "## Provisioning Settings\n";
                // URLの組み立て: http://IP/prov/pana/Config-{MAC}.cfg
                $base_url = "http://{$prov_ip}/" . PROV_PANA;
                $content .= "CFG_STANDARD_FILE_PATH=\"{$base_url}/Config-{MAC}.cfg\"\n";
                $content .= "CFG_PRODUCT_FILE_PATH=\"{$base_url}/Config-{MODEL}.cfg\"\n\n";

                $content .= "#HTTPD Settings\n";
                $content .= "HTTPD_PORTOPEN_AUTO=\"Y\"\n\n";

                $content .= "#NTP Settings\n";
                $content .= "NTP_ADDR=\"" . $getVal('NTP_ADDR') . "\"\n";
                $content .= "TIME_SYNC_INTVL=\"60\"\n";
                $content .= "TIME_QUERY_INTVL=\"43200\"\n";
                $content .= "LOCAL_TIME_ZONE_POSIX=\"\"\n";
                $content .= "TIME_ZONE=\"" . $getVal('TIME_ZONE') . "\"\n";
                $content .= "DST_ENABLE=\"N\"\n\n";

                $content .= "## SIP Settings\n";
                // JSONの設定を元にSIP関連を出力（または直接記述でも可）
                $sip_keys = [
                    'SIP_RGSTR_ADDR_1', 'SIP_RGSTR_PORT_1',
                    'SIP_PRXY_ADDR_1', 'SIP_PRXY_PORT_1',
                    'SIP_OUTPROXY_ADDR_1', 'SIP_OUTPROXY_PORT_1',
                    'SIP_SVCDOMAIN_1'
                ];
                
                // プレゼンスポートはデフォルト固定または入力値があればそれを使う
                // 今回はリストにないので固定出力、あるいは入力を追加も可能ですが、
                // ご要望の「プレゼンスサーバー項目自体不要」に合わせて削除しました。
                // ただし期待される出力例にあった SIP_PRSNC_PORT_1="5070" は残すか判断が必要ですが、
                // とりあえず出力例に合わせてポートだけ出しておきます。
                
                foreach ($sip_keys as $key) {
                    if (isset($inputs[$key])) {
                        $content .= "{$key}=\"{$inputs[$key]}\"\n";
                    }
                }
                // 期待される出力に含まれていたSIP_PRSNC_PORT_1 (サーバーアドレスは削除済)
                $content .= "SIP_PRSNC_PORT_1=\"5070\"\n";

                $_SESSION['pana_master_generator'] = ['content' => $content, 'form_inputs' => $inputs];
                $flash_message['text'] = '設定ファイルを生成しました。内容を確認して保存してください。';
                break;

            case 'savetofile':
                $generator_data = $_SESSION['pana_master_generator'] ?? null;
                if (isset($_POST['savechecked']) && $_POST['savechecked'] === 'yes') {
                    if ($generator_data) {
                        $product_file = PROV_PATH . '/' . PROV_PANA . '/' . $config['filename'];
                        if (!is_dir(dirname($product_file))) {
                            mkdir(dirname($product_file), 0755, true);
                        }
                        
                        if (file_put_contents($product_file, str_replace("\r", '', $generator_data['content'])) !== false) {
                            $flash_message['text'] = "設定ファイル({$product_file})を保存しました。";
                            unset($_SESSION['pana_master_generator']);
                        } else {
                            $flash_message = ['type' => 'error', 'text' => 'ファイルの保存に失敗しました。'];
                        }
                    }
                } else {
                    $flash_message = ['type' => 'error', 'text' => '保存するには確認チェックボックスをオンにしてください。'];
                }
                break;
        }
    } else {
        $flash_message = ['type' => 'error', 'text' => '設定定義ファイルが見つかりません。'];
    }

    if ($flash_message) {
        $_SESSION['flash_message'] = $flash_message;
    }
    header('Location: index.php?page=prov-pana-master');
    exit;
}

$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

// セッションから生成結果とフォーム入力を取得
$generator_data = $_SESSION['pana_master_generator'] ?? null;

// デフォルト値の計算
$server_ip = $_SERVER['SERVER_ADDR'] ?? '192.168.1.1';
$form_values = $generator_data['form_inputs'] ?? [];

// 初期値の充填
if ($config && empty($form_values)) {
    foreach ($config['settings'] as $setting) {
        $default_val = $setting['default'] ?? '';
        if ($default_val === 'server_ip') {
            $form_values[$setting['key']] = $server_ip;
        } else {
            $form_values[$setting['key']] = $default_val;
        }
        
        // ポートの初期値
        if (!empty($setting['has_port']) && !empty($setting['port_key'])) {
            $form_values[$setting['port_key']] = $setting['port_default'] ?? '5070';
        }
    }
}
?>

<?php if (!$config): ?>
    <h2>エラー</h2>
    <div class="notice-message" style="color: #f44336;">
        マスター設定ファイル（<code>pages/provisioning/panasonic-master.json</code>）が見つかりません。
    </div>
<?php else: ?>
    <h2><?= htmlspecialchars($config['model_name'], ENT_QUOTES, 'UTF-8') ?></h2>

    <?php if ($flash_message): ?>
    <div class="notice-message" style="color: <?= $flash_message['type'] === 'error' ? '#f44336' : '#4CAF50' ?>; margin-bottom: 1.5em; font-weight: bold;">
        <?= htmlspecialchars($flash_message['text'], ENT_QUOTES, 'UTF-8') ?>
    </div>
    <?php endif; ?>

    <h3>ステップ1：共通設定の入力</h3>
    <p>サーバーIPを変更すると、<code>CFG_STANDARD_FILE_PATH</code>等のURLが自動的に書き換わります。</p>
    <form action="" method="post">
        <input type="hidden" name="function" value="genconfig">
        <div class="table-container">
            <table class="absp-table" style="width: auto;">
                <tbody>
                    <?php foreach ($config['settings'] as $setting): ?>
                    <tr>
                        <th style="width: 220px; text-align: left; padding: 8px;">
                            <label for="<?= $setting['key'] ?>"><?= $setting['label'] ?></label>
                            <?php if(!empty($setting['description'])): ?>
                                <br><small style="font-weight: normal; color: #666;"><?= $setting['description'] ?></small>
                            <?php endif; ?>
                        </th>
                        <td style="padding: 8px;">
                            <div style="display: flex; align-items: center;">
                                <?php if ($setting['type'] === 'text'): ?>
                                    <input type="text" id="<?= $setting['key'] ?>" name="<?= $setting['key'] ?>" 
                                           class="input-middle" 
                                           style="width: 250px;"
                                           value="<?= htmlspecialchars($form_values[$setting['key']] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <?php elseif ($setting['type'] === 'select'): ?>
                                    <select id="<?= $setting['key'] ?>" name="<?= $setting['key'] ?>" style="width: 260px;">
                                        <?php foreach ($setting['options'] as $val => $text): ?>
                                        <option value="<?= $val ?>" <?= (($form_values[$setting['key']] ?? '') == $val) ? 'selected' : '' ?>><?= $text ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>

                                <?php /* ポート入力欄の表示（has_portフラグがある場合） */ ?>
                                <?php if (!empty($setting['has_port'])): ?>
                                    <span style="margin: 0 10px;">:</span>
                                    <input type="text" 
                                           name="<?= $setting['port_key'] ?>" 
                                           placeholder="Port"
                                           style="width: 60px;"
                                           value="<?= htmlspecialchars($form_values[$setting['port_key']] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div style="margin-top: 1em;">
            <button type="submit" class="btn btn-primary">生成実行</button>
            <a href="index.php?page=prov-pana-master&action=clear" class="btn">フォームをクリア</a>
        </div>
    </form>
    
    <?php if ($generator_data): ?>
        <h3 style="margin-top: 2em;">ステップ2：生成結果の確認と保存</h3>
        <form action="" method="post">
            <input type="hidden" name="function" value="savetofile">
            <h4>生成結果 (<?= htmlspecialchars($config['filename'], ENT_QUOTES, 'UTF-8') ?>)</h4>
            <textarea readonly style="width: 100%; height: 350px; font-family: monospace;"><?= htmlspecialchars($generator_data['content'], ENT_QUOTES, 'UTF-8') ?></textarea>
            <div class="form-inline-group" style="margin-top: 1em;">
                <input type="checkbox" id="savechecked" name="savechecked" value="yes">
                <label for="savechecked">内容を確認しました。ファイルを保存します。</label>
                <button type="submit" class="btn">保存する</button>
            </div>
        </form>
    <?php endif; ?>

<?php endif; ?>
