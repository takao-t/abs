<?php
if (!defined('ABS_PANEL_INCLUDED')) {
    die("Direct access is not permitted.");
}

global $ami;

// POST時処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['function'])) {
    $function = $_POST['function'];

    switch ($function) {
        case 'newadd':
            // --- 新規追加 ---
            $p_localexten = trim($_POST['localexten'] ?? '');
            $p_iopnum     = trim($_POST['iopnum'] ?? '');
            $p_iopexten   = trim($_POST['iopexten'] ?? '');
            $is_valid     = true;
            $error_msg    = '';

            if (!ctype_digit($p_localexten) || empty($p_localexten)) {
                $error_msg = '内線番号は数字で入力してください。';
                $is_valid = false;
            } elseif (!ctype_digit($p_iopnum)) {
                $error_msg = '拠点番号が不正です。';
                $is_valid = false;
            } elseif (!ctype_digit($p_iopexten) || empty($p_iopexten)) {
                $error_msg = '相手内線番号は数字で入力してください。';
                $is_valid = false;
            } else {
                // 内線番号の重複チェック
                $existing_endpoint = $ami->getDbItem('ABS/EXT', $p_localexten);
                if ($existing_endpoint !== '') {
                    $error_msg = "内線番号[{$p_localexten}]は既に使用されています。";
                    $is_valid = false;
                }
            }
            
            if ($is_valid) {
                $remote_exten = 'R' . $p_iopnum . $p_iopexten;
                $ami->putDbItem('ABS/EXT', $p_localexten, $remote_exten);
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => "リモート内線[{$p_localexten}]を登録しました。"];
            } else {
                $_SESSION['flash_message'] = ['type' => 'error', 'text' => $error_msg];
            }
            break;

        case 'entdel':
            // --- 一括削除 ---
            $deleted_count = 0;
            if (isset($_POST['delete_exts']) && is_array($_POST['delete_exts'])) {
                foreach ($_POST['delete_exts'] as $exten_to_delete) {
                    $ami->delDbItem('ABS/EXT', $exten_to_delete);
                    $deleted_count++;
                }
            }
            if ($deleted_count > 0) {
                 $_SESSION['flash_message'] = ['type' => 'success', 'text' => "{$deleted_count}件のリモート内線を削除しました。"];
            } else {
                 $_SESSION['flash_message'] = ['type' => 'error', 'text' => '削除対象が選択されていません。'];
            }
            break;
    }

    // POST処理後はリダイレクト
    header('Location: index.php?page=remote-exten-page');
    exit;
}

// GET時処理
$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

$iop_entries = $ami->getDbFamily('ABS/IOP');
$here_iop_num = $ami->getDbItem('ABS/IOP', 'HERE');
$iop_options = [];
foreach ($iop_entries as $line) {
    if (strpos($line, "/NAME") === false) continue;
    
    list($iopnum, $line2) = explode("/", $line, 2);
    list(, $iopname) = explode(":", $line2, 2);
    
    $iopnum = trim($iopnum);
    if ($iopnum == $here_iop_num) continue; // 自局は除外

    $iop_options[$iopnum] = trim($iopname);
}
ksort($iop_options);

$all_extens = $ami->getDbFamily('ABS/EXT');
$iop_digits = $ami->getDbItem('ABS/IOP', 'DIGITS') ?? 2; // デフォルトは2桁
$remote_extens = [];

foreach ($all_extens as $line) {
    if (strpos($line, "/") !== false) continue; // サブキーを持つエントリは除外
    
    list($exten, $endpoint) = explode(":", $line, 2);
    $exten = trim($exten);
    $endpoint = trim($endpoint);

    // 'R'で始まるものだけをリモート内線として抽出
    if (strpos($endpoint, "R") === 0) {
        $remote_extens[] = [
            'exten' => $exten,
            'iop_site' => substr($endpoint, 1, $iop_digits),
            'iop_exten' => substr($endpoint, 1 + $iop_digits)
        ];
    }
}

?>
<h2>リモート内線設定</h2>
<p style="color: var(--secondary-text-color);">他の拠点の内線を、ローカルの内線番号で呼び出すための設定です。</p>

<?php if ($flash_message): ?>
<div class="notice-message" style="color: <?= $flash_message['type'] === 'error' ? '#f44336' : '#4CAF50' ?>; border: 1px solid currentColor; padding: 1em; margin-bottom: 1.5em; border-radius: 4px;">
    <?= htmlspecialchars($flash_message['text'], ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>

<h3 style="margin-top: 2em;">新規追加</h3>
<form action="index.php?page=remote-exten-page" method="post">
    <input type="hidden" name="function" value="newadd">
    <table class="absp-table" style="max-width: 800px;">
        <thead>
            <tr>
                <th>内線番号</th>
                <th>相手拠点</th>
                <th>相手内線番号</th>
                <th style="width: 80px;"></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><input type="text" name="localexten" class="input-short2" required></td>
                <td>
                    <select name="iopnum" class="input-middle">
                        <?php foreach ($iop_options as $num => $name): ?>
                        <option value="<?= htmlspecialchars($num, ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($num, ENT_QUOTES, 'UTF-8') ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><input type="text" name="iopexten" class="input-short2" required></td>
                <td><button type="submit" class="btn">追加</button></td>
            </tr>
        </tbody>
    </table>
</form>


<h3 style="margin-top: 2em;">登録済リモート内線一覧</h3>
<form action="index.php?page=remote-exten-page" method="post" onsubmit="return confirm('選択した内線を削除しますか？');">
    <input type="hidden" name="function" value="entdel">
    <div class="table-container">
        <table class="absp-table">
            <thead>
                <tr>
                    <th style="width: 50px;"><input type="checkbox" onchange="document.querySelectorAll('.delete-check').forEach(c => c.checked = this.checked)"></th>
                    <th>内線番号</th>
                    <th>相手拠点番号</th>
                    <th>相手内線番号</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($remote_extens)): ?>
                    <tr><td colspan="4" style="text-align: center; color: var(--secondary-text-color);">リモート内線は登録されていません。</td></tr>
                <?php else: ?>
                    <?php foreach($remote_extens as $ext): ?>
                    <tr>
                        <td style="text-align: center;">
                            <input type="checkbox" class="delete-check" name="delete_exts[]" value="<?= htmlspecialchars($ext['exten'], ENT_QUOTES, 'UTF-8') ?>">
                        </td>
                        <td><?= htmlspecialchars($ext['exten'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($ext['iop_site'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($ext['iop_exten'], ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if (!empty($remote_extens)): ?>
        <button type="submit" class="btn" style="margin-top: 1em;">選択した項目を削除</button>
    <?php endif; ?>
</form>
