<?php
if (!defined('ABS_PANEL_INCLUDED')) {
    die("Direct access is not permitted.");
}

global $ami;

// POST時処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $p_command = $_POST['command'] ?? '';
    
    // 実行結果と実行したコマンドをセッションに保存
    // 静的呼び出しをインスタンスメソッドに変更
    $_SESSION['last_command_output'] = $ami->execCliCommand($p_command);
    $_SESSION['last_command_run'] = $p_command;

    header('Location: index.php?page=exec-cmd-page');
    exit;
}

// GET時処理
$last_command = $_SESSION['last_command_run'] ?? '';
$command_output_raw = $_SESSION['last_command_output'] ?? '';
unset($_SESSION['last_command_run'], $_SESSION['last_command_output']);

// 出力結果をHTML表示用に安全にフォーマットする
$formatted_output = '';
if ($command_output_raw) {
    // 最初に全体をHTMLエスケープして安全性を確保
    $safe_output = htmlspecialchars($command_output_raw, ENT_QUOTES, 'UTF-8');
    // Output: を削除
    $formatted_output = str_replace(
        ['Output: ', ],
        ['', ],
        $safe_output
    );
    $formatted_output = ltrim($formatted_output, '<br>');
}

?>
<h2>コマンド実行</h2>

<div class="notice-message" style="color: #f44336; border: 1px solid #f44336; padding: 1em; margin-bottom: 1.5em; border-radius: 4px;">
    <strong>警告:</strong> この機能はAsteriskのCLIコマンドを直接実行します。システムの根幹に関わるコマンド（`core`, `database`系など）の実行には十分注意してください。誤った操作はシステム全体を停止させる可能性があります。
</div>

<form action="" method="POST" class="form-inline-group">
    <label for="command">*CLI&gt;</label>
    <input type="text" id="command" name="command" value="<?= htmlspecialchars($last_command, ENT_QUOTES, 'UTF-8') ?>" style="flex-grow: 1; font-family: Consolas;">
    <button type="submit" class="btn btn-primary">実行</button>
</form>

<?php if ($command_output_raw): ?>
    <h3 style="margin-top: 2em;">実行結果</h3>
    <div class="command-output">
        <?= $formatted_output ?>
    </div>
<?php endif; ?>
