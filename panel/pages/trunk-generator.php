<?php
if (!defined('ABS_PANEL_INCLUDED')) {
    die("Direct access is not permitted.");
}

// --- パス定義 ---
$base_dir = ABSTRUNKS;
$path_trunks = $base_dir . '/trunks';
$path_acl    = $base_dir . '/acl';
$templates_dir = 'pages/templates'; // テンプレートディレクトリ
$json_path = $templates_dir . '/trunk_definitions.json'; // 定義JSON

// --- 定義ファイルの読み込み ---
$trunk_definitions = [];
if (file_exists($json_path)) {
    $json_content = file_get_contents($json_path);
    $trunk_definitions = json_decode($json_content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        // JSONエラー時は空にしておくか、エラー表示する
        $trunk_definitions = [];
        echo "<div class='notice-message'>Error: JSON definition file is corrupted.</div>";
    }
} else {
    echo "<div class='notice-message'>Error: trunk_definitions.json not found.</div>";
}

// --- ディレクトリチェックと自動作成 (変更なし) ---
$dir_error = null;
$setup_command = "";

if (!file_exists($base_dir)) {
    $dir_error = 'base_missing';
} elseif (!is_writable($base_dir)) {
    $dir_error = 'base_not_writable';
} else {
    if (!file_exists($path_trunks)) {
        if (!@mkdir($path_trunks, 0750, true)) $dir_error = 'sub_create_fail';
    }
    if (!file_exists($path_acl)) {
        if (!@mkdir($path_acl, 0750, true)) $dir_error = 'sub_create_fail';
    }
}

if ($dir_error) {
    $setup_command = "sudo mkdir -p " . $base_dir . "\n";
    $setup_command .= "sudo chown www-data:asterisk " . $base_dir . "\n";
    $setup_command .= "sudo chmod 770 " . $base_dir; 
}

// --- アクション処理 ---

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'clear_generator') {
    unset($_SESSION['generator_result']);
    unset($_SESSION['form_inputs']);
    header('Location: index.php?page=trunk-generator');
    exit;
}

// POST処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $flash_message = ['type' => 'success', 'text' => '処理が完了しました。'];
    $function = $_POST['function'] ?? '';

    switch ($function) {
        // --- ファイル削除 (変更なし) ---
        case 'delete_file':
            $target_file = $_POST['filename'] ?? '';
            $target_file = basename($target_file);
            
            if (strpos($target_file, 'pjsip_trunk_') === 0 && preg_match('/\.conf$/', $target_file)) {
                $trunk_full_path = $path_trunks . '/' . $target_file;
                $acl_filename = 'acl_' . $target_file;
                $acl_full_path = $path_acl . '/' . $acl_filename;

                if (file_exists($trunk_full_path)) {
                    if (unlink($trunk_full_path)) {
                        $msg = "トランク設定 {$target_file} を削除しました。";
                        if (file_exists($acl_full_path)) {
                            unlink($acl_full_path);
                            $msg .= " 関連するACL設定も削除しました。";
                        }
                        $flash_message = ['type' => 'success', 'text' => $msg];
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

        // --- 生成プレビュー (JSON使用版) ---
        case 'generate':
            $inputs = [
                'template_key' => trim($_POST['template'] ?? ''), // JSONのキー
                'num'          => trim($_POST['num'] ?? ''),
                'ipaddr'       => trim($_POST['ipaddr'] ?? ''),
                'username'     => trim($_POST['username'] ?? ''),
                'password'     => trim($_POST['password'] ?? ''),
                'proxy'        => trim($_POST['proxy'] ?? ''),
                'exten'        => trim($_POST['exten'] ?? ''),
            ];

            // 選択されたテンプレートキーがJSON定義にあるか確認
            if (!isset($trunk_definitions[$inputs['template_key']])) {
                $flash_message = ['type' => 'error', 'text' => '不正なテンプレートが選択されました。'];
                $_SESSION['form_inputs'] = $inputs;
                break;
            }

            // 定義情報の取得
            $def = $trunk_definitions[$inputs['template_key']];
            
            // トランク名生成
            $trunkname = $def['prefix'] . $inputs['num'];
            $target_filename = 'pjsip_trunk_' . $inputs['template_key'] . $inputs['num'] . '.conf';
            $acl_filename = 'acl_' . $target_filename;

            // テンプレート読み込み
            $template_path = $templates_dir . '/' . $def['template'];
            if (!is_readable($template_path)) {
                $flash_message = ['type' => 'error', 'text' => 'テンプレートファイル(' . $def['template'] . ')が見つかりません。'];
                $_SESSION['form_inputs'] = $inputs;
                break;
            }
            $content = file_get_contents($template_path);
            
            // 置換処理
            $content = str_replace('##NUM##', $inputs['num'], $content);
            $content = str_replace('##IPADDR##', $inputs['ipaddr'], $content);
            $content = str_replace('##USERNAME##', $inputs['username'], $content);
            $content = str_replace('##PASSWORD##', $inputs['password'], $content);
            $content = str_replace('##PROXY##', $inputs['proxy'], $content);
            $content = str_replace('##EXTEN##', $inputs['exten'], $content);
            $content = str_replace('##TRUNKNAME##', $trunkname, $content);

            // ACLコンテンツ生成 (JSON定義に基づく)
            $acl_content = '';
            
            if (!empty($def['acl_template'])) {
                // ACLテンプレートが指定されている場合
                $acl_tmpl_path = $templates_dir . '/' . $def['acl_template'];
                if (is_readable($acl_tmpl_path)) {
                    $acl_content = file_get_contents($acl_tmpl_path);
                } else {
                    $acl_content = "; エラー: 指定されたACLテンプレート({$def['acl_template']})が見つかりません。";
                }
            } else {
                // ACLテンプレートがない場合は、IPアドレス指定があればデフォルト生成
                if (!empty($inputs['ipaddr'])) {
                    $acl_content = "permit=" . $inputs['ipaddr'] . "/32";
                }
            }
            
            $_SESSION['generator_result'] = [
                'content' => $content,
                'acl_content' => $acl_content,
                'trunkname' => $trunkname,
                'target_filename' => $target_filename,
                'acl_filename' => $acl_filename,
                'form_inputs' => $inputs
            ];
            break;

        // --- 保存処理 (変更なし) ---
        case 'savetofile':
            $result = $_SESSION['generator_result'] ?? null;
            if ($result) {
                $submitted_acl = $_POST['acl_content'] ?? '';
                $submitted_acl = str_replace(["\r\n", "\r"], "\n", $submitted_acl);

                // ACLバリデーション
                $acl_lines = explode("\n", $submitted_acl);
                $clean_acl_lines = [];
                $acl_errors = [];

                foreach ($acl_lines as $line) {
                    $line = trim($line);
                    if ($line === '') continue;

                    if (strpos($line, 'permit=') !== 0) {
                        $acl_errors[] = "不正な行: '$line' ('permit='で始まる必要があります)";
                        continue;
                    }

                    $value = substr($line, 7);
                    if (strpos($value, '0.0.0.0') === 0 || strpos($value, '255.255.255.255') === 0) {
                        $acl_errors[] = "禁止IP: '$line'";
                        continue;
                    }

                    $cidr_parts = explode('/', $value);
                    $ip_part = $cidr_parts[0];
                    if (!filter_var($ip_part, FILTER_VALIDATE_IP)) {
                        $acl_errors[] = "不正なIP: '$line'";
                        continue;
                    }
                    if (isset($cidr_parts[1])) {
                        if (!is_numeric($cidr_parts[1]) || $cidr_parts[1] < 0 || $cidr_parts[1] > 128) {
                            $acl_errors[] = "不正なCIDR: '$line'";
                            continue;
                        }
                    }
                    $clean_acl_lines[] = $line;
                }

                if (!empty($acl_errors)) {
                    $flash_message = ['type' => 'error', 'text' => "ACL設定エラー:\n" . implode("\n", $acl_errors)];
                    $result['acl_content'] = $submitted_acl;
                    $_SESSION['generator_result'] = $result;
                    break;
                }

                if (!is_writable($path_trunks) || (!is_writable($path_acl) && !empty($clean_acl_lines))) {
                     $flash_message = ['type' => 'error', 'text' => '保存先ディレクトリに書き込み権限がありません。'];
                     break;
                }

                $success = true;
                // トランク保存
                $trunk_save_path = $path_trunks . '/' . $result['target_filename'];
                $trunk_content = str_replace("\r", '', $result['content']);
                if (file_put_contents($trunk_save_path, $trunk_content) === false) $success = false;

                // ACL保存
                $acl_save_path = $path_acl . '/' . $result['acl_filename'];
                if ($success && !empty($clean_acl_lines)) {
                    $acl_save_content = implode("\n", $clean_acl_lines);
                    if (file_put_contents($acl_save_path, $acl_save_content) === false) $success = false;
                } elseif ($success && empty($clean_acl_lines)) {
                    if (file_exists($acl_save_path)) unlink($acl_save_path);
                }

                if ($success) {
                    $flash_message['text'] = "設定ファイルを保存しました。\nトランク: {$result['target_filename']}" . 
                                             (!empty($clean_acl_lines) ? "\nACL: {$result['acl_filename']}" : "");
                    unset($_SESSION['generator_result']);
                } else {
                    $flash_message = ['type' => 'error', 'text' => 'ファイルの保存に失敗しました。'];
                }
            } else {
                $flash_message = ['type' => 'error', 'text' => 'セッション切れです。'];
            }
            break;
    }

    if ($flash_message) $_SESSION['flash_message'] = $flash_message;
    header('Location: index.php?page=trunk-generator');
    exit;
}

// --- 表示用データ準備 ---
$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

$result = $_SESSION['generator_result'] ?? null;
$form_values = $result['form_inputs'] ?? ($_SESSION['form_inputs'] ?? []);
unset($_SESSION['form_inputs']);

// 既存ファイル一覧
$existing_files = [];
if (!$dir_error && file_exists($path_trunks)) {
    $files = glob($path_trunks . '/pjsip_trunk_*.conf');
    if ($files) {
        foreach ($files as $f) {
            $dt = new DateTime();
            $dt->setTimestamp(filemtime($f));
            $dt->setTimezone(new DateTimeZone('Asia/Tokyo'));
            $existing_files[] = [
                'name' => basename($f),
                'date' => $dt->format('Y-m-d H:i'),
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
        設定ファイルのベースディレクトリ (<code><?= htmlspecialchars($base_dir) ?></code>) の準備が必要です。<br>
        SSH等でサーバにログインし、以下のコマンドを1回だけ実行してください。<br>
        <span style="font-size:0.9em; color:#666;">※これを実行すると、Web管理画面が自動的に必要なサブディレクトリを作成できるようになります。</span>
    </p>
    <pre style="background: #333; color: #fff; padding: 10px; border-radius: 4px; overflow-x: auto;"><?= htmlspecialchars($setup_command) ?></pre>
</div>
<?php endif; ?>

<?php if ($flash_message): ?>
<div class="notice-message" style="white-space: pre-wrap; color: <?= $flash_message['type'] === 'error' ? '#f44336' : '#4CAF50' ?>; margin-bottom: 1.5em; font-weight: bold;">
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
                    <form action="" method="post" onsubmit="return confirm('削除してよろしいですか？\n紐づいているACLファイルがある場合、それも同時に削除されます。');" style="margin:0;">
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
                        <?php foreach ($trunk_definitions as $key => $def): ?>
                            <option value="<?= htmlspecialchars($key) ?>" <?= (($form_values['template_key'] ?? 'hgw') === $key) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($def['label']) ?>
                            </option>
                        <?php endforeach; ?>
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

<?php if ($result): ?>
<h3 style="margin-top: 2em;">ステップ2：生成結果の確認と保存</h3>
<p style="font-size: 0.9em; color: var(--secondary-text-color); margin-top: 0;">
    ABS上で使用するトランク名は「<strong><?= htmlspecialchars($result['trunkname'], ENT_QUOTES, 'UTF-8') ?></strong>」です。
</p>

<form action="" method="post">
    <input type="hidden" name="function" value="savetofile">

    <h4>生成された設定内容 (トランク)</h4>
    <textarea readonly style="height: 250px;"><?= htmlspecialchars($result['content'], ENT_QUOTES, 'UTF-8') ?></textarea>

    <h4>ACL設定内容 (編集可能)</h4>
    <div style="background-color: #fff3cd; color: #856404; padding: 10px; border: 1px solid #ffeeba; border-radius: 4px; margin-bottom: 10px; font-size: 0.9em;">
        <strong><i class="fas fa-exclamation-circle"></i> 注意:</strong> ACL設定は使用する環境に応じて設定を行ってください。
        設定に誤りがあると全ての通話が阻害されますので慎重に行ってください。<br>
        基本的に <code>permit=IPアドレス</code> で許可したいIPアドレスを書くだけです。空欄にした場合、ACLファイルは生成されません。
    </div>
    
    <textarea name="acl_content" style="height: 150px;"><?= htmlspecialchars($result['acl_content'], ENT_QUOTES, 'UTF-8') ?></textarea>
    <p style="font-size: 0.85em; color: var(--secondary-text-color); margin-top:5px;">
        許可済みフォーマット: <code>permit=192.168.1.1</code> または <code>permit=192.168.1.0/24</code><br>
        ※ <code>permit = ...</code> のようにスペースを入れることはできません。<br>
        ※ <code>0.0.0.0</code> および <code>255.255.255.255</code> は使用できません。
    </p>

    <div style="margin-top: 2em; padding: 15px; border: 1px solid var(--border-color); background-color: var(--surface-color); border-radius: 4px;">
        <label style="display: block; margin-bottom: 10px; font-weight: bold;">
            <input type="checkbox" required> 内容を確認しました。以下のファイルとして保存します。
        </label>
        <ul style="font-family: monospace; font-size: 0.9em; color: var(--text-color);">
            <li><?= htmlspecialchars($path_trunks . '/' . $result['target_filename'], ENT_QUOTES, 'UTF-8') ?></li>
            <li><?= htmlspecialchars($path_acl . '/' . $result['acl_filename'], ENT_QUOTES, 'UTF-8') ?> (空の場合は作成されません)</li>
        </ul>
        <p style="font-size: 0.9em; color: #f44336; margin-top: 5px;">注意: 同じ名前のファイルが存在すると上書きされます。</p>
        
        <button type="submit" class="btn btn-primary">保存する</button>
    </div>
</form>
<?php endif; ?>
