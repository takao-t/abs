<?php
if (!defined('ABS_PANEL_INCLUDED')) {
    die("Direct access is not permitted.");
}

// --- POST処理 ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_hints'])) {
    
    // 指定されたコマンドを実行
    AbspFunctions\exec_cli_command(' channel originate Local/s@sub-exthintgen application NoOp');
    
    // ユーザーへの通知メッセージをセッションに保存
    $_SESSION['flash_message'] = [
        'type' => 'success', 
        'text' => '内線hintの生成を開始しました。すこし待ってからページを再読み込みしてください。'
    ];
    
    // フォームの再送信を防ぐためにリダイレクト
    header('Location: index.php?page=hint-generator');
    exit;
}

// --- GET処理 ---

// セッションからフラッシュメッセージを取得（あれば）
$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

// 設定ファイルのパスを定義
define('HINTS_CONF_PATH', '/etc/asterisk/extensions_exthints.conf');
$hints_content = '';

// 設定ファイルの内容を読み込む
if (is_readable(HINTS_CONF_PATH)) {
    $content = file_get_contents(HINTS_CONF_PATH);
    if ($content === false) {
        $hints_content = 'エラー: ファイル (' . HINTS_CONF_PATH . ') の読み込みに失敗しました。';
    } else {
        // セキュリティのため、HTMLエンティティに変換
        $hints_content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
    }
} else {
    $hints_content = '設定ファイル (' . HINTS_CONF_PATH . ') が見つからないか、読み取り権限がありません。';
}
?>

<h2>内線ヒント生成</h2>

<!-- POST処理後に表示するメッセージエリア -->
<?php if ($flash_message): ?>
<div style="display: flex; align-items: center; justify-content: space-between; border: 1px solid #4ade80; background-color: rgba(74, 222, 128, 0.1); color: #4ade80; padding: 1em; margin-bottom: 1.5em; border-radius: 4px;">
    <span><?= htmlspecialchars($flash_message['text'], ENT_QUOTES, 'UTF-8') ?></span>
    <a href="index.php?page=hint-generator" class="btn">再読み込み</a>
</div>
<?php endif; ?>


<!-- 現在の設定内容表示ボックス -->
<h3>現在のhint設定内容</h3>
<div class="command-output" style="min-height: 300px; max-height: 50vh; overflow-y: auto;">
    <pre style="margin: 0;"><?= $hints_content ?></pre>
</div>


<!-- 生成実行ボタン -->
<div style="margin-top: 1.5em;">
    <form action="index.php?page=hint-generator" method="post" onsubmit="return confirm('現在の内線情報からhint設定を生成します。よろしいですか？');">
        <button type="submit" name="generate_hints" class="btn btn-primary">現在の内線情報から生成</button>
    </form>
</div>

<p style="font-size: 0.9em; color: var(--secondary-text-color); margin-top: 1em;">
    この機能は、現在の内線設定情報を元に、電話機のBLF用 'hint' の設定ファイルを自動生成します。<br>
    生成処理はバックグラウンドで実行されます。
</p>
