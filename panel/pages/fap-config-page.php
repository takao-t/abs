<?php
if (!defined('ABS_PANEL_INCLUDED')) {
    die("Direct access is not permitted.");
}

// POST時処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $flash_message = ['type' => 'success', 'text' => '設定を保存しました。'];
    $function = $_POST['function'] ?? '';

    switch ($function) {
        // ユーザ新規追加 or 更新
        case 'newadd':
        case 'update':
            $p_uid = trim($_POST['uid'] ?? '');
            $p_ext = trim($_POST['ext'] ?? '');
            $p_pin = trim($_POST['pin'] ?? '');
            $p_limit = trim($_POST['limit'] ?? '0');
            $p_ogcid = trim($_POST['ogcid'] ?? '');
            $is_valid = true;

            if (empty($p_uid) || empty($p_ext) || empty($p_pin)) {
                $flash_message = ['type' => 'error', 'text' => 'ユーザID, PIN, 内線番号は必須です。'];
                $is_valid = false;
            } elseif (!ctype_digit($p_uid) || !ctype_digit($p_ext) || !ctype_digit($p_pin)) {
                $flash_message = ['type' => 'error', 'text' => 'ユーザID, PIN, 内線番号は数字で入力してください。'];
                $is_valid = false;
            }

            if ($is_valid) {
                // 内線重複チェック
                $p_exists = false;
                $e_ext = AbspFunctions\get_db_item("ABS/EXT", $p_ext);
                if (!empty($e_ext) && !strstr($e_ext, "FAP")) {
                    $p_exists = true;
                    $flash_message = ['type' => 'error', 'text' => '内線番号が他の機能で既に使用されています。'];
                } else {
                    $entry = AbspFunctions\get_db_family('ABS/FAP/UID');
                    if (is_array($entry)) {
                        foreach ($entry as $line) {
                            list($uid, $ent) = explode('/', $line, 2);
                            list($cat, $val) = explode(':', $ent, 2);
                            if (trim($cat) == 'EXT' && trim($val) == $p_ext && trim($uid) != $p_uid) {
                                $p_exists = true;
                                $flash_message = ['type' => 'error', 'text' => '内線番号が他のフリーアドレスユーザに使用されています。'];
                                break;
                            }
                        }
                    }
                }

                if (!$p_exists) {
                    AbspFunctions\put_db_item("ABS/FAP/UID/$p_uid", "EXT", $p_ext);
                    AbspFunctions\put_db_item("ABS/FAP/UID/$p_uid", "PIN", $p_pin);
                    AbspFunctions\put_db_item("ABS/FAP/UID/$p_uid", "LMT", $p_limit);
                    AbspFunctions\put_db_item("ABS/FAP/UID/$p_uid", "OGCID", ctype_digit($p_ogcid) ? $p_ogcid : "");
                    $flash_message['text'] = ($function == 'newadd') ? "ユーザ {$p_uid} を追加しました。" : "ユーザ {$p_uid} を更新しました。";
                } else {
                    $is_valid = false; // 重複があったので、フォームの値をセッションで引き継ぐ
                }
            }

            // バリデーションエラーがあった場合、入力値をセッションに保存してフォームに再表示する
            if (!$is_valid) {
                $_SESSION['form_data'] = $_POST;
            }
            break;

        // ユーザ削除
        case 'entdel':
            if (isset($_POST['delcb']) && $_POST['delcb'] === 'yes') {
                $p_d_uid = $_POST['d_uid'];
                $p_d_ext = $_POST['d_ext'];
                $p_d_peer = $_POST['d_peer'];
                if (!empty($p_d_peer)) { // ログイン中の場合は関連データを削除
                    AbspFunctions\del_db_item("ABS/EXT/$p_d_ext", "OGCID");
                    AbspFunctions\del_db_item("ABS/EXT", $p_d_ext);
                    AbspFunctions\del_db_item("ABS/ERV", $p_d_peer);
                    AbspFunctions\del_db_item("ABS/LMT", $p_d_peer);
                }
                AbspFunctions\del_db_tree("ABS/FAP/UID/$p_d_uid");
                $flash_message['text'] = "ユーザ {$p_d_uid} を削除しました。";
            } else {
                $flash_message = ['type' => 'error', 'text' => '削除するにはチェックボックスをオンにしてください。'];
            }
            break;

        // ユーザ編集モードへの移行
        case 'entedi':
            $_SESSION['edit_user_data'] = [
                'uid' => $_POST['e_uid'], 'ext' => $_POST['e_ext'],
                'pin' => $_POST['e_pin'], 'limit' => $_POST['e_limit'],
                'ogcid' => $_POST['e_ogcid']
            ];
            $flash_message = null; // 編集モードに入るだけなのでメッセージは不要
            break;

        // 端末ログアウト
        case 'tlogout':
            $p_l_ext = $_POST['d_ext'];
            $p_l_peer = $_POST['d_peer'];
            AbspFunctions\del_db_item("ABS/EXT/$p_l_ext", "OGCID");
            AbspFunctions\del_db_item("ABS/EXT", $p_l_ext);
            AbspFunctions\del_db_item("ABS/ERV", $p_l_peer);
            AbspFunctions\del_db_item("ABS/LMT", $p_l_peer);
            AbspFunctions\exec_cli_command("devstate change Custom:$p_l_peer NOT_INUSE");
            $flash_message['text'] = "端末 {$p_l_peer} をログアウトさせました。";
            break;
        
        // 強制ログアウト設定
        case 'flogout':
            AbspFunctions\put_db_item('ABS/FAP', 'FLO', $_POST['flogout']);
            break;

        // MACアドレス登録
        case 'fap_reg':
            $p_macadd = trim($_POST['macadd']);
            $p_peer = trim($_POST['peer']);
            if (!empty($p_macadd)) {
                $p_macadd = strtoupper(str_replace('-', ':', $p_macadd));
                // 簡単なバリデーション
                if (filter_var($p_macadd, FILTER_VALIDATE_MAC)) {
                    AbspFunctions\put_db_item("ABS/FAP/MAC", $p_peer, $p_macadd);
                } else {
                    AbspFunctions\del_db_item("ABS/FAP/MAC", $p_peer); // 不正な値なら削除
                }
            } else {
                AbspFunctions\del_db_item("ABS/FAP/MAC", $p_peer);
            }
            break;
    }

    if ($flash_message) {
        $_SESSION['flash_message'] = $flash_message;
    }

    header('Location: index.php?page=fap-config-page');
    exit;
}

// GET時処理
$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

// フォームの初期値と設定
$form_defaults = ['uid' => '', 'ext' => '', 'pin' => '', 'ogcid' => '', 'limit' => '0'];
$form_values = $_SESSION['form_data'] ?? $form_defaults;
unset($_SESSION['form_data']);

$is_edit_mode = false;
if (isset($_SESSION['edit_user_data'])) {
    $is_edit_mode = true;
    $form_values = $_SESSION['edit_user_data'];
    unset($_SESSION['edit_user_data']);
}

// ユーザ一覧を取得
$user_list = [];
$db_entries = AbspFunctions\get_db_family('ABS/FAP/UID');
if (is_array($db_entries)) {
    foreach ($db_entries as $line) {
        list($uid, $ent) = explode('/', $line, 2);
        $uid = trim($uid);
        list($cat, $val) = explode(':', $ent, 2);
        $user_list[$uid]['UID'] = $uid;
        $user_list[$uid][trim($cat)] = trim($val);
    }
}
// ログイン状態を追加
foreach ($user_list as $uid => $user) {
    $ext = $user['EXT'] ?? '';
    $user_list[$uid]['LOGGED_IN_PEER'] = !empty($ext) ? AbspFunctions\get_db_item('ABS/EXT', $ext) : '';
}

// 強制ログアウト設定
$flogout_setting = AbspFunctions\get_db_item('ABS/FAP', 'FLO');

?>
<h2>フリーアドレスユーザ設定</h2>

<?php if ($flash_message): ?>
<div class="notice-message" style="color: <?= $flash_message['type'] === 'error' ? '#f44336' : '#4CAF50' ?>; margin-bottom: 1.5em; font-weight: bold;">
    <?= htmlspecialchars($flash_message['text'], ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>

<h3><?= $is_edit_mode ? 'ユーザ編集' : 'ユーザ追加' ?></h3>
<form action="" method="POST">
    <input type="hidden" name="function" value="<?= $is_edit_mode ? 'update' : 'newadd' ?>">
    <div class="form-inline-group">
        <label for="uid">ユーザID:</label>
        <input type="text" id="uid" name="uid" value="<?= htmlspecialchars($form_values['uid'], ENT_QUOTES, 'UTF-8') ?>" class="input-short" <?= $is_edit_mode ? 'readonly style="background-color: #444;"' : '' ?>>

        <label for="pin">PIN:</label>
        <input type="text" id="pin" name="pin" value="<?= htmlspecialchars($form_values['pin'], ENT_QUOTES, 'UTF-8') ?>" class="input-short">

        <label for="ext">内線番号:</label>
        <input type="text" id="ext" name="ext" value="<?= htmlspecialchars($form_values['ext'], ENT_QUOTES, 'UTF-8') ?>" class="input-short">

        <label for="limit">規制値:</label>
        <select id="limit" name="limit" class="input-xshort">
            <?php for ($i = 0; $i <= 3; $i++): ?>
            <option value="<?= $i ?>" <?= ((string)$form_values['limit'] === (string)$i) ? 'selected' : '' ?>><?= $i ?></option>
            <?php endfor; ?>
        </select>

        <label for="ogcid">発信者番号:</label>
        <input type="text" id="ogcid" name="ogcid" value="<?= htmlspecialchars($form_values['ogcid'], ENT_QUOTES, 'UTF-8') ?>" class="input-short2">

        <button type="submit" class="btn btn-primary"><?= $is_edit_mode ? '更新' : '追加' ?></button>
        <?php if ($is_edit_mode): ?>
            <a href="index.php?page=fap-config-page" class="btn">キャンセル</a>
        <?php endif; ?>
    </div>
</form>

<h3>フリーアドレスユーザ一覧</h3>
<div class="table-container">
    <table class="absp-table">
        <thead>
            <tr>
                <th>ユーザID</th>
                <th>PIN</th>
                <th>内線番号</th>
                <th>規制値</th>
                <th>発信者番号</th>
                <th>ログイン端末</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($user_list)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 20px;">登録されているユーザはありません。</td>
                </tr>
            <?php else: ?>
                <?php foreach ($user_list as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['UID'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($user['PIN'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($user['EXT'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($user['LMT'] ?? '0', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($user['OGCID'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($user['LOGGED_IN_PEER'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <div class="form-inline-group" style="gap: 0.5em;">
                            <form action="" method="POST" style="margin: 0;">
                                <input type="hidden" name="function" value="entedi">
                                <input type="hidden" name="e_uid" value="<?= htmlspecialchars($user['UID'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="e_ext" value="<?= htmlspecialchars($user['EXT'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="e_pin" value="<?= htmlspecialchars($user['PIN'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="e_limit" value="<?= htmlspecialchars($user['LMT'] ?? '0', ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="e_ogcid" value="<?= htmlspecialchars($user['OGCID'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" class="btn btn-row">編集</button>
                            </form>
                            <form action="" method="POST" style="margin: 0;" onsubmit="if(!this.delcb.checked) { alert('削除するにはチェックボックスをオンにしてください。'); return false; } return confirm('ユーザ <?= htmlspecialchars($user['UID'] ?? '', ENT_QUOTES, 'UTF-8') ?> を本当に削除しますか？');">
                                <input type="hidden" name="function" value="entdel">
                                <input type="hidden" name="d_uid" value="<?= htmlspecialchars($user['UID'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="d_ext" value="<?= htmlspecialchars($user['EXT'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="d_peer" value="<?= htmlspecialchars($user['LOGGED_IN_PEER'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <input type="checkbox" name="delcb" value="yes" title="削除するにはチェックを入れてください">
                                <button type="submit" class="btn btn-row">削除</button>
                            </form>
                            <?php if (!empty($user['LOGGED_IN_PEER'])): ?>
                                <form action="" method="POST" style="margin: 0;">
                                    <input type="hidden" name="function" value="tlogout">
                                    <input type="hidden" name="d_ext" value="<?= htmlspecialchars($user['EXT'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="d_peer" value="<?= htmlspecialchars($user['LOGGED_IN_PEER'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                    <button type="submit" class="btn btn-row">ログアウト</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<h3>各種設定</h3>
<form action="" method="POST" class="form-inline-group">
    <label for="flogout">他端末でログイン時の強制ログアウト:</label>
    <select name="flogout" id="flogout" class="input-em6">
        <option value="YES" <?= ($flogout_setting !== 'NO') ? 'selected' : '' ?>>する</option>
        <option value="NO" <?= ($flogout_setting === 'NO') ? 'selected' : '' ?>>しない</option>
    </select>
    <input type="hidden" name="function" value="flogout">
    <button type="submit" class="btn">設定</button>
</form>


<h3>フリーアドレス端末管理 (電話機設定ファイル生成用)</h3>
<div class="table-container">
    <table class="absp-table">
        <thead>
            <tr>
                <th>端末(ピア)名</th>
                <th>MACアドレス</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php for($i = 1; $i <= $max_sip_phones; $i++):
                $peer_name = 'FAP' . sprintf("%03d", $i);
                $mac_address = AbspFunctions\get_db_item('ABS/FAP/MAC', $peer_name);
            ?>
            <tr>
                <td><?= htmlspecialchars($peer_name, ENT_QUOTES, 'UTF-8') ?></td>
                <td style="width: 250px;">
                    <form action="" method="post" style="margin: 0;">
                        <input type="text" name="macadd" value="<?= htmlspecialchars($mac_address, ENT_QUOTES, 'UTF-8') ?>" class="input-middle">
                </td>
                <td>
                        <input type="hidden" name="function" value="fap_reg">
                        <input type="hidden" name="peer" value="<?= htmlspecialchars($peer_name, ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" class="btn btn-row">登録</button>
                    </form>
                </td>
            </tr>
            <?php endfor; ?>
        </tbody>
    </table>
</div>
