<?php
if (!defined('ABS_PANEL_INCLUDED')) {
    die("Direct access is not permitted.");
}

// POST時処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $flash_message = ['type' => 'success', 'text' => '設定を保存しました。'];
    $function = $_POST['function'] ?? '';

    switch ($function) {
        // 新規追加
        case 'newadd':
            $p_qnum = trim($_POST['qnum'] ?? '');
            $p_pnum = trim($_POST['pnum'] ?? '');
            $p_pname = trim($_POST['pname'] ?? '');
            $is_valid = true;

            if (strlen($p_qnum) !== 3 || !ctype_digit($p_qnum) || (int)$p_qnum > 299) {
                $flash_message = ['type' => 'error', 'text' => '短縮番号は3桁の数字（000～299）で指定してください。'];
                $is_valid = false;
            } elseif (empty($p_pnum)) {
                $flash_message = ['type' => 'error', 'text' => '電話番号は必須です。'];
                $is_valid = false;
            } elseif (AbspFunctions\get_db_item('ABS/quickdial', $p_qnum) !== "") {
                // 重複チェック
                $flash_message = ['type' => 'error', 'text' => "短縮番号 {$p_qnum} は既に使用されています。変更する場合は一覧から「編集」ボタンを押してください。"];
                $is_valid = false;
            }

            if ($is_valid) {
                AbspFunctions\put_db_item('ABS/quickdial', $p_qnum, $p_pnum);
                AbspFunctions\put_db_item('cidname', $p_pnum, $p_pname);
                $flash_message['text'] = "短縮番号 {$p_qnum} を追加しました。";
            } else {
                $_SESSION['form_data'] = $_POST;
            }
            break;
            
        // 更新
        case 'update':
            $p_qnum = trim($_POST['qnum'] ?? '');
            $p_pnum = trim($_POST['pnum'] ?? '');
            $p_pname = trim($_POST['pname'] ?? '');
            
            if (empty($p_pnum)) {
                $flash_message = ['type' => 'error', 'text' => '電話番号は必須です。'];
                $_SESSION['form_data'] = $_POST;
            } else {
                AbspFunctions\put_db_item('ABS/quickdial', $p_qnum, $p_pnum);
                AbspFunctions\put_db_item('cidname', $p_pnum, $p_pname);
                $flash_message['text'] = "短縮番号 {$p_qnum} を更新しました。";
            }
            break;

        // 削除
        case 'entdel':
            if (isset($_POST['delcb']) && $_POST['delcb'] === 'yes') {
                $p_d_qnum = $_POST['d_qnum'];
                AbspFunctions\del_db_item('ABS/quickdial', $p_d_qnum);
                $flash_message['text'] = "短縮番号 {$p_d_qnum} を削除しました。";
            } else {
                $flash_message = ['type' => 'error', 'text' => '削除するにはチェックボックスをオンにしてください。'];
            }
            break;

        // 編集モードへの移行
        case 'entedi':
            $_SESSION['edit_qd_data'] = [
                'qnum' => $_POST['e_qnum'],
                'pnum' => $_POST['e_pnum'],
                'pname' => $_POST['e_pname'],
            ];
            $flash_message = null;
            break;

        // プレフィックス設定
        case 'ogpset':
            AbspFunctions\put_db_item('ABS/quickdial', 'PFX', $_POST['qdogp'] ?? '0');
            $flash_message['text'] = 'クイックダイヤル時のプレフィクスを設定しました。';
            break;
    }

    if ($flash_message) {
        $_SESSION['flash_message'] = $flash_message;
    }

    header('Location: index.php?page=qd-config-page');
    exit;
}

// GET時処理
$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

// プレフィックス設定値を取得
$qdpfx_setting = AbspFunctions\get_db_item('ABS/quickdial', 'PFX') ?: '0';

// フォームの初期値と設定
$form_defaults = ['qnum' => '', 'pnum' => '', 'pname' => ''];
$form_values = $_SESSION['form_data'] ?? $form_defaults;
unset($_SESSION['form_data']);

$is_edit_mode = false;
if (isset($_SESSION['edit_qd_data'])) {
    $is_edit_mode = true;
    $form_values = $_SESSION['edit_qd_data'];
    unset($_SESSION['edit_qd_data']);
}

// 登録済み一覧の取得
$qd_list = [];
$db_entries = AbspFunctions\get_db_family('ABS/quickdial');
if (is_array($db_entries)) {
    foreach ($db_entries as $line) {
        list($qnum, $pnum) = explode(' : ', $line, 2);
        $qnum = trim($qnum);
        if (ctype_digit($qnum)) { // PFXキーは除外
            $pnum = trim($pnum);
            $qd_list[] = [
                'qnum' => $qnum,
                'pnum' => $pnum,
                'pname' => AbspFunctions\get_db_item('cidname', $pnum) ?: '',
            ];
        }
    }
}
// 短縮番号でソート
sort($qd_list);

?>
<h2>短縮(クイック)ダイヤル管理</h2>

<?php if ($flash_message): ?>
<div class="notice-message" style="color: <?= $flash_message['type'] === 'error' ? '#f44336' : '#4CAF50' ?>; margin-bottom: 1.5em; font-weight: bold;">
    <?= htmlspecialchars($flash_message['text'], ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>

<h3>プレフィクス設定</h3>
<form action="" method="post" class="form-inline-group">
    <input type="hidden" name="function" value="ogpset">
    <label for="qdogp">クイックダイヤル発信時のプレフィクス:</label>
    <select name="qdogp" id="qdogp" class="input-xmiddle">
        <option value="0" <?= ($qdpfx_setting == '0') ? 'selected' : '' ?>>未設定</option>
        <option value="1" <?= ($qdpfx_setting == '1') ? 'selected' : '' ?>>OGP1</option>
        <option value="2" <?= ($qdpfx_setting == '2') ? 'selected' : '' ?>>OGP2</option>
    </select>
    <button type="submit" class="btn">設定</button>
</form>

<h3><?= $is_edit_mode ? '短縮ダイヤル編集' : '短縮ダイヤル追加' ?></h3>
<p style="font-size: 0.9em; color: var(--secondary-text-color); margin-top: 0;">
    短縮番号は3桁（000～299）で指定してください。名前は省略可能です。
</p>
<form action="" method="POST">
    <input type="hidden" name="function" value="<?= $is_edit_mode ? 'update' : 'newadd' ?>">
    <div class="form-inline-group">
        <label for="qnum">短縮番号:</label>
        <input type="text" id="qnum" name="qnum" value="<?= htmlspecialchars($form_values['qnum'], ENT_QUOTES, 'UTF-8') ?>" class="input-short" maxlength="3" <?= $is_edit_mode ? 'readonly style="background-color: #444;"' : '' ?>>

        <label for="pnum">電話番号:</label>
        <input type="text" id="pnum" name="pnum" value="<?= htmlspecialchars($form_values['pnum'], ENT_QUOTES, 'UTF-8') ?>" class="input-middle2">
        
        <label for="pname">名前:</label>
        <input type="text" id="pname" name="pname" value="<?= htmlspecialchars($form_values['pname'], ENT_QUOTES, 'UTF-8') ?>" class="input-middle2">

        <button type="submit" class="btn btn-primary"><?= $is_edit_mode ? '更新' : '追加' ?></button>
        <?php if ($is_edit_mode): ?>
            <a href="index.php?page=qd-config-page" class="btn">キャンセル</a>
        <?php endif; ?>
    </div>
</form>

<h3>登録済み一覧</h3>
<div class="table-container">
    <table class="absp-table">
        <thead>
            <tr>
                <th>短縮番号</th>
                <th>電話番号</th>
                <th>名前</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($qd_list)): ?>
                <tr>
                    <td colspan="4" style="text-align: center; padding: 20px;">登録されている短縮ダイヤルはありません。</td>
                </tr>
            <?php else: ?>
                <?php foreach ($qd_list as $qd_entry): ?>
                <tr>
                    <td><?= htmlspecialchars($qd_entry['qnum'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($qd_entry['pnum'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($qd_entry['pname'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <div class="form-inline-group" style="gap: 0.5em;">
                            <form action="" method="POST" style="margin: 0;">
                                <input type="hidden" name="function" value="entedi">
                                <input type="hidden" name="e_qnum" value="<?= htmlspecialchars($qd_entry['qnum'], ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="e_pnum" value="<?= htmlspecialchars($qd_entry['pnum'], ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="e_pname" value="<?= htmlspecialchars($qd_entry['pname'], ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" class="btn btn-row">編集</button>
                            </form>
                            <form action="" method="POST" style="margin: 0;" onsubmit="if(!this.delcb.checked) { alert('削除するにはチェックボックスをオンにしてください。'); return false; } return confirm('短縮番号 <?= htmlspecialchars($qd_entry['qnum'], ENT_QUOTES, 'UTF-8') ?> を本当に削除しますか？');">
                                <input type="hidden" name="function" value="entdel">
                                <input type="hidden" name="d_qnum" value="<?= htmlspecialchars($qd_entry['qnum'], ENT_QUOTES, 'UTF-8') ?>">
                                <input type="checkbox" name="delcb" value="yes" title="削除するにはチェックを入れてください">
                                <button type="submit" class="btn btn-row">削除</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
