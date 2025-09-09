<?php
if (!defined('ABS_PANEL_INCLUDED')) {
    die("Direct access is not permitted.");
}

// GET時処理
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'clear') {
    unset($_SESSION['pana_master_generator']);
    header('Location: index.php?page=prov-pana-master');
    exit;
}

// POST時処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $flash_message = ['type' => 'success', 'text' => '処理が完了しました。'];
    $function = $_POST['function'] ?? '';
    $config_path = __DIR__ . '/provisioning/panasonic-master.json';
    $config = is_readable($config_path) ? json_decode(file_get_contents($config_path), true) : null;

    if ($config) {
        switch ($function) {
            case 'genconfig':
                $inputs = [];
                $content = "# Panasonic SIP Phone Standard Format File # DO NOT CHANGE THIS LINE!\n\n";
                foreach ($config['settings'] as $setting) {
                    $key = $setting['key'];
                    $value = $_POST[$key] ?? '';
                    $inputs[$key] = $value;
                    $content .= "{$key}=\"{$value}\"\n";
                }

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

$config_path = __DIR__ . '/provisioning/panasonic-master.json';
$config = is_readable($config_path) ? json_decode(file_get_contents($config_path), true) : null;

// セッションから生成結果とフォーム入力を取得
$generator_data = $_SESSION['pana_master_generator'] ?? null;

// デフォルト値の動的設定
$server_ip = $_SERVER['SERVER_ADDR'] ?? '192.168.1.1';
$defaults = [];
if ($config) {
    foreach ($config['settings'] as $setting) {
        $default_val = $setting['default'] ?? '';
        if ($default_val === 'server_ip') {
            $defaults[$setting['key']] = $server_ip;
        } elseif ($default_val === 'server_ip/pana') {
            $defaults[$setting['key']] = $server_ip . '/' . PROV_PANA;
        } else {
            $defaults[$setting['key']] = $default_val;
        }
    }
}
$form_values = $generator_data['form_inputs'] ?? $defaults;

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
    <form action="" method="post">
        <input type="hidden" name="function" value="genconfig">
        <div class="table-container">
            <table class="absp-table" style="width: auto;">
                <tbody>
                    <?php foreach ($config['settings'] as $setting): ?>
                    <tr>
                        <td style="width: 200px;"><label for="<?= $setting['key'] ?>"><?= $setting['label'] ?></label></td>
                        <td>
                            <?php if ($setting['type'] === 'text'): ?>
                                <input type="text" id="<?= $setting['key'] ?>" name="<?= $setting['key'] ?>" class="input-middle" value="<?= htmlspecialchars($form_values[$setting['key']] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <?php elseif ($setting['type'] === 'select'): ?>
                                <select id="<?= $setting['key'] ?>" name="<?= $setting['key'] ?>">
                                    <?php foreach ($setting['options'] as $val => $text): ?>
                                    <option value="<?= $val ?>" <?= (($form_values[$setting['key']] ?? '') == $val) ? 'selected' : '' ?>><?= $text ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
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
            <textarea readonly style="width: 100%; height: 250px;"><?= htmlspecialchars($generator_data['content'], ENT_QUOTES, 'UTF-8') ?></textarea>
            <div class="form-inline-group" style="margin-top: 1em;">
                <input type="checkbox" id="savechecked" name="savechecked" value="yes">
                <label for="savechecked">内容を確認しました。ファイルを保存します。</label>
                <button type="submit" class="btn">保存する</button>
            </div>
        </form>
    <?php endif; ?>

<?php endif; ?>
