<?php
// index.php で生成された $ami インスタンスを使用
global $ami;

if (!defined('ABS_PANEL_INCLUDED') || !is_object($ami)) {
    die("Direct access is not permitted.");
}

// POST時処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $flash_message = ['type' => 'success', 'text' => '設定を保存しました。'];
    $function = $_POST['function'] ?? '';

    if ($function == 'vm_settings') {
        $ami->putDbItem('ABS/VM', 'PIN', $_POST['pin'] ?? '');
        $ami->putDbItem('ABS/VM', 'RPIN', $_POST['rpin'] ?? '');
    } else {
        $flash_message = null; // No action taken
    }

    if ($flash_message) {
        $_SESSION['flash_message'] = $flash_message;
    }
    header('Location: index.php?page=vm-config-page');
    exit;
}

// GET時処理
$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

// 音声フォーマット変換
// (注意: audio/convert.sh に実行権限が必要です)
exec('audio/convert.sh abs-tcmessage abs-tcrmessage > /dev/null 2>&1');

// PIN設定値を取得
$pin_setting = $ami->getDbItem('ABS/VM', 'PIN');
$rpin_setting = $ami->getDbItem('ABS/VM', 'RPIN');

// 音声ファイルのパスと最終更新日時
$tcmessage_path = 'audio/abs-tcmessage.mp3';
$tcrmessage_path = 'audio/abs-tcrmessage.mp3';

// file_exists 等はPHP標準関数なのでそのまま使用
$tcmessage_mtime = file_exists($tcmessage_path) ? date("Y-m-d H:i:s", filemtime($tcmessage_path)) : '不明';
$tcrmessage_mtime = file_exists($tcrmessage_path) ? date("Y-m-d H:i:s", filemtime($tcrmessage_path)) : '不明';

?>
<h2>留守録設定</h2>

<?php if ($flash_message): ?>
<div class="notice-message" style="color: <?= $flash_message['type'] === 'error' ? '#f44336' : '#4CAF50' ?>; margin-bottom: 1.5em; font-weight: bold;">
    <?= htmlspecialchars($flash_message['text'], ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>

<h3>PIN設定</h3>
<p style="font-size: 0.9em; color: var(--secondary-text-color); margin-top: 0;">
    留守番電話メッセージの再生や、応答メッセージを電話機から録音するためのPIN（暗証番号）を設定します。
</p>
<form action="" method="POST">
    <input type="hidden" name="function" value="vm_settings">
    <div class="form-inline-group" style="margin-bottom: 0.8em;">
        <label for="pin">留守録再生PIN:</label>
        <input type="text" id="pin" name="pin" value="<?= htmlspecialchars($pin_setting, ENT_QUOTES, 'UTF-8') ?>" class="input-short">
    </div>
    <div class="form-inline-group">
        <label for="rpin">メッセージ録音PIN:</label>
        <input type="text" id="rpin" name="rpin" value="<?= htmlspecialchars($rpin_setting, ENT_QUOTES, 'UTF-8') ?>" class="input-short">
    </div>
    <button type="submit" class="btn btn-primary" style="margin-top: 1em;">設定を保存</button>
</form>

<h3>応答音声確認</h3>
<div style="padding: 1em; background-color: var(--surface-color); border: 1px solid var(--border-color); border-radius: 6px;">
    <figure style="margin: 0 0 1em 0;">
        <figcaption style="font-size: 0.9em; color: var(--secondary-text-color); margin-bottom: 0.5em;">再生後切断メッセージ</figcaption>
        <audio controls src="<?= $tcmessage_path ?>?t=<?= time() ?>" style="width: 100%;">
            Your browser does not support the audio element.
        </audio>
        <p style="font-size: 0.8em; color: var(--secondary-text-color); text-align: right; margin: 0.3em 0 0 0;">
            最終更新: <?= $tcmessage_mtime ?>
        </p>
    </figure>

    <figure style="margin: 0;">
        <figcaption style="font-size: 0.9em; color: var(--secondary-text-color); margin-bottom: 0.5em;">再生後 要件録音メッセージ</figcaption>
        <audio controls src="<?= $tcrmessage_path ?>?t=<?= time() ?>" style="width: 100%;">
            Your browser does not support the audio element.
        </audio>
        <p style="font-size: 0.8em; color: var(--secondary-text-color); text-align: right; margin: 0.3em 0 0 0;">
            最終更新: <?= $tcrmessage_mtime ?>
        </p>
    </figure>
</div>
