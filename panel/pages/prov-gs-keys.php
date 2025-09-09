<?php
if (!defined('ABS_PANEL_INCLUDED')) { die("Direct access is not permitted."); }

// 設定読み込み
$model_id = $_GET['model'] ?? '';
$config_path = __DIR__ . '/provisioning/' . $model_id . '.json';
$config = null;
if ($model_id && is_readable($config_path)) {
    $config = json_decode(file_get_contents($config_path), true);
}

// セッションから生成結果とフォーム入力を取得
$generator_data = $_SESSION['gs_key_generator'][$model_id] ?? null;
$form_values = $generator_data['form_inputs'] ?? [];

// POST時処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $config) {
    $flash_message = ['type' => 'success', 'text' => '処理が完了しました。'];
    $function = $_POST['function'] ?? '';

    switch ($function) {
        case 'keyset':
            $inputs = [];
            for ($i = 1; $i <= $config['key_count']; $i++) {
                $inputs["flex_label{$i}"] = $_POST["flex_label{$i}"] ?? '';
                $inputs["flex_facil{$i}"] = $_POST["flex_facil{$i}"] ?? 'none';
                $inputs["flex_keyarg{$i}"] = $_POST["flex_keyarg{$i}"] ?? '';
            }
            
            $content = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n";
            $content .= "<gs_provision version=\"1\">\n  <config version=\"1\">\n";
            $content .= "    <P148>Grandstream {$config['model']}</P148>\n";

            for ($i = 1; $i <= $config['key_count']; $i++) {
                $p_facil = $inputs["flex_facil{$i}"];
                if ($p_facil !== 'none') {
                    $content .= "    \n";
                    
                    // P値の決定ロジック
                    $p_use_val = '-1'; // デフォルトはnone
                    if (isset($config['p_values_by_func'][$p_facil])) {
                        // GXP2135/2170のようなページ依存のP値を持つモデル用
                        $keys_per_page = 8; // 仮定
                        $p_use_val = ($i <= $keys_per_page) ? $config['p_values_by_func'][$p_facil]['page1'] : $config['p_values_by_func'][$p_facil]['other_pages'];
                    } else {
                        // GXP2130/GRP2604PのようなシンプルなP値を持つモデル用
                        $p_use_map = ['quick' => '0', 'blf' => '1', 'park' => '9'];
                        if (array_key_exists($p_facil, $p_use_map)) {
                           $p_use_val = $p_use_map[$p_facil];
                        }
                    }
                    if ($i === 1 && $p_facil === 'line') $p_use_val = '0'; // キー1がラインの場合の特別処理

                    $content .= "    <P{$config['p_values']['use'][$i]}>{$p_use_val}</P{$config['p_values']['use'][$i]}>\n";
                    $content .= "    <P{$config['p_values']['acc'][$i]}>0</P{$config['p_values']['acc'][$i]}>\n";
                    $content .= "    <P{$config['p_values']['lbl'][$i]}>{$inputs["flex_label{$i}"]}</P{$config['p_values']['lbl'][$i]}>\n";
                    $content .= "    <P{$config['p_values']['val'][$i]}>{$inputs["flex_keyarg{$i}"]}</P{$config['p_values']['val'][$i]}>\n";
                }
            }
            $content .= "  </config>\n</gs_provision>";
            
            $_SESSION['gs_key_generator'][$model_id] = [
                'content' => $content, 'form_inputs' => $inputs
            ];
            $flash_message['text'] = '設定ファイルを生成しました。内容を確認して保存してください。';
            break;

        case 'savetofile':
             if (isset($_POST['savechecked']) && $_POST['savechecked'] === 'yes') {
                if ($generator_data) {
                    $product_file = PROV_PATH . '/' . PROV_GS . '/' . $config['filename'];
                    if (!is_dir(dirname($product_file))) mkdir(dirname($product_file), 0755, true);
                    
                    if (file_put_contents($product_file, str_replace("\r", '', $generator_data['content'])) !== false) {
                        $flash_message['text'] = "設定ファイル({$product_file})を保存しました。";
                        unset($_SESSION['gs_key_generator'][$model_id]);
                    } else {
                        $flash_message = ['type' => 'error', 'text' => 'ファイルの保存に失敗しました。'];
                    }
                }
            } else {
                $flash_message = ['type' => 'error', 'text' => '保存するには確認チェックボックスをオンにしてください。'];
            }
            break;
    }

    if ($flash_message) { $_SESSION['flash_message'] = $flash_message; }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}


?>

<?php if (!$config): ?>
    <h2>エラー</h2>
    <p>指定された機種（<?= htmlspecialchars($model_id, ENT_QUOTES, 'UTF-8') ?>）の設定ファイルが見つかりません。</p>
<?php else: ?>
    <h2><?= htmlspecialchars($config['model'], ENT_QUOTES, 'UTF-8') ?> キー設定</h2>
    <p style="font-size: 0.9em; color: var(--secondary-text-color); margin-top: 0;">
        注：キー1は何も指定しない場合、デフォルトアカウントキーとなります。
    </p>

    <form action="" method="post">
        <input type="hidden" name="function" value="keyset">
        
        <div class="table-container">
            <table class="absp-table">
                <thead>
                    <tr>
                        <th>キー番号</th><th>ラベル</th><th>機能</th><th>値</th>
                        <th style="width: 2em; background-color: var(--bg-color);"></th>
                        <th>キー番号</th><th>ラベル</th><th>機能</th><th>値</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $key_count = $config['key_count'];
                    $rows = ceil($key_count / 2);
                    for ($i = 1; $i <= $rows; $i++):
                        $key_left = $i;
                        $key_right = $i + $rows;
                    ?>
                    <tr>
                        <td><?= $config['key_type'] . $key_left ?></td>
                        <td><input type="text" name="flex_label<?= $key_left ?>" class="input-short2" value="<?= htmlspecialchars($form_values["flex_label{$key_left}"] ?? '', ENT_QUOTES, 'UTF-8') ?>"></td>
                        <td>
                            <select name="flex_facil<?= $key_left ?>">
                                <?php foreach ($config['functions'] as $val => $text): ?>
                                    <option value="<?= $val ?>" <?= (($form_values["flex_facil{$key_left}"] ?? 'none') == $val) ? 'selected' : '' ?>><?= $text ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><input type="text" name="flex_keyarg<?= $key_left ?>" class="input-short2" value="<?= htmlspecialchars($form_values["flex_keyarg{$key_left}"] ?? '', ENT_QUOTES, 'UTF-8') ?>"></td>
                        
                        <td style="background-color: var(--bg-color);"></td>
                        
                        <?php if ($key_right <= $key_count): ?>
                            <td><?= $config['key_type'] . $key_right ?></td>
                            <td><input type="text" name="flex_label<?= $key_right ?>" class="input-short2" value="<?= htmlspecialchars($form_values["flex_label{$key_right}"] ?? '', ENT_QUOTES, 'UTF-8') ?>"></td>
                            <td>
                                <select name="flex_facil<?= $key_right ?>">
                                     <?php foreach ($config['functions'] as $val => $text): ?>
                                        <option value="<?= $val ?>" <?= (($form_values["flex_facil{$key_right}"] ?? 'none') == $val) ? 'selected' : '' ?>><?= $text ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input type="text" name="flex_keyarg<?= $key_right ?>" class="input-short2" value="<?= htmlspecialchars($form_values["flex_keyarg{$key_right}"] ?? '', ENT_QUOTES, 'UTF-8') ?>"></td>
                        <?php else: // 右側にキーがない場合 ?>
                            <td colspan="4"></td>
                        <?php endif; ?>
                    </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </div>
        
        <div style="margin-top: 1em;">
            <button type="submit" class="btn btn-primary">生成実行</button>
        </div>
    </form>
    
    <?php if ($generator_data): ?>
        <h3 style="margin-top: 2em;">生成結果の確認と保存</h3>
        <form action="" method="post">
            <input type="hidden" name="function" value="savetofile">
            <textarea readonly style="width: 100%; height: 250px;"><?= htmlspecialchars($generator_data['content'], ENT_QUOTES, 'UTF-8') ?></textarea>
            <div class="form-inline-group" style="margin-top: 1em;">
                <input type="checkbox" id="savechecked" name="savechecked" value="yes">
                <label for="savechecked">内容を確認しました。ファイル (<?= htmlspecialchars($config['filename'], ENT_QUOTES, 'UTF-8') ?>) を保存します。</label>
                <button type="submit" class="btn">保存する</button>
            </div>
        </form>
    <?php endif; ?>

<?php endif; ?>
