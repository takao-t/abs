<?php
if (!defined('ABS_PANEL_INCLUDED')) {
    die("Direct access is not permitted.");
}

// ディレクトリがない、または書き込めない場合のステータス確認
$dir_error = null;
if (!file_exists(ABSTRUNKS)) {
    // 作成を試みる
    if (!@mkdir(ABSTRUNKS, 0750, true)) {
        $dir_error = 'directory_missing';
    }
} elseif (!is_writable(ABSTRUNKS)) {
    $dir_error = 'not_writable';
}

// エラーがある場合、管理者へのガイドを作成
$setup_command = "";
if ($dir_error) {
    // www-data(Web)が書き込めて、asterisk(PBX)が読める権限設定を案内
    $setup_command = "sudo mkdir -p " . ABSTRUNKS . "\n";
    $setup_command .= "sudo chown www-data:asterisk " . ABSTRUNKS . "\n";
    $setup_command .= "sudo chmod 750 " . ABSTRUNKS;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'clear_generator') {
    unset($_SESSION['generator_result']);
    unset($_SESSION['form_inputs']);
    header('Location: index.php?page=trunk-generator');
    exit;
}

// POST時
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $flash_message = ['type' => 'success', 'text' => '処理が完了しました。'];
    $function = $_POST['function'] ?? '';

    switch ($function) {
        case 'delete_file':
            $target_file = $_POST['filename'] ?? '';
            // セキュリティ対策: パス区切り除去 & プレフィックス確認
            $target_file = basename($target_file);
            
            // 意図しないファイルの削除を防ぐため、命名規則を厳密にチェック
            if (strpos($target_file, 'pjsip_trunk_') === 0 && preg_match('/\.conf$/', $target_file)) {
                $full_path = ABSTRUNKS . '/' . $target_file;
                if (file_exists($full_path)) {
                    if (unlink($full_path)) {
                        $flash_message = ['type' => 'success', 'text' => "設定ファイル {$target_file} を削除しました。"];
                    } else {
                        $flash_message = ['type' => 'error', 'text' => 'ファイルの削除に失敗しました。権限を確認してください。'];
                    }
                } else {
                    $flash_message = ['type' => 'error', 'text' => 'ファイルが見つかりません。'];
                }
            } else {
                $flash_message = ['type' => 'error', 'text' => '不正なファイル名です。削除できません。'];
            }
            break;

        case 'generate':
            $inputs = [
                'template' => trim($_POST['template'] ?? 'hgw'),
                'num'      => trim($_POST['num'] ?? ''),
                'ipaddr'   => trim($_POST['ipaddr'] ?? ''),
                'username' => trim($_POST['username'] ?? ''),
                'password' => trim($_POST['password'] ?? ''),
                'proxy'    => trim($_POST['proxy'] ?? ''),
                'exten'    => trim($_POST['exten'] ?? ''),
            ];

            // トランク名定義
            $trunkdef = ['hgw'=>'hikari-hgw', 'ogw'=>'hikari-ogw', 'rtx'=>'hikari-rtx', 'smart'=>'fsmart', 'opengate'=>'opengate', 'basix'=>'basix'];
            $trunkname = ($trunkdef[$inputs['template']] ?? 'unknown') . $inputs['num'];
            $target_filename = 'pjsip_trunk_' . $inputs['template'] . $inputs['num'] . '.conf';

            $template_path = 'pages/templates/pjsip_trunk_' . $inputs['template'] . '.tmpl';
            if (!is_readable($template_path)) {
                $flash_message = ['type' => 'error', 'text' => 'テンプレートファイルが見つかりません。'];
                $_SESSION['form_inputs'] = $inputs;
                break;
            }
            $content = file_get_contents($template_path);
            $content = str_replace('##NUM##', $inputs['num'], $content);
            $content = str_replace('##IPADDR##', $inputs['ipaddr'], $content);
            $content = str_replace('##USERNAME##', $inputs['username'], $content);
            $content = str_replace('##PASSWORD##', $inputs['password'], $content);
            $content = str_replace('##PROXY##', $inputs['proxy'], $content);
            $content = str_replace('##EXTEN##', $inputs['exten'], $content);
            $content = str_replace('##TRUNKNAME##', $trunkname, $content);

            $acl = 'permit=' . $inputs['ipaddr'] . '/32';
            $acl_templates = ['basix', 'smart', 'opengate', 'rtx'];
            if (in_array($inputs['template'], $acl_templates)) {
                $acl_path = 'pages/templates/' . $inputs['template'] . '.acl';
                $acl = is_readable($acl_path) ? file_get_contents($acl_path) : 'ACLファイルが見つかりません。';
            }
            
            $_SESSION['generator_result'] = [
                'content' => $content, 'acl' => $acl, 'trunkname' => $trunkname,
                'target_filename' => $target_filename, 'form_inputs' => $inputs
            ];
            break;

        case 'savetofile':
            if (isset($_POST['savechecked']) && $_POST['savechecked'] === 'yes') {
                $result = $_SESSION['generator_result'] ?? null;
                if ($result) {
                    // --- 追加: 保存前の最終書き込み権限チェック ---
                    if (!is_writable(ABSTRUNKS)) {
                         $flash_message = ['type' => 'error', 'text' => '保存先ディレクトリに書き込み権限がありません。上部の案内を確認してください。'];
                         break;
                    }

                    $save_path = ABSTRUNKS . '/' . $result['target_filename'];
                    $content_to_save = str_replace("\r", '', $result['content']);
                    if (file_put_contents($save_path, $content_to_save) !== false) {
                        $flash_message['text'] = "ファイル {$result['target_filename']} に保存しました。";
                        unset($_SESSION['generator_result']);
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
    header('Location: index.php?page=trunk-generator');
    exit;
}

$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);
$result = $_SESSION['generator_result'] ?? null;
$form_values = $result['form_inputs'] ?? ($_SESSION['form_inputs'] ?? []);
unset($_SESSION['form_inputs']);

$existing_files = [];
if (!$dir_error) {
    // globでパターンマッチするファイルのみ取得
    $files = glob(ABSTRUNKS . '/pjsip_trunk_*.conf');
    if ($files) {
        foreach ($files as $f) {
            // タイムゾーンをJSTに指定して日時オブジェクトを作成
            $dt = new DateTime();
            $dt->setTimestamp(filemtime($f));
            $dt->setTimezone(new DateTimeZone('Asia/Tokyo'));
            
            $existing_files[] = [
                'name' => basename($f),
                'date' => $dt->format('Y-m-d H:i'), // JSTでフォーマット
                'size' => filesize($f)
            ];
        }
    }
}

?>
<h2>トランク設定ファイル生成</h2>

<?php if ($dir_error): ?>
<div style="background-color: #fff3cd; color: #856404; padding: 15px; border: 1px solid #ffeeba; border-radius: 4px; margin-bottom: 20px;">
    <strong><i class="fas fa-exclamation-triangle"></i> 設定保存ディレクトリの準備が必要です</strong>
    <p style="margin: 5px 0;">
        現在、設定ファイルの保存先ディレクトリ (<code><?= ABSTRUNKS ?></code>) が存在しないか、Webサーバからの書き込み権限がありません。<br>
        SSH等でサーバにログインし、以下のコマンドを実行してください。
    </p>
    <pre style="background: #333; color: #fff; padding: 10px; border-radius: 4px; overflow-x: auto;"><?= htmlspecialchars($setup_command) ?></pre>
</div>
<?php endif; ?>
<?php if ($flash_message): ?>
<div class="notice-message" style="color: <?= $flash_message['type'] === 'error' ? '#f44336' : '#4CAF50' ?>; margin-bottom: 1.5em; font-weight: bold;">
    <?= htmlspecialchars($flash_message['text'], ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>

<?php if (!empty($existing_files)): ?>
<h3>稼働中のトランク設定ファイル</h3>
<div class="table-container">
    <table class="absp-table" style="width: 100%;">
        <thead>
            <tr>
                <th>ファイル名</th>
                <th>更新日時</th>
                <th>サイズ</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($existing_files as $file): ?>
            <tr>
                <td><?= htmlspecialchars($file['name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= $file['date'] ?></td>
                <td><?= $file['size'] ?> bytes</td>
                <td>
                    <form action="" method="post" onsubmit="return confirm('本当に削除しますか？\nAsteriskの設定リロード後に反映されます。');" style="margin:0;">
                        <input type="hidden" name="function" value="delete_file">
                        <input type="hidden" name="filename" value="<?= htmlspecialchars($file['name'], ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" class="btn btn-small" style="background-color: #f44336; color: white; padding: 2px 10px; font-size: 0.8em;">削除</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<hr style="margin: 2em 0; border: 0; border-top: 1px solid var(--border-color);">
<?php endif; ?>
<h3>ステップ1：トランク情報の入力</h3>
<form action="" method="post">
    <input type="hidden" name="function" value="generate">
    <div class="table-container">
        <table class="absp-table" style="width: auto;">
            <tr>
                <td style="width: 150px;"><label for="template">(A) 種別</label></td>
                <td>
                    <select id="template" name="template">
                        <option value="hgw" <?= (($form_values['template'] ?? 'hgw') == 'hgw') ? 'selected' : '' ?>>ひかり電話(ホームゲートウェイ)</option>
                        <option value="ogw" <?= (($form_values['template'] ?? '') == 'ogw') ? 'selected' : '' ?>>ひかり電話(オフィスゲートウェイ)</option>
                        <option value="rtx" <?= (($form_values['template'] ?? '') == 'rtx') ? 'selected' : '' ?>>ひかり電話(RTX/NVR直収)</option>
                        <option value="opengate" <?= (($form_values['template'] ?? '') == 'opengate') ? 'selected' : '' ?>>FUSION(Open Gate)</option>
                        <option value="basix" <?= (($form_values['template'] ?? '') == 'basix') ? 'selected' : '' ?>>Brastel(BASIX)</option>
                    </select>
                </td>
            </tr>
            <tr><td><label for="num">(B) トランク番号</label></td><td><input type="text" id="num" name="num" class="input-short" value="<?= htmlspecialchars($form_values['num'] ?? '', ENT_QUOTES, 'UTF-8') ?>"> <span style="font-size: 0.9em; color: var(--secondary-text-color);">※同一種別を複数使用する場合</span></td></tr>
            <tr><td><label for="ipaddr">(C) ドメイン/IPアドレス</label></td><td><input type="text" id="ipaddr" name="ipaddr" class="input-middle" value="<?= htmlspecialchars($form_values['ipaddr'] ?? '', ENT_QUOTES, 'UTF-8') ?>"> <span style="font-size: 0.9em; color: var(--secondary-text-color);"> サーバのIPアドレスを指定する場合はここに </span></td></tr>
            <tr><td><label for="proxy">(D) プロキシ/ドメイン</label></td><td><input type="text" id="proxy" name="proxy" class="input-middle" value="<?= htmlspecialchars($form_values['proxy'] ?? '', ENT_QUOTES, 'UTF-8') ?>"> <span style="font-size: 0.9em; color: var(--secondary-text-color);"> ドメインを別途指定する場合はここに </span></td></tr>
            <tr><td><label for="username">(E) ユーザ名</label></td><td><input type="text" id="username" name="username" class="input-middle" value="<?= htmlspecialchars($form_values['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></td></tr>
            <tr><td><label for="password">(F) パスワード</label></td><td><input type="text" id="password" name="password" class="input-middle" value="<?= htmlspecialchars($form_values['password'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></td></tr>
            <tr><td><label for="exten">(G) 契約番号</label></td><td><input type="text" id="exten" name="exten" class="input-middle" value="<?= htmlspecialchars($form_values['exten'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></td></tr>
        </table>
    </div>
    <div style="margin-top: 1em;">
        <button type="submit" class="btn btn-primary">設定を生成</button>
        <a href="index.php?page=trunk-generator&action=clear_generator" class="btn">フォームをクリア</a>
    </div>
</form>


<?php if ($result): // 生成結果がある場合のみ表示 ?>
<h3 style="margin-top: 2em;">ステップ2：生成結果の確認と保存</h3>
<p style="font-size: 0.9em; color: var(--secondary-text-color); margin-top: 0;">
    ABS上で使用するトランク名は「<strong><?= htmlspecialchars($result['trunkname'], ENT_QUOTES, 'UTF-8') ?></strong>」です。
</p>

<h4>生成された設定内容</h4>
<textarea readonly style="width: 100%; height: 300px; font-family: monospace; background-color: var(--surface-color); color: var(--text-color); border: 1px solid var(--border-color); border-radius: 4px;"><?= htmlspecialchars($result['content'], ENT_QUOTES, 'UTF-8') ?></textarea>

<form action="" method="post" class="form-inline-group" style="margin-top: 1em;">
    <input type="hidden" name="function" value="savetofile">
    <input type="checkbox" id="savechecked" name="savechecked" value="yes">
    <label for="savechecked">内容を確認しました。「<?= htmlspecialchars(ABSTRUNKS . '/' . $result['target_filename'], ENT_QUOTES, 'UTF-8') ?>」として保存します。</label>
    <button type="submit" class="btn">保存する</button>
</form>
<p style="font-size: 0.9em; color: #f44336;">注意: 同じ名前のファイルが存在すると上書きされます。</p>

<h4>手動で行う設定</h4>
<p style="font-size: 0.9em; color: var(--secondary-text-color); margin-top: 0;">
    ファイルを保存後、以下の設定を手動で行ってください(参考)。
</p>
<ol style="font-size: 0.9em; padding-left: 2em;">
    `pjsip_base_acl.conf`の`[acl]`セクションに以下を追記:
        <textarea readonly style="width: 100%; height: 80px; font-family: monospace; background-color: var(--surface-color); color: var(--text-color); border: 1px solid var(--border-color); border-radius:4px;"><?= htmlspecialchars($result['acl'], ENT_QUOTES, 'UTF-8') ?></textarea>
</ol>
<?php endif; ?>
