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
            $p_didnumber = trim($_POST['didnumber'] ?? '');
            $p_target = trim($_POST['target'] ?? '');
            $is_valid = true;

            if (empty($p_didnumber) || empty($p_target)) {
                $flash_message = ['type' => 'error', 'text' => '着信番号と着信先は必須です。'];
                $is_valid = false;
            } elseif ($p_didnumber !== 'any' && !ctype_digit($p_didnumber)) {
                $flash_message = ['type' => 'error', 'text' => '着信番号は数字、または "any" で入力してください。'];
                $is_valid = false;
            }

            if ($is_valid) {
                AbspFunctions\put_db_item('ABS/DID', $p_didnumber, $p_target);
                $flash_message['text'] = ($function == 'newadd') ? "着信番号 {$p_didnumber} を追加しました。" : "着信番号 {$p_didnumber} を更新しました。";
            } else {
                // バリデーションエラーがあった場合、入力値をセッションに保存
                $_SESSION['form_data'] = $_POST;
            }
            break;

        // 削除
        case 'entdel':
            if (isset($_POST['delcb']) && $_POST['delcb'] === 'yes') {
                $p_d_didnumber = $_POST['d_didnumber'];
                AbspFunctions\del_db_item('ABS/DID', $p_d_didnumber);
                $flash_message['text'] = "着信番号 {$p_d_didnumber} を削除しました。";
            } else {
                $flash_message = ['type' => 'error', 'text' => '削除するにはチェックボックスをオンにしてください。'];
            }
            break;

        // 編集モードへの移行
        case 'entedi':
            $_SESSION['edit_did_data'] = [
                'didnumber' => $_POST['e_didnumber'],
                'target' => $_POST['e_target']
            ];
            $flash_message = null; // 編集モードに入るだけなのでメッセージは不要
            break;

        // 鳴動パターン設定
        case 'rgptset':
            $p_rgpt = $_POST['rgpt'];
            AbspFunctions\put_db_item('ABS/DID', 'RGPT', $p_rgpt);
            $flash_message['text'] = 'ダイヤルイン時鳴動パターンを設定しました。';
            break;

        // 着信時プレフィクス付加設定
        case 'pfxadd':
            $p_apfx = $_POST['apfx'] ?? '0';
            AbspFunctions\put_db_item('ABS', 'APF', $p_apfx);

            if (isset($_POST['d56opt']) && $_POST['d56opt'] === 'on') {
                AbspFunctions\put_db_item('ABS', 'D56', '1');
            } else {
                AbspFunctions\del_db_item('ABS', 'D56');
            }
            $flash_message['text'] = '着信時外線捕捉プレフィクス設定を更新しました。';
            break;
    }

    if ($flash_message) {
        $_SESSION['flash_message'] = $flash_message;
    }

    header('Location: index.php?page=did-config-page');
    exit;
}

// GET時処理
$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

// フォームの初期値と設定
$form_defaults = ['didnumber' => '', 'target' => ''];
$form_values = $_SESSION['form_data'] ?? $form_defaults;
unset($_SESSION['form_data']);

$is_edit_mode = false;
if (isset($_SESSION['edit_did_data'])) {
    $is_edit_mode = true;
    $form_values = $_SESSION['edit_did_data'];
    unset($_SESSION['edit_did_data']);
}

// 着信先選択肢を生成
$target_selectors = AbspFunctions\create_target_list('group', $form_values['target']);

// 登録済着信番号一覧を取得
$did_list = [];
$db_entries = AbspFunctions\get_db_family('ABS/DID');
if (is_array($db_entries)) {
    foreach ($db_entries as $line) {
        list($pnam, $target) = explode(' : ', $line, 2);
        $pnam = trim($pnam);
        $target = trim($target);
        if ($pnam !== 'RGPT' && $pnam !== 'TCS' && $pnam != 'THS') { // 除外する項目
             $did_list[] = ['did' => $pnam, 'target' => $target];
        }
    }
}

// 各種設定値を取得
$rgpt_setting = AbspFunctions\get_db_item('ABS/DID', 'RGPT') ?: '0';
$apfx_setting = AbspFunctions\get_db_item('ABS', 'APF') ?: '0';
$d56_setting = AbspFunctions\get_db_item('ABS', 'D56');

?>
<h2>ダイヤルイン着信設定</h2>

<?php if ($flash_message): ?>
<div class="notice-message" style="color: <?= $flash_message['type'] === 'error' ? '#f44336' : '#4CAF50' ?>; margin-bottom: 1.5em; font-weight: bold;">
    <?= htmlspecialchars($flash_message['text'], ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>

<h3><?= $is_edit_mode ? 'ダイヤルイン着信番号編集' : 'ダイヤルイン着信番号追加' ?></h3>
<form action="" method="POST">
    <input type="hidden" name="function" value="<?= $is_edit_mode ? 'update' : 'newadd' ?>">
    <div class="form-inline-group">
        <label for="didnumber">着信番号:</label>
        <input type="text" id="didnumber" name="didnumber" value="<?= htmlspecialchars($form_values['didnumber'], ENT_QUOTES, 'UTF-8') ?>" class="input-middle2" <?= $is_edit_mode ? 'readonly style="background-color: #444;"' : '' ?> placeholder="例: 0312345678 or any">

        <label for="target">着信先:</label>
        <select id="target" name="target">
            <?= $target_selectors ?>
        </select>

        <button type="submit" class="btn btn-primary"><?= $is_edit_mode ? '更新' : '追加' ?></button>
        <?php if ($is_edit_mode): ?>
            <a href="index.php?page=did-config-page" class="btn">キャンセル</a>
        <?php endif; ?>
    </div>
    <p style="font-size: 0.9em; color: var(--secondary-text-color); margin-top: 0.5em;">
        着信番号はハイフンなしの数字、またはすべての着信にマッチする 'any' を指定します。
    </p>
</form>


<h3>登録済着信番号一覧</h3>
<div class="table-container">
    <table class="absp-table">
        <thead>
            <tr>
                <th>着信番号</th>
                <th>着信先</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($did_list)): ?>
                <tr>
                    <td colspan="3" style="text-align: center; padding: 20px;">登録されている着信番号はありません。</td>
                </tr>
            <?php else: ?>
                <?php foreach ($did_list as $did_entry): ?>
                <tr>
                    <td><?= htmlspecialchars($did_entry['did'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($did_entry['target'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <div class="form-inline-group" style="gap: 0.5em;">
                            <form action="" method="POST" style="margin: 0;">
                                <input type="hidden" name="function" value="entedi">
                                <input type="hidden" name="e_didnumber" value="<?= htmlspecialchars($did_entry['did'], ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="e_target" value="<?= htmlspecialchars($did_entry['target'], ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" class="btn btn-row">編集</button>
                            </form>
                            <form action="" method="POST" style="margin: 0;" onsubmit="if(!this.delcb.checked) { alert('削除するにはチェックボックスをオンにしてください。'); return false; } return confirm('着信番号 <?= htmlspecialchars($did_entry['did'], ENT_QUOTES, 'UTF-8') ?> を本当に削除しますか？');">
                                <input type="hidden" name="function" value="entdel">
                                <input type="hidden" name="d_didnumber" value="<?= htmlspecialchars($did_entry['did'], ENT_QUOTES, 'UTF-8') ?>">
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

<h3>各種設定</h3>
<form action="" method="POST" class="form-inline-group" style="margin-bottom: 1.5em;">
    <input type="hidden" name="function" value="rgptset">
    <label for="rgpt">ダイヤルイン時鳴動パターン:</label>
    <select name="rgpt" id="rgpt" class="input-xshort">
        <?php for ($i = 0; $i <= 5; $i++): ?>
        <option value="<?= $i ?>" <?= ((string)$rgpt_setting === (string)$i) ? 'selected' : '' ?>><?= $i ?></option>
        <?php endfor; ?>
    </select>
    <button type="submit" class="btn">設定</button>
</form>

<form action="" method="POST">
    <input type="hidden" name="function" value="pfxadd">
    <div class="form-inline-group">
        <label for="apfx">着信時外線捕捉プレフィクス付加:</label>
        <select name="apfx" id="apfx" class="input-em6">
            <option value="1" <?= ($apfx_setting === '1') ? 'selected' : '' ?>>する</option>
            <option value="0" <?= ($apfx_setting !== '1') ? 'selected' : '' ?>>しない</option>
        </select>
    </div>
    <div style="font-size: 0.9em; color: var(--secondary-text-color); margin: 0.5em 0;">
        着信時のCIDに外線発信用プレフィクスを付加します (ダイヤルイン時はOGP1, キー着信時は*56x)。
    </div>
    <div class="form-inline-group">
        <input type="checkbox" name="d56opt" value="on" id="d56opt" <?= ($d56_setting === '1') ? 'checked' : '' ?>>
        <label for="d56opt">D56 (*56[キー番号]) 特番でリダイヤル発信を有効にする</label>
    </div>
     <button type="submit" class="btn" style="margin-top: 1em;">設定</button>
</form>
