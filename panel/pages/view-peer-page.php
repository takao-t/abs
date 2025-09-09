<?php
if (!defined('ABS_PANEL_INCLUDED')) {
    die("Direct access is not permitted.");
}

// GET時処理
$tech = AbspFunctions\get_db_item('ABS', 'EXTTECH') ?: 'PJSIP';
$target_file = ($tech == 'SIP') ? (ASTDIR . '/sip_phones.debug') : (ASTDIR . '/pjsip_wizard.conf');
$config_data = null;
$file_error = null;

if (file_exists($target_file)) {
    $file_content = file_get_contents($target_file);
    // INIパーサーが解釈しやすいように不要な文字列を削除
    $cleaned_content = preg_replace('/\((.*?)\)/', '', $file_content);
    $config_data = parse_ini_string($cleaned_content, true);
} else {
    $file_error = "設定ファイル ({$target_file}) が見つかりません。";
}

// 通常端末リストの作成
$normal_peers = [];
if ($config_data) {
    for ($i = 1; $i <= $max_sip_phones; $i++) {
        $peer_name = "phone{$i}";
        $password_key = ($tech == 'SIP') ? 'secret' : 'inbound_auth/password';
        $normal_peers[] = [
            'name' => $peer_name,
            'password' => $config_data[$peer_name][$password_key] ?? '取得不可'
        ];
    }
}

// フリーアドレス端末リストの作成
$fap_peers = [];
if ($config_data) {
    for ($i = 1; $i <= $max_fap_phones; $i++) {
        $peer_name = 'FAP' . sprintf("%03d", $i);
        $password_key = ($tech == 'SIP') ? 'secret' : 'inbound_auth/password';
        $fap_peers[] = [
            'name' => $peer_name,
            'password' => $config_data[$peer_name][$password_key] ?? '取得不可'
        ];
    }
}

?>
<h2>端末(エンドポイント)情報確認</h2>

<div class="notice-message" style="color: var(--secondary-text-color); border: 1px solid var(--border-color); padding: 1em; margin-bottom: 1.5em; border-radius: 4px;">
    <strong>注意:</strong><br>
    このページの情報は、サーバー上の設定ファイルから直接読み込んでいます。ここからパスワード等の変更は行えません。<br>
    設定ファイルを直接編集した場合は、Asteriskの再起動が必要です。<br>
    <strong style="color: #f44336;">このページの情報の取り扱いには十分に注意してください。</strong>
</div>

<?php if ($file_error): ?>
    <p style="color: #f44336; font-weight: bold;"><?= htmlspecialchars($file_error, ENT_QUOTES, 'UTF-8') ?></p>
<?php else: ?>
    <h3>通常端末</h3>
    <p>
       <strong>注意:</strong><br>
       phone1～phone32は通常の電話機(ハード/ソフトフォン)、phone<?= htmlspecialchars(trim($brphone_min), ENT_QUOTES, 'UTF-8') ?>～phone<?= htmlspecialchars(trim($brphone_max), ENT_QUOTES, 'UTF-8') ?>はブラウザフォン用です。トランスポートが異なるので注意してください。
    </p>
    <div class="table-container">
        <table class="absp-table">
            <thead>
                <tr><th style="width: 200px;">端末(エンドポイント)名</th><th>パスワード</th></tr>
            </thead>
            <tbody>
                <?php foreach($normal_peers as $peer): ?>
                <tr>
                    <td><?= htmlspecialchars($peer['name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($peer['password'], ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <h3>フリーアドレス端末</h3>
    <div class="table-container">
        <table class="absp-table">
            <thead>
                <tr><th style="width: 200px;">端末(エンドポイント)名</th><th>パスワード</th></tr>
            </thead>
            <tbody>
                <?php foreach($fap_peers as $peer): ?>
                <tr>
                    <td><?= htmlspecialchars(trim($peer['name']), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars(trim($peer['password']), ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<a href="index.php?page=system-config-page" class="btn" style="margin-top: 1em;">戻る</a>
