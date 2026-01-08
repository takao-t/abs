<?php
if (!defined('ABS_PANEL_INCLUDED')) {
    die("Direct access is not permitted.");
}

global $ami;

// POST時処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $flash_message = ['type' => 'success', 'text' => '設定を保存しました。'];
    $function = $_POST['function'] ?? '';

    switch ($function) {
        case 'updatepass':
            $n_username = $_SESSION['username'] ?? '';
            $n_opasswd = $_POST['opasswd'] ?? '';
            $n_npasswd1 = $_POST['npasswd1'] ?? '';
            $n_npasswd2 = $_POST['npasswd2'] ?? '';
            $is_valid = true;

            if (empty($n_opasswd) || empty($n_npasswd1)) {
                $flash_message = ['type' => 'error', 'text' => 'すべてのパスワード欄を入力してください。'];
                $is_valid = false;
            } elseif ($n_npasswd1 !== $n_npasswd2) {
                $flash_message = ['type' => 'error', 'text' => '新しいパスワードが一致しません。'];
                $is_valid = false;
            }
            
            if ($is_valid) {
                $tmp_hash = $ami->getDbItem('ABS/PANELUSER', $n_username);
                if($tmp_hash !== '' && password_verify($n_opasswd, $tmp_hash)){
                    $hashed_pass = password_hash($n_npasswd1, PASSWORD_DEFAULT);
                    $ami->putDbItem('ABS/PANELUSER', $n_username, $hashed_pass);
                    $flash_message['text'] = "ユーザ {$n_username} のパスワードを更新しました。";
                } else {
                    $flash_message = ['type' => 'error', 'text' => '現在のパスワードが正しくありません。'];
                }
            }
            break;
        
        case 'useradd':
            $n_username = trim($_POST['username'] ?? '');
            $n_npasswd1 = $_POST['npasswd1'] ?? '';
            $n_npasswd2 = $_POST['npasswd2'] ?? '';
            $is_valid = true;

            if (empty($n_username) || empty($n_npasswd1)) {
                $flash_message = ['type' => 'error', 'text' => 'ユーザ名とパスワードを入力してください。'];
                $is_valid = false;
            } elseif ($n_npasswd1 !== $n_npasswd2) {
                $flash_message = ['type' => 'error', 'text' => 'パスワードが一致しません。'];
                $is_valid = false;
            } elseif ($ami->getDbItem('ABS/PANELUSER', $n_username) !== '') {
                $flash_message = ['type' => 'error', 'text' => 'そのユーザ名は既に使用されています。'];
                $is_valid = false;
            }

            if ($is_valid) {
                $hashed_pass = password_hash($n_npasswd1, PASSWORD_DEFAULT);
                $ami->putDbItem('ABS/PANELUSER', $n_username, $hashed_pass);
                $flash_message['text'] = "ユーザ {$n_username} を追加しました。";
            }
            break;

        case 'acl_add':
            $new_entry = trim($_POST['acl_entry'] ?? '');
            if (!empty($new_entry)) {
                // validation (簡易的)
                if (preg_match('/^(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})(\/\d{1,2})?$/', $new_entry)) {
                    $current_acl_string = $ami->getDbItem('ABS/PANEL', 'ACL') ?? '';
                    $acl_list = !empty($current_acl_string) ? explode(',', $current_acl_string) : [];
                    
                    if (!in_array($new_entry, $acl_list)) {
                        $acl_list[] = $new_entry;
                        $ami->putDbItem('ABS/PANEL', 'ACL', implode(',', $acl_list));
                        $flash_message['text'] = 'ACLエントリを追加しました。';
                    } else {
                        $flash_message = ['type' => 'error', 'text' => 'そのエントリは既に追加されています。'];
                    }
                } else {
                    $flash_message = ['type' => 'error', 'text' => 'IPアドレスまたはCIDRの形式が正しくありません。'];
                }
            } else {
                $flash_message = ['type' => 'error', 'text' => '追加するエントリを入力してください。'];
            }
            break;

        case 'acl_delete':
            $entry_to_delete = $_POST['acl_entry_to_delete'] ?? '';
            if (!empty($entry_to_delete)) {
                $current_acl_string = $ami->getDbItem('ABS/PANEL', 'ACL') ?? '';
                $acl_list = !empty($current_acl_string) ? explode(',', $current_acl_string) : [];
                
                // 削除対象のエントリを除外した新しい配列を作成
                $new_acl_list = array_filter($acl_list, function($entry) use ($entry_to_delete) {
                    return $entry !== $entry_to_delete;
                });

                $ami->putDbItem('ABS/PANEL', 'ACL', implode(',', $new_acl_list));
                $flash_message['text'] = 'ACLエントリを削除しました。';
            }
            break;

        case 'licset': //ライセンスキー設定
            $ami->putDbItem('ABS', 'LIC', $_POST['lickey'] ?? '');
            break;
            
        case 'endisedit':
            $value = $_POST['editor_enabled'] ?? 'NO';
            $ami->putDbItem('ABS/PANEL', 'PGRESTRICTED', $value);
            $flash_message['text'] = 'ファイル編集機能の設定を保存しました。';
            break;

        case 'set_session_timeout':
            $timeout_minutes = filter_input(INPUT_POST, 'session_timeout', FILTER_VALIDATE_INT);

            if ($timeout_minutes !== false && $timeout_minutes >= 5) {
                $ami->putDbItem('ABS/PANEL', 'SESSION_TIMEOUT', $timeout_minutes);
                $flash_message['text'] = "セッションタイムアウトを{$timeout_minutes}分に設定しました。";
            } else {
                $flash_message = ['type' => 'error', 'text' => '無効な値です。5分以上の半角数字を入力してください。'];
            }
            break;
    }

    if ($flash_message) {
        $_SESSION['flash_message'] = $flash_message;
    }
    header('Location: index.php?page=system-config-page');
    exit;
}

// GET時処理
$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

//ネットワーク等(ホスト)情報表示のためのデータ取得
function get_network_interfaces(): array {
    $interfaces = [];
    // `LC_ALL=C` を指定して、環境に依存しない英語出力を保証する
    exec('LC_ALL=C ip addr show', $cmd_output);

    $current_interface = null;
    foreach ($cmd_output as $line) {
        if (preg_match('/^\d+:\s+([^:@]+)/', $line, $matches)) {
            $interface_name = trim($matches[1]);
            // 新しいインターフェースが見つかったら、それを現在の作業対象にする
            $interfaces[$interface_name] = [
                'name' => $interface_name,
                'ips' => [],
                'mac' => 'N/A'
            ];
            $current_interface = &$interfaces[$interface_name];
        }

        if ($current_interface) {
            if (preg_match('/link\/(ether|loopback)\s+([0-9a-f:]+)/', $line, $matches)) {
                if($matches[2] === '00:00:00:00:00:00'){
                    $current_interface['mac'] = '---';
                }
                else {
                    $current_interface['mac'] = $matches[2];
                }
            }
            if (preg_match('/inet\s+([0-9\.]+)\/\d+/', $line, $matches)) {
                $current_interface['ips'][] = $matches[1];
            }
        }
    }
    return $interfaces;
}

$network_interfaces = get_network_interfaces();
$system_info = php_uname();
$uptime_info = exec('uptime');
// AMI経由でAsteriskバージョン取得
$asterisk_info_ar[] = explode("\n", $ami->execCliCommand('core show version'),);
$asterisk_info = str_replace('Output: ', '', $asterisk_info_ar[0][2] ?? '');

// ユーザーリストの読み込み
date_default_timezone_set('Asia/Tokyo');
$user_list = [];
$user_dbent_raw = $ami->getFamilyDB('ABS/PANELUSER');

if (is_array($user_dbent_raw)) {
    foreach($user_dbent_raw as $line){
        if (strpos($line, ':') !== false) {
            $username = trim(explode(':', $line, 2)[0]);
            if (strpos($username, '/') === false) {
                $user_activity_time = $mycache->get('ABS/PANELUSER/' . $username, 'last_activity');
                $act_val = intval($user_activity_time);
                if($act_val === 0){
                    $activity_dt = '-';
                }
                else {
                    $activity_dt = date('Y/m/d H:i:s', $act_val);
                }
                $user_list[$username] = [
                    'name' => $username,
                    'lastact' => $activity_dt,
                ];
            }
        }
    }
    sort($user_list);
}

$current_acl_string = $ami->getDbItem('ABS/PANEL', 'ACL') ?? '';
$acl_list = !empty($current_acl_string) ? explode(',', $current_acl_string) : [];

// 各種設定値の取得
$lickey_setting = $ami->getDbItem('ABS', 'LIC') ?? '';
$file_editor_setting = $ami->getDbItem('ABS/PANEL', 'PGRESTRICTED') ?? 'NO';
$current_timeout = $ami->getDbItem('ABS/PANEL', 'SESSION_TIMEOUT') ?? 30;

?>
<h2>システム設定</h2>

<?php if ($flash_message): ?>
<div class="notice-message" style="color: <?= $flash_message['type'] === 'error' ? '#f44336' : '#4CAF50' ?>; border: 1px solid currentColor; padding: 1em; margin-bottom: 1.5em; border-radius: 4px;">
    <?= htmlspecialchars($flash_message['text'], ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>

<h3>ネットワークインターフェース一覧 🌐</h3>
<div class="table-container">
    <table class="absp-table">
        <thead>
            <tr>
                <th>インターフェース名</th>
                <th>IPアドレス</th>
                <th>MACアドレス</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($network_interfaces)): ?>
                <tr><td colspan="3" style="text-align: center; padding: 20px;">ネットワークインターフェース情報を取得できませんでした。</td></tr>
            <?php else: ?>
                <?php foreach($network_interfaces as $interface): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($interface['name'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <td><?= empty($interface['ips']) ? 'N/A' : htmlspecialchars(implode(', ', $interface['ips']), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($interface['mac'], ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<h3>システム情報</h3>
<div class="table-container">
    <table class="absp-table">
         <thead>
            <tr>
                <th style="width: 150px;">項目</th>
                <th>情報</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>システム</td>
                <td><?= htmlspecialchars($system_info, ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
            <tr>
                <td>Uptime</td>
                <td><?= htmlspecialchars($uptime_info, ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
            <tr>
                <td>Asterisk</td>
                <td><?= htmlspecialchars($asterisk_info, ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
        </tbody>
    </table>
</div>

<h3>ユーザ管理</h3>
<h4>ユーザ一覧</h4>
<?php if(empty($user_list)): ?>
    <p style="color: var(--secondary-text-color);">管理ユーザが登録されていません。</p>
<?php else: ?>
    <div class="table-container">
        <table class="absp-table">
            <thead>
                <tr>
                    <th style="width: 150px;">ユーザ名</th>
                    <th>最終アクティビティ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($user_list as $user_entry): ?>
                  <tr>
                    <td><?= htmlspecialchars($user_entry['name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($user_entry['lastact'], ENT_QUOTES, 'UTF-8') ?></td>
                  </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<h4>新規ユーザ追加</h4>
<form action="index.php?page=system-config-page" method="post">
    <input type="hidden" name="function" value="useradd">
    <div class="form-inline-group">
        <label for="add_username">ユーザ名:</label>
        <input type="text" id="add_username" name="username" class="input-short2">
        <label for="add_npasswd1">パスワード:</label>
        <input type="password" id="add_npasswd1" name="npasswd1" class="input-short2">
        <label for="add_npasswd2">パスワード(確認):</label>
        <input type="password" id="add_npasswd2" name="npasswd2" class="input-short2">
        <button type="submit" class="btn">追加</button>
    </div>
</form>

<h4>パスワード変更</h4>
<p style="font-size: 0.9em; color: var(--secondary-text-color); margin-top: 0;">
    現在ログイン中のユーザ (<strong><?= htmlspecialchars($_SESSION['username'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>) のパスワードを変更します。
</p>
<form action="index.php?page=system-config-page" method="post">
    <input type="hidden" name="function" value="updatepass">
    <div class="form-inline-group">
        <label for="opasswd">現在のパスワード:</label>
        <input type="password" id="opasswd" name="opasswd" class="input-short2">
        <label for="update_npasswd1">新しいパスワード:</label>
        <input type="password" id="update_npasswd1" name="npasswd1" class="input-short2">
        <label for="update_npasswd2">新しいパスワード(確認):</label>
        <input type="password" id="update_npasswd2" name="npasswd2" class="input-short2">
        <button type="submit" class="btn">変更</button>
    </div>
</form>

<h3>ライセンスキー</h3>
<p style="font-size: 0.9em; color: var(--secondary-text-color); margin-top: 0;">
    割り当てられたライセンスキーを設定してください。
</p>
<form action="index.php?page=system-config-page" method="post" class="form-inline-group">
    <input type="hidden" name="function" value="licset">
    <label for="lickey">ライセンスキー:</label>
    <input type="text" id="lickey" name="lickey" style="width: 30em;" value="<?= htmlspecialchars($lickey_setting, ENT_QUOTES, 'UTF-8') ?>">
    <button type="submit" class="btn">設定</button>
</form>

<h3>セキュリティ設定</h3>
<h4>セッションタイムアウト</h4>
<form action="index.php?page=system-config-page" method="post">
    <input type="hidden" name="function" value="set_session_timeout">
    <div class="form-inline-group">
        <label for="session_timeout">タイムアウト (分):</label>
        <input type="number" id="session_timeout" name="session_timeout" value="<?= htmlspecialchars($current_timeout, ENT_QUOTES, 'UTF-8') ?>" class="input-short2" min="5" required>
        <button type="submit" class="btn">設定保存</button>
    </div>
    <p style="font-size: 0.9em; color: var(--secondary-text-color); margin-top: 0.5em;">
        最後の操作からここで設定した時間が経過すると、自動的にログアウトします。(最低5分)
    </p>
</form>

<h4>ACL設定 (アクセス制御)</h4>
<p style="font-size: 0.9em; color: var(--secondary-text-color); margin-top: 0;">
    このコントロールパネルにアクセスできるIPアドレスを制限します。<br>
    <strong style="color: #f44336;">注意: 何も設定されていない場合は、すべてのIPアドレスからのアクセスが許可されます。一度設定すると、リスト内のIPからのみアクセス可能になります。現在のIPアドレスを誤って締め出さないように注意してください。</strong><br> あなたの現在のIPアドレス: <strong><?= htmlspecialchars($_SERVER['REMOTE_ADDR'], ENT_QUOTES, 'UTF-8') ?></strong><br>
</p>
<h5>許可リスト</h5>
<?php if (empty($acl_list)): ?>
    <p style="color: var(--secondary-text-color);">ACLは設定されていません (全許可)。</p>
<?php else: ?>
    <div class="table-container">
        <table class="absp-table" style="max-width: 500px;">
            <tbody>
                <?php foreach($acl_list as $entry): ?>
                <tr>
                    <td><?= htmlspecialchars($entry, ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="width: 80px; text-align: right;">
                        <form action="index.php?page=system-config-page" method="post" style="margin:0;">
                            <input type="hidden" name="function" value="acl_delete">
                            <input type="hidden" name="acl_entry_to_delete" value="<?= htmlspecialchars($entry, ENT_QUOTES, 'UTF-8') ?>">
                            <button type="submit" class="btn btn-row">削除</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<h5>ACLエントリ追加</h5>
<form action="index.php?page=system-config-page" method="post" class="form-inline-group">
    <input type="hidden" name="function" value="acl_add">
    <input type="text" name="acl_entry" class="input-middle" placeholder="例: 192.168.1.100 or 10.0.0.0/8">
    <button type="submit" class="btn">追加</button>
</form>

<h3>ファイル編集機能</h3>
<p style="font-size: 0.9em; color: var(--secondary-text-color); margin-top: 0;">
    <strong style="color: #f44336;">セキュリティリスク:</strong> この機能を制限なしにすると、Web UIからサーバー上のファイルを直接編集できるようになります。
    セキュリティ上の理由から、通常は「制限する」を推奨します。
</p>
<form action="index.php?page=system-config-page" method="post" class="form-inline-group">
    <input type="hidden" name="function" value="endisedit">
    <label for="editor_enabled">ファイル編集機能制限:</label>
    <select id="editor_enabled" name="editor_enabled">
        <option value="NO" <?= ($file_editor_setting == 'NO') ? 'selected' : '' ?>>制限なし (非推奨)</option>
        <option value="YES"  <?= ($file_editor_setting == 'YES') ? 'selected' : '' ?>>制限する (推奨)</option>
    </select>
    <button type="submit" class="btn">設定</button>
</form>
