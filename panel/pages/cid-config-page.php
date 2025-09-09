<?php
if (!defined('ABS_PANEL_INCLUDED')) {
    die("Direct access is not permitted.");
}

// POST時処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $flash_message = ['type' => 'success', 'text' => '設定を保存しました。'];
    $function = $_POST['function'] ?? '';

    switch ($function) {
        // 新規追加 or 更新
        case 'newadd':
        case 'update':
            $p_cidnumber = trim($_POST['cidnumber'] ?? '');
            $p_cidname = trim($_POST['cidname'] ?? '');
            $is_valid = true;

            if (empty($p_cidnumber)) {
                $flash_message = ['type' => 'error', 'text' => '番号は必須です。'];
                $is_valid = false;
            } elseif (!ctype_digit($p_cidnumber)) {
                $flash_message = ['type' => 'error', 'text' => '番号は数字で入力してください。'];
                $is_valid = false;
            }

            if ($is_valid) {
                AbspFunctions\put_db_item('cidname', $p_cidnumber, $p_cidname);
                $flash_message['text'] = ($function == 'newadd') ? "番号 {$p_cidnumber} を追加しました。" : "番号 {$p_cidnumber} を更新しました。";
            } else {
                $_SESSION['form_data'] = $_POST;
            }
            break;

        // 削除
        case 'entdel':
            if (isset($_POST['delcb']) && $_POST['delcb'] === 'yes') {
                $p_d_cidnumber = $_POST['d_cidnumber'];
                AbspFunctions\del_db_item('cidname', $p_d_cidnumber);
                $flash_message['text'] = "番号 {$p_d_cidnumber} を削除しました。";
            } else {
                $flash_message = ['type' => 'error', 'text' => '削除するにはチェックボックスをオンにしてください。'];
            }
            break;

        // 編集モードへの移行
        case 'entedi':
            $_SESSION['edit_cid_data'] = [
                'cidnumber' => $_POST['e_cidnumber'],
                'cidname' => $_POST['e_cidname']
            ];
            $flash_message = null; // メッセージは不要
            break;
    }

    if ($flash_message) {
        $_SESSION['flash_message'] = $flash_message;
    }

    header('Location: index.php?page=cid-config-page');
    exit;
}

// GET時処理
$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

// 履歴ページなどからのGETリクエストで編集モードに入る処理
if (isset($_GET['post_pnum'])) {
    // AdHoc: 元のコードにあった履歴ページからの遷移チェック（必要に応じてコメント解除）
    // $ref_url = explode('/', $_SERVER['HTTP_REFERER'] ?? '');
    // if(end($ref_url) == 'index.php?page=call-log-disp.php'){
        $pnum = trim($_GET['post_pnum']);
        $pname = AbspFunctions\get_db_item('cidname', $pnum);
        // 着信拒否リストに載っている場合は、その旨を名前に表示する（元のロジックを維持）
        if (AbspFunctions\get_db_item('ABS/blocklist', $pnum) == '1') {
            $pname = '【着信拒否中】';
        }
        $_SESSION['edit_cid_data'] = ['cidnumber' => $pnum, 'cidname' => $pname];
    // }
}

// フォームの初期値と設定
$form_defaults = ['cidnumber' => '', 'cidname' => ''];
$form_values = $_SESSION['form_data'] ?? $form_defaults;
unset($_SESSION['form_data']);

$is_edit_mode = false;
if (isset($_SESSION['edit_cid_data'])) {
    $is_edit_mode = true;
    $form_values = $_SESSION['edit_cid_data'];
    unset($_SESSION['edit_cid_data']);
}

// 登録済み一覧の取得
$cid_list = [];
$db_entries = AbspFunctions\get_db_family('cidname');
if (is_array($db_entries)) {
    foreach ($db_entries as $line) {
        list($pnum, $pname) = explode(' : ', $line, 2);
        $cid_list[] = ['number' => trim($pnum), 'name' => trim($pname)];
    }
}

?>
<h2>発信者名(CID)管理</h2>

<?php if ($flash_message): ?>
<div class="notice-message" style="color: <?= $flash_message['type'] === 'error' ? '#f44336' : '#4CAF50' ?>; margin-bottom: 1.5em; font-weight: bold;">
    <?= htmlspecialchars($flash_message['text'], ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>

<h3><?= $is_edit_mode ? '発信者名 編集' : '発信者名 追加' ?></h3>
<form action="" method="POST">
    <input type="hidden" name="function" value="<?= $is_edit_mode ? 'update' : 'newadd' ?>">
    <div class="form-inline-group">
        <label for="cidnumber">番号:</label>
        <input type="text" id="cidnumber" name="cidnumber" value="<?= htmlspecialchars($form_values['cidnumber'], ENT_QUOTES, 'UTF-8') ?>" class="input-middle2" <?= $is_edit_mode ? 'readonly style="background-color: #444;"' : '' ?>>

        <label for="cidname">名称(CID name):</label>
        <input type="text" id="cidname" name="cidname" value="<?= htmlspecialchars($form_values['cidname'], ENT_QUOTES, 'UTF-8') ?>" class="input-middle">

        <button type="submit" class="btn btn-primary"><?= $is_edit_mode ? '更新' : '追加' ?></button>
        <?php if ($is_edit_mode): ?>
            <a href="index.php?page=cid-config-page" class="btn">キャンセル</a>
        <?php endif; ?>
    </div>
</form>

<h3>登録済み一覧</h3>
<div class="table-container">
    <table class="absp-table">
        <thead>
            <tr>
                <th>番号</th>
                <th>名称(CID name)</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($cid_list)): ?>
                <tr>
                    <td colspan="3" style="text-align: center; padding: 20px;">登録されている番号はありません。</td>
                </tr>
            <?php else: ?>
                <?php foreach ($cid_list as $cid_entry): ?>
                <tr>
                    <td><?= htmlspecialchars($cid_entry['number'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($cid_entry['name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <div class="form-inline-group" style="gap: 0.5em;">
                            <form action="" method="POST" style="margin: 0;">
                                <input type="hidden" name="function" value="entedi">
                                <input type="hidden" name="e_cidnumber" value="<?= htmlspecialchars($cid_entry['number'], ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="e_cidname" value="<?= htmlspecialchars($cid_entry['name'], ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" class="btn btn-row">編集</button>
                            </form>
                            <form action="" method="POST" style="margin: 0;" onsubmit="if(!this.delcb.checked) { alert('削除するにはチェックボックスをオンにしてください。'); return false; } return confirm('番号 <?= htmlspecialchars($cid_entry['number'], ENT_QUOTES, 'UTF-8') ?> を本当に削除しますか？');">
                                <input type="hidden" name="function" value="entdel">
                                <input type="hidden" name="d_cidnumber" value="<?= htmlspecialchars($cid_entry['number'], ENT_QUOTES, 'UTF-8') ?>">
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
