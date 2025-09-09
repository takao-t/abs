<?php
if (!defined('ABS_PANEL_INCLUDED')) {
    die("Direct access is not permitted.");
}

// POST時処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['backup_file'])) {

    // --- 1.1 ファイルアップロードの基本チェック ---
    if ($_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'ファイルアップロードでエラーが発生しました。エラーコード: ' . $_FILES['backup_file']['error']];
        header('Location: index.php?page=backup-restore-page');
        exit;
    }

    $file_path = $_FILES['backup_file']['tmp_name'];
    $file_content = file_get_contents($file_path);

    // --- 1.2 バックアップファイルの正当性チェック ---
    if (strpos($file_content, '# ABS Panel Backup File') === false) {
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => '無効なファイル形式です。ABS Panelのバックアップファイルではありません。'];
        header('Location: index.php?page=backup-restore-page');
        exit;
    }

    // --- 1.3 (新機能) クリーンリストアが選択された場合の処理 ---
    $is_clean_restore = isset($_POST['clean_restore']) && $_POST['clean_restore'] === '1';
    if ($is_clean_restore) {
        // バックアップファイルから削除対象のファミリを抽出
        preg_match_all('/^(\/([^\s\/]+)\/)/m', $file_content, $matches);
        $families_to_delete = array_unique($matches[2]);

        foreach ($families_to_delete as $family) {
            $command = "database deltree {$family}";
            AbspFunctions\exec_cli_command($command); // 戻り値のチェックは省略 (必要なら追加)
        }
    }

    // --- 1.4 ファイル内容の解析とリストア実行 ---
    $lines = explode("\n", $file_content);
    $entry_count = 0;
    $error_count = 0;
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || $line[0] === '#') {
            continue;
        }
        if (preg_match('/^(\/[^\s]+)\s+:\s+(.*)$/', $line, $matches)) {
            $full_key_path = $matches[1];
            $value = $matches[2];
            list(, $family, $key) = explode('/', $full_key_path, 3);
            
            $command = sprintf("database put %s %s \"%s\"", $family, $key, addslashes($value));
            $output = AbspFunctions\exec_cli_command($command);

            $is_success = strpos($output, 'Response: Success') === 0 && strpos($output, 'Updated database successfully') !== false;
            if ($is_success) {
                $entry_count++;
            } else {
                $error_count++;
            }
        }
    }

    // --- 1.5 結果をフラッシュメッセージに保存してリダイレクト ---
    $restore_mode = $is_clean_restore ? 'クリーンリストア' : 'リストア';
    if ($error_count > 0) {
        $message = "{$restore_mode}処理が完了しましたが、{$error_count}件のエラーが発生しました。";
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => $message];
    } else {
        $message = "{$restore_mode}処理が正常に完了しました。{$entry_count}件のデータを書き込みました。";
        $_SESSION['flash_message'] = ['type' => 'success', 'text' => $message];
    }
    
    header('Location: index.php?page=backup-restore-page');
    exit;
}

// GET時処理
$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);
?>
<h2>バックアップとリストア</h2>

<?php if ($flash_message): ?>
<div class="notice-message" style="color: <?= $flash_message['type'] === 'error' ? '#f44336' : '#4CAF50' ?>; border: 1px solid currentColor; padding: 1em; margin-bottom: 1.5em; border-radius: 4px;">
    <?= htmlspecialchars($flash_message['text'], ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>

<h3>バックアップ</h3>
<p style="font-size: 0.9em; color: var(--secondary-text-color); margin-top: 0;">
    現在のシステム設定（AstDBに保存されている情報）をテキストファイルとしてダウンロードします。
</p>
<div class="form-inline-group">
    <a href="php/download-backup.php" class="btn btn-primary">バックアップファイルを作成・ダウンロード</a>
</div>

<h3 style="margin-top: 2em;">リストア</h3>
<p style="font-size: 0.9em; color: var(--secondary-text-color); margin-top: 0;">
    バックアップファイルをアップロードして、システム設定を復元します。
</p>

<form action="index.php?page=backup-restore-page" method="post" enctype="multipart/form-data" onsubmit="return confirm('本当にリストアを実行しますか？');">
    <div class="form-inline-group" style="margin-bottom: 1em;">
        <input type="file" name="backup_file" accept=".txt" required>
        <button type="submit" class="btn btn-primary">アップロードしてリストアを実行</button>
    </div>
    <div class="form-inline-group">
        <label style="display: flex; align-items: center; cursor: pointer;">
            <input type="checkbox" name="clean_restore" value="1" style="margin-right: 0.5em;">
            リストアの前に、関連する既存データをすべて削除する (クリーンリストア)
        </label>
        <strong style="color: #f44336; font-size: 0.9em;">(注意: この操作は元に戻せません)</strong>
    </div>
</form>
