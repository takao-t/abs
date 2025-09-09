<?php
if (!defined('ABS_PANEL_INCLUDED')) {
    die("Direct access is not permitted.");
}

// GET時処理
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'clear') {
    unset($_SESSION['gs_master_generator']);
    header('Location: index.php?page=prov-gs-master');
    exit;
}

// PSOT時処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $flash_message = ['type' => 'success', 'text' => '処理が完了しました。'];
    $function = $_POST['function'] ?? '';

    switch ($function) {
        case 'genconfig':
            $inputs = [
                'ntp_addr'   => $_POST['ntp_addr'] ?? '',
                'regi_addr'  => $_POST['regi_addr'] ?? '',
                'regi_port'  => $_POST['regi_port'] ?? '',
                'proxy_addr' => $_POST['proxy_addr'] ?? '',
                'proxy_port' => $_POST['proxy_port'] ?? '',
                'prov_path'  => $_POST['prov_path'] ?? '',
                'ringtone'   => $_POST['ringtone'] ?? '0',
                'callwait'   => $_POST['callwait'] ?? '0',
            ];

            // --- XML生成 ---
            $content = <<<EOT
<?xml version="1.0" encoding="UTF-8" ?>
<gs_provision version="1">
  <config version="1">
    <P30>{$inputs['ntp_addr']}</P30>
    <P47>{$inputs['regi_addr']}:{$inputs['regi_port']}</P47>
    <P48>{$inputs['proxy_addr']}:{$inputs['proxy_port']}</P48>
    <P81>1</P81>
    <P91>{$inputs['callwait']}</P91>
    <P104>{$inputs['ringtone']}</P104>
    <P122>1</P122>
    <P191>0</P191>
    <P192>{$inputs['prov_path']}</P192>
    <P212>1</P212>
    <P237>{$inputs['prov_path']}</P237>
    <P290>{\P\a\\r\kx|\k\\e\yx+| x+ | \+x+ *x+ | *xx*x+ }</P290>
    <P331>{$inputs['prov_path']}</P331>
    <P2916>4</P2916>
    <P2918>0</P2918>
    <P6766>3</P6766>
    <P8369>2</P8369>
  </config>
</gs_provision>
EOT;
            // 生成結果をセッションに保存
            $_SESSION['gs_master_generator'] = [
                'content' => $content,
                'form_inputs' => $inputs
            ];
            $flash_message['text'] = '設定ファイルを生成しました。内容を確認して保存してください。';
            break;

        case 'savetofile':
            if (isset($_POST['savechecked']) && $_POST['savechecked'] === 'yes') {
                $result = $_SESSION['gs_master_generator'] ?? null;
                if ($result) {
                    $master_file = PROV_PATH . '/' . PROV_GS . '/cfg.xml';
                    
                    if (!is_dir(dirname($master_file))) mkdir(dirname($master_file), 0755, true);

                    if (file_put_contents($master_file, str_replace("\r", '', $result['content'])) !== false) {
                        $flash_message['text'] = "マスター設定ファイル({$master_file})を保存しました。";
                        unset($_SESSION['gs_master_generator']);
                    } else {
                        $flash_message = ['type' => 'error', 'text' => 'ファイルの保存に失敗しました。パーミッションを確認してください。'];
                    }
                } else {
                    $flash_message = ['type' => 'error', 'text' => '保存対象のデータが見つかりません。再度生成してください。'];
                }
            } else {
                $flash_message = ['type' => 'error', 'text' => '保存するには確認チェックボックスをオンにしてください。'];
            }
            break;
    }

    if ($flash_message) {
        $_SESSION['flash_message'] = $flash_message;
    }
    header('Location: index.php?page=prov-gs-master');
    exit;
}

// GET時処理(2)
$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

$generator_data = $_SESSION['gs_master_generator'] ?? null;

$server_ip = $_SERVER['SERVER_ADDR'] ?? '192.168.1.1';
$defaults = [
    'ntp_addr'   => $server_ip,
    'regi_addr'  => $server_ip,
    'regi_port'  => '5070',
    'proxy_addr' => $server_ip,
    'proxy_port' => '5070',
    'prov_path'  => $server_ip . '/' . PROV_GS,
    'ringtone'   => '0',
    'callwait'   => '0',
];

$form_values = $generator_data['form_inputs'] ?? $defaults;

?>
<h2>Grandstream マスターファイル生成</h2>

<?php if ($flash_message): ?>
<div class="notice-message" style="color: <?= $flash_message['type'] === 'error' ? '#f44336' : '#4CAF50' ?>; margin-bottom: 1.5em; font-weight: bold;">
    <?= htmlspecialchars($flash_message['text'], ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>

<div class="notice-message" style="color: var(--secondary-text-color); border: 1px solid var(--border-color); padding: 1em; margin-bottom: 1.5em; border-radius: 4px;">
    <strong>電話機側設定の注意:</strong><br>
    メンテナンス -> アップグレードとプロビジョニング<br>
    <ul>
        <li>設定を以下を介して更新: <strong>HTTP</strong></li>
        <li>設定サーバパス: <strong><?= htmlspecialchars($form_values['prov_path'], ENT_QUOTES, 'UTF-8') ?></strong></li>
    </ul>
</div>


<h3>ステップ1：設定値の入力</h3>
<form action="" method="post">
    <input type="hidden" name="function" value="genconfig">
    <input type="hidden" name="prov_path" value="<?= htmlspecialchars($form_values['prov_path'], ENT_QUOTES, 'UTF-8') ?>">
    <div class="table-container">
        <table class="absp-table" style="width: auto;">
            <tr><td><label for="ntp_addr">NTPサーバ</label></td><td><input type="text" id="ntp_addr" name="ntp_addr" class="input-middle" value="<?= htmlspecialchars($form_values['ntp_addr'], ENT_QUOTES, 'UTF-8') ?>"></td></tr>
            <tr><td><label for="regi_addr">レジスタサーバ</label></td><td><input type="text" id="regi_addr" name="regi_addr" class="input-middle" value="<?= htmlspecialchars($form_values['regi_addr'], ENT_QUOTES, 'UTF-8') ?>"> : <input type="text" name="regi_port" class="input-short" value="<?= htmlspecialchars($form_values['regi_port'], ENT_QUOTES, 'UTF-8') ?>"></td></tr>
            <tr><td><label for="proxy_addr">プロキシサーバ</label></td><td><input type="text" id="proxy_addr" name="proxy_addr" class="input-middle" value="<?= htmlspecialchars($form_values['proxy_addr'], ENT_QUOTES, 'UTF-8') ?>"> : <input type="text" name="proxy_port" class="input-short" value="<?= htmlspecialchars($form_values['proxy_port'], ENT_QUOTES, 'UTF-8') ?>"></td></tr>
            <tr><td><label for="ringtone">デフォルト着信音</label></td><td><select id="ringtone" name="ringtone"><option value="0" <?= ($form_values['ringtone'] == '0')?'selected':'' ?>>0</option><option value="1" <?= ($form_values['ringtone'] == '1')?'selected':'' ?>>1</option><option value="2" <?= ($form_values['ringtone'] == '2')?'selected':'' ?>>2</option><option value="3" <?= ($form_values['ringtone'] == '3')?'selected':'' ?>>3</option></select></td></tr>
            <tr><td><label for="callwait">通話中着信</label></td><td><select id="callwait" name="callwait"><option value="0" <?= ($form_values['callwait'] == '0')?'selected':'' ?>>する</option><option value="1" <?= ($form_values['callwait'] == '1')?'selected':'' ?>>しない</option></select></td></tr>
        </table>
    </div>
    <div style="margin-top: 1em;">
        <button type="submit" class="btn btn-primary">生成実行</button>
        <a href="index.php?page=prov-gs-master&action=clear" class="btn">フォームをクリア</a>
    </div>
</form>

<?php if ($generator_data): ?>
    <h3 style="margin-top: 2em;">ステップ2：生成結果の確認と保存</h3>
    <form action="" method="post">
        <input type="hidden" name="function" value="savetofile">
        
        <h4>生成結果 (cfg.xml)</h4>
        <textarea name="content" readonly style="width: 100%; height: 250px; font-family: monospace; background-color: var(--surface-color); color: var(--text-color); border: 1px solid var(--border-color); border-radius: 4px;"><?= htmlspecialchars($generator_data['content'], ENT_QUOTES, 'UTF-8') ?></textarea>
        
        <div class="form-inline-group" style="margin-top: 1em;">
            <input type="checkbox" id="savechecked" name="savechecked" value="yes">
            <label for="savechecked">内容を確認しました。ファイルを所定の場所に保存します。</label>
            <button type="submit" class="btn">保存する</button>
        </div>
        <p style="font-size: 0.9em; color: #f44336;">注意: 同じ名前のファイルが存在すると上書きされます。</p>
    </form>
<?php endif; ?>
