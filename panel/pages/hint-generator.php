<?php
if (!defined('ABS_PANEL_INCLUDED')) {
    die("Direct access is not permitted.");
}

// POST時処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['function']) && $_POST['function'] === 'savetofile') {
    $flash_message = ['type' => 'success', 'text' => '設定を保存しました。'];
    
    if (isset($_POST['savechecked']) && $_POST['savechecked'] === 'yes') {
        $filename = $_POST['filename'] ?? '';
        $content = $_POST['content'] ?? '';
        $save_path = ASTDIR . '/' . $filename;

        if ($filename && file_put_contents($save_path, str_replace("\r", '', $content)) !== false) {
            $flash_message['text'] = "ファイル ({$save_path}) を保存しました。";
        } else {
            $flash_message = ['type' => 'error', 'text' => 'ファイルの保存に失敗しました。パーミッションを確認してください。'];
        }
    } else {
        $flash_message = ['type' => 'error', 'text' => '保存するには確認チェックボックスをオンにしてください。'];
    }

    if ($flash_message) {
        $_SESSION['flash_message'] = $flash_message;
    }
    header('Location: index.php?page=hint-generator');
    exit;
}

// GET時処理
$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

// ヒント設定のコンテンツを生成
$content = "[ext-hints]\n";
$localtech = AbspFunctions\get_db_item('ABS', 'EXTTECH') ?: 'PJSIP'; // デフォルト値を設定

// $max_sip_phones は config.php で定義されている想定
for ($i = 1; $i <= $max_sip_phones; $i++) {
    $exten = AbspFunctions\get_db_item('ABS/ERV', "phone{$i}");
    if ($exten !== '') {
        $content .= "exten => {$exten},hint,{$localtech}/phone{$i}\n";
    }
}

$target_filename = 'extensions_exthints.conf';

?>
<h2>内線ヒント生成</h2>
<p style="font-size: 0.9em; color: var(--secondary-text-color); margin-top: 0;">
    BLF用にAsteriskの内線状態（hint）を定義するファイルを生成します。PJSIPのみ対応です。<br>
</p>

<?php if ($flash_message): ?>
<div class="notice-message" style="color: <?= $flash_message['type'] === 'error' ? '#f44336' : '#4CAF50' ?>; margin-bottom: 1.5em; font-weight: bold;">
    <?= htmlspecialchars($flash_message['text'], ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>


<h3>生成結果</h3>
<form action="" method="post">
    <input type="hidden" name="function" value="savetofile">
    <input type="hidden" name="filename" value="<?= htmlspecialchars($target_filename, ENT_QUOTES, 'UTF-8') ?>">
    <textarea name="content" readonly style="width: 100%; height: 400px;"><?= htmlspecialchars($content, ENT_QUOTES, 'UTF-8') ?></textarea>
    
    <div class="form-inline-group" style="margin-top: 1em;">
        <input type="checkbox" id="savechecked" name="savechecked" value="yes">
        <label for="savechecked">内容を確認しました。「<?= htmlspecialchars(ASTDIR . '/' . $target_filename, ENT_QUOTES, 'UTF-8') ?>」として保存します。</label>
        <button type="submit" class="btn btn-primary">保存する</button>
    </div>
    <p style="font-size: 0.9em; color: #f44336;">注意: 保存すると現在のファイルは上書きされます。</p>
</form>
