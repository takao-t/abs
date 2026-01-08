<?php
/**
 * fap-config-page.php
 * フリーアドレスデスク(FAP)設定ページ
 * Refactored for AbspManager Class & PJSIP support
 */

if (!defined('ABS_PANEL_INCLUDED')) {
    die("Direct access is not permitted.");
}

global $ami;
global $max_fap_phones;

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
            $p_pgrp = trim($_POST['pgrp'] ?? '');
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
                
                // 1. 通常の内線(EXT)との重複チェック
                $e_ext = $ami->getDbItem("ABS/EXT", $p_ext);
                if (!empty($e_ext) && !strstr($e_ext, "FAP")) {
                    $p_exists = true;
                    $flash_message = ['type' => 'error', 'text' => '内線番号が他の機能で既に使用されています。'];
                } else {
                    // 2. 他のFAPユーザとの重複チェック
                    $entry = $ami->getFamilyDB('ABS/FAP/UID');
                    if (is_array($entry)) {
                        foreach ($entry as $line) {
                            // getFamilyDBの戻り値は "UID/KEY : VALUE" の形式 (プレフィックス除去済)
                            // 例: "1001/EXT : 200"
                            $parts = explode('/', $line, 2);
                            if (count($parts) < 2) continue;

                            $chk_uid = trim($parts[0]);
                            $remain = $parts[1];

                            list($cat_part, $val_part) = explode(':', $remain, 2);
                            $cat = trim($cat_part);
                            $val = trim($val_part);

                            if ($cat == 'EXT' && $val == $p_ext && $chk_uid != $p_uid) {
                                $p_exists = true;
                                $flash_message = ['type' => 'error', 'text' => '内線番号が他のフリーアドレスユーザに使用されています。'];
                                break;
                            }
                        }
                    }
                }

                if (!$p_exists) {
                    $ami->putDbItem("ABS/FAP/UID/$p_uid", "EXT", $p_ext);
                    $ami->putDbItem("ABS/FAP/UID/$p_uid", "PIN", $p_pin);
                    $ami->putDbItem("ABS/FAP/UID/$p_uid", "LMT", $p_limit);
                    $ami->putDbItem("ABS/FAP/UID/$p_uid", "PGRP", $p_pgrp);
                    $ami->putDbItem("ABS/FAP/UID/$p_uid", "OGCID", ctype_digit($p_ogcid) ? $p_ogcid : "");
                    
                    $flash_message['text'] = ($function == 'newadd') ? "ユーザ {$p_uid} を追加しました。" : "ユーザ {$p_uid} を更新しました。";
                } else {
                    $is_valid = false;
                }
            }

            if (!$is_valid) {
                $_SESSION['form_data'] = $_POST;
            }
            break;

        // ユーザ削除
        case 'entdel':
            if (isset($_POST['delcb']) && $_POST['delcb'] === 'yes') {
                $p_d_uid = $_POST['d_uid'];
                $p_d_ext = $_POST['d_ext'];
                $p_d_endpoint = $_POST['d_endpoint']; // ログイン中のエンドポイント (例: PJSIP/phone1)

                if (!empty($p_d_endpoint)) {
                    // ログイン中の場合は関連データを削除
                    $ami->delDbItem("ABS/EXT/$p_d_ext", "OGCID");
                    $ami->delDbItem("ABS/EXT", $p_d_ext);
                    $ami->delDbItem("ABS/ERV", $p_d_endpoint);
                    $ami->delDbItem("ABS/LMT", $p_d_endpoint);
                }
                $ami->delDbTreeItem("ABS/FAP/UID/$p_d_uid");
                $flash_message['text'] = "ユーザ {$p_d_uid} を削除しました。";
            } else {
                $flash_message = ['type' => 'error', 'text' => '削除するにはチェックボックスをオンにしてください。'];
            }
            break;

        // ユーザ編集モードへの移行
        case 'entedi':
            $_SESSION['edit_user_data'] = [
                'uid' => $_POST['e_uid'], 
                'ext' => $_POST['e_ext'],
                'pin' => $_POST['e_pin'], 
                'limit' => $_POST['e_limit'],
                'ogcid' => $_POST['e_ogcid']
            ];
            $flash_message = null;
            break;

        // 端末ログアウト
        case 'tlogout':
            $p_l_ext = $_POST['d_ext'];
            $p_l_endpoint = $_POST['d_endpoint']; // 例: PJSIP/phone1

            $ami->delDbItem("ABS/EXT/$p_l_ext", "OGCID");
            $ami->delDbItem("ABS/EXT", $p_l_ext);
            $ami->delDbItem("ABS/ERV", $p_l_endpoint);
            $ami->delDbItem("ABS/LMT", $p_l_endpoint);
            
            // DevStateの変更。Custom:ステータス名はシステム仕様に準拠させる。
            // $p_l_endpoint が "PJSIP/phone1" の場合、そのまま渡すか "phone1" にするかは既存仕様次第だが、
            // ここでは安全のためそのまま渡す (Asterisk側で柔軟に処理されることを期待、またはCustom:PJSIP/phone1という名前で運用されている前提)
            $ami->execCliCommand("devstate change Custom:$p_l_endpoint NOT_INUSE");
            
            $flash_message['text'] = "端末 {$p_l_endpoint} をログアウトさせました。";
            break;
        
        // 強制ログアウト設定
        case 'flogout':
            $ami->putDbItem('ABS/FAP', 'FLO', $_POST['flogout']);
            break;

        // MACアドレス登録 (端末管理)
        case 'fap_reg':
            $p_macadd = trim($_POST['macadd'] ?? '');
            $p_terminal_id = trim($_POST['terminal_id'] ?? ''); // FAP001 等

            if (!empty($p_macadd)) {
                // クラスメソッドで正規化 (区切り文字除去・大文字化)
                $valid_mac = $ami->normalizeMacAddress($p_macadd);
                
                if ($valid_mac) {
                    $ami->putDbItem("ABS/FAP/MAC", $p_terminal_id, $valid_mac);
                } else {
                    $flash_message = ['type' => 'error', 'text' => "MACアドレスの形式が不正です: $p_macadd"];
                    $ami->delDbItem("ABS/FAP/MAC", $p_terminal_id);
                }
            } else {
                $ami->delDbItem("ABS/FAP/MAC", $p_terminal_id);
                $flash_message['text'] = "端末 {$p_terminal_id} のMACアドレスを削除しました。";
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

// フォーム初期値
$form_defaults = ['uid' => '', 'ext' => '', 'pin' => '', 'ogcid' => '', 'limit' => '0', 'pgrp' => ''];
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
$db_entries = $ami->getFamilyDB('ABS/FAP/UID');
if (is_array($db_entries)) {
    foreach ($db_entries as $line) {
        // "1001/EXT : 200" 形式をパース
        $parts = explode('/', $line, 2);
        if (count($parts) < 2) continue;

        $uid = trim($parts[0]);
        $remain = $parts[1];

        list($cat_part, $val_part) = explode(':', $remain, 2);
        
        $cat = trim($cat_part); // EXT, PIN, LMT...
        $val = trim($val_part);

        $user_list[$uid]['UID'] = $uid;
        $user_list[$uid][$cat] = $val;
    }
}
// ログイン状態を追加
foreach ($user_list as $uid => $user) {
    $ext = $user['EXT'] ?? '';
    // 内線番号からログイン中のエンドポイント(PJSIP/phone1など)を取得
    $user_list[$uid]['LOGGED_IN_PEER'] = !empty($ext) ? $ami->getDbItem('ABS/EXT', $ext) : '';
}

// 強制ログアウト設定
$flogout_setting = $ami->getDbItem('ABS/FAP', 'FLO');

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

        <label for="pgrp">PickUp:</label>
        <input type="text" id="pgrp" name="pgrp" value="<?= htmlspecialchars($form_values['pgrp'], ENT_QUOTES, 'UTF-8') ?>" class="input-xshort">

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
                <th>PickUp</th>
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
                    <td><?= htmlspecialchars($user['PGRP'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
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
                                <input type="hidden" name="d_endpoint" value="<?= htmlspecialchars($user['LOGGED_IN_PEER'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <input type="checkbox" name="delcb" value="yes" title="削除するにはチェックを入れてください">
                                <button type="submit" class="btn btn-row">削除</button>
                            </form>
                            <?php if (!empty($user['LOGGED_IN_PEER'])): ?>
                                <form action="" method="POST" style="margin: 0;">
                                    <input type="hidden" name="function" value="tlogout">
                                    <input type="hidden" name="d_ext" value="<?= htmlspecialchars($user['EXT'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="d_endpoint" value="<?= htmlspecialchars($user['LOGGED_IN_PEER'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
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
                <th>端末名</th>
                <th>MACアドレス</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php for($i = 1; $i <= $max_fap_phones; $i++):
                // FAP001, FAP002... という仮想的な端末名(プロビジョニングID)を使用
                $fap_name = 'FAP' . sprintf("%03d", $i);
                
                // DBからMAC取得
                $raw_mac = $ami->getDbItem('ABS/FAP/MAC', $fap_name);
                
                // 表示用に整形 (例: AA:BB:CC...)
                $display_mac = $ami->formatMacAddress($raw_mac);
            ?>
            <tr>
                <td><?= htmlspecialchars($fap_name, ENT_QUOTES, 'UTF-8') ?></td>
                <td style="width: 250px;">
                    <form action="" method="post" style="margin: 0;">
                        <input type="text" name="macadd" value="<?= htmlspecialchars($display_mac, ENT_QUOTES, 'UTF-8') ?>" class="input-middle">
                </td>
                <td>
                        <input type="hidden" name="function" value="fap_reg">
                        <input type="hidden" name="terminal_id" value="<?= htmlspecialchars($fap_name, ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" class="btn btn-row">登録</button>
                    </form>
                </td>
            </tr>
            <?php endfor; ?>
        </tbody>
    </table>
</div>
