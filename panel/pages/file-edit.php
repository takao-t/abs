<?php
if (!defined('ABS_PANEL_INCLUDED')) {
    die("Direct access is not permitted.");
}

global $ami;

// 制限するページの場合、直接指定されても読み込まない
if ($ami->getDbItem('ABS/PANEL', 'PGRESTRICTED') !== 'NO') {
    die("File Editor not permitted");
}

// POST時処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $flash_message = ['type' => 'success', 'text' => '処理が完了しました。'];
    $function = $_POST['function'] ?? '';

    switch($function) {
        case 'open':
            $file = $_POST['readfile'] ?? '';
            $target = ASTDIR . '/' . $file;
            if ($file && is_readable($target)) {
                $_SESSION['file_editor_content'] = file_get_contents($target);
                $_SESSION['file_editor_filename'] = $file;

                if (isset($_POST['do_backup']) && $_POST['do_backup'] === 'yes') {
                    $backup_file = $target . '.bak';
                    if (copy($target, $backup_file)) {
                        $flash_message['text'] = "ファイルを開き、バックアップを作成しました。";
                    } else {
                        $flash_message = ['type' => 'error', 'text' => "ファイルを開きましたが、バックアップの作成に失敗しました。"];
                    }
                }
            } else {
                $flash_message = ['type' => 'error', 'text' => 'ファイルを開けませんでした。'];
            }
            break;
        
        case 'save':
            $file = $_POST['savefile'] ?? '';
            $content = str_replace("\r", '', $_POST['contents'] ?? '');
            $target = ASTDIR . '/' . $file;
            if ($file && file_put_contents($target, $content) !== false) {
                $flash_message['text'] = "ファイル {$file} を保存しました。";
            } else {
                $flash_message = ['type' => 'error', 'text' => 'ファイルの保存に失敗しました。'];
                $_SESSION['file_editor_content'] = $content; // 失敗時は内容を維持
                $_SESSION['file_editor_filename'] = $file;
            }
            break;

        case 'create':
            $file = trim($_POST['newfile'] ?? '');
            $target = ASTDIR . '/' . $file;
            if ($file) {
                if (file_exists($target)) {
                    $flash_message = ['type' => 'error', 'text' => 'そのファイルは既に存在します。'];
                } elseif (touch($target)) {
                    $flash_message['text'] = "ファイル {$file} を新規作成しました。";
                    $_SESSION['file_editor_filename'] = $file;
                    $_SESSION['file_editor_content'] = '';
                } else {
                    $flash_message = ['type' => 'error', 'text' => 'ファイルの作成に失敗しました。'];
                }
            } else {
                 $flash_message = ['type' => 'error', 'text' => 'ファイル名を入力してください。'];
            }
            break;
    }

    if ($flash_message) {
        $_SESSION['flash_message'] = $flash_message;
    }
    header('Location: index.php?page=file-edit');
    exit;
}

// GET時処理
$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

// セッションから編集中のファイル情報を取得
$current_file = $_SESSION['file_editor_filename'] ?? '';
$current_content = $_SESSION['file_editor_content'] ?? '';
unset($_SESSION['file_editor_filename'], $_SESSION['file_editor_content']);

// ファイルリストの取得
$file_list = [];
$dir_handle = opendir(ASTDIR);
if ($dir_handle) {
    while (false !== ($file = readdir($dir_handle))) {
        if (is_file(ASTDIR . '/' . $file)) {
            $file_list[] = $file;
        }
    }
    closedir($dir_handle);
    sort($file_list);
}

?>
<h2>ファイル編集</h2>

<div class="notice-message" style="color: #f44336; border: 1px solid #f44336; padding: 1em; margin-bottom: 1.5em; border-radius: 4px;">
    <strong>⚠️ 重大な警告:</strong> この機能はサーバー上の設定ファイルを直接編集します。誤った編集はシステム全体を停止させる可能性があります。使用には最大限の注意を払い、可能であればこの機能を無効にすることを強く推奨します。
</div>

<?php if ($flash_message): ?>
<div class="notice-message" style="color: <?= $flash_message['type'] === 'error' ? '#f44336' : '#4CAF50' ?>; margin-bottom: 1.5em; font-weight: bold;">
    <?= htmlspecialchars($flash_message['text'], ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>

<div style="display: flex; gap: 2em;">
    <div style="flex-grow: 1;">
        <h4>編集中のファイル: <?= htmlspecialchars($current_file, ENT_QUOTES, 'UTF-8') ?: '(ファイルを選択してください)' ?></h4>
        <form action="" method="post">
            <input type="hidden" name="function" value="save">
            <input type="hidden" name="savefile" value="<?= htmlspecialchars($current_file, ENT_QUOTES, 'UTF-8') ?>">
            <textarea name="contents" style="width: 100%; height: 400px; font-family: monospace; background-color: var(--surface-color); color: var(--text-color); border: 1px solid var(--border-color); border-radius: 4px;"><?= htmlspecialchars($current_content, ENT_QUOTES, 'UTF-8') ?></textarea>
            <div style="margin-top: 1em;">
                <button type="submit" class="btn btn-primary" <?= empty($current_file) ? 'disabled' : '' ?>>保存</button>
            </div>
        </form>
    </div>

    <div style="width: 250px; flex-shrink: 0;">
        <h4>ファイルを開く</h4>
        <form action="" method="post">
            <input type="hidden" name="function" value="open">
            <select name="readfile" size="15" style="width: 100%; margin-bottom: 1em; background-color: var(--surface-color); color: var(--text-color); border: 1px solid var(--border-color);">
                <?php foreach($file_list as $file): ?>
                    <option value="<?= htmlspecialchars($file, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($file, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
            <div class="form-inline-group" style="justify-content: space-between;">
                <button type="submit" class="btn">開く</button>
                <div>
                    <input type="checkbox" id="do_backup" name="do_backup" value="yes">
                    <label for="do_backup">バックアップ</label>
                </div>
            </div>
        </form>

        <h4 style="margin-top: 2em;">ファイル新規作成</h4>
        <form action="" method="post" class="form-inline-group">
            <input type="hidden" name="function" value="create">
            <label for="newfile" style="white-space: nowrap;">ファイル名:</label>
            <input type="text" id="newfile" name="newfile" class="input-middle" style="width: 100%;">
            <button type="submit" class="btn">作成</button>
        </form>
    </div>
</div>
