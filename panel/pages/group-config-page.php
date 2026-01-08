<?php
/**
 * group-config-page.php
 * * 内線グループ設定ページ
 * * Refactored for AbspManager Class
 */

if (!defined('ABS_PANEL_INCLUDED')) {
    die("Direct access is not permitted.");
}

global $ami;
global $max_group, $max_pgroup;

// セッションからメッセージを復元 (GET時)
$notice_msg_g = $_SESSION['notice_msg_g'] ?? [];
$notice_msg_p = $_SESSION['notice_msg_p'] ?? [];

// 一度表示したらクリア
unset($_SESSION['notice_msg_g']);
unset($_SESSION['notice_msg_p']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 保存用配列を初期化 (上書きされないように注意)
    // 実際にはリダイレクトするので、ここで処理した結果をSESSIONに入れる
    $new_notice_msg_g = [];
    $new_notice_msg_p = [];

    // ===== 内線グループ設定の処理 =====

    // 個別保存ボタンが押された場合
    if (isset($_POST['update_group'])) {
        $p_grp = key($_POST['update_group']); // 押されたボタンのキー（グループ番号）を取得

        $p_member = $_POST['grp_member'][$p_grp] ?? '';
        $p_mode   = $_POST['mode'][$p_grp] ?? 'RA';
        $p_exten  = $_POST['exten'][$p_grp] ?? '';
        $p_timeout= $_POST['timeout'][$p_grp] ?? '';
        $p_ovr    = $_POST['ovr'][$p_grp] ?? '0';
        $p_bnl    = $_POST['bnl'][$p_grp] ?? '0';
        $p_bnt    = $_POST['bnt'][$p_grp] ?? '';

        $group_info_set = [ 
            'member' => $p_member, 
            'group' => $p_grp, 
            'mode' => $p_mode, 
            'exten' => $p_exten, 
            'timeout' => $p_timeout, 
            'ovr' => $p_ovr, 
            'bnl' => $p_bnl, 
            'bnt' => $p_bnt 
        ];

        // FDとの重複チェック
        $e_exists = false;
        if (trim($p_exten) !== '') {
            $entry = $ami->getFamilyDB('ABS/FAP/UID');
            if (is_array($entry)) {
                foreach ($entry as $line) {
                    // "UID/KEY : VALUE" 形式をパース
                    $parts = explode('/', $line, 2);
                    if (count($parts) < 2) continue;
                    
                    $remain = $parts[1]; // KEY : VALUE
                    list($cat_part, $val_part) = explode(':', $remain, 2);
                    
                    if (trim($cat_part) == 'EXT' && trim($val_part) == $p_exten) { 
                        $e_exists = true; 
                        break; 
                    }
                }
            }
        }

        if ($e_exists) {
            $new_notice_msg_g[$p_grp] = "エラー: 内線番号がFDと重複";
        } else {
            $new_notice_msg_g[$p_grp] = $ami->setGroupInfo($group_info_set);
        }
    }
    // 一括保存ボタンが押された場合
    elseif (isset($_POST['update_all_groups'])) {
        $p_members = $_POST['grp_member'] ?? [];
        $p_extens  = $_POST['exten'] ?? [];

        // FD内線リスト取得
        $fd_extens = [];
        $entry = $ami->getFamilyDB('ABS/FAP/UID');
        if (is_array($entry)) {
            foreach ($entry as $line) {
                $parts = explode('/', $line, 2);
                if (count($parts) < 2) continue;
                list($cat_part, $val_part) = explode(':', $parts[1], 2);
                if (trim($cat_part) == 'EXT') {
                    $fd_extens[] = trim($val_part);
                }
            }
        }

        foreach ($p_members as $grp_id => $member) {
            $p_exten = $_POST['exten'][$grp_id] ?? '';
            
            // 重複チェック
            if (trim($p_exten) !== '' && in_array(trim($p_exten), $fd_extens)) {
                 $new_notice_msg_g[$grp_id] = "エラー: 内線番号がFDと重複";
                 continue;
            }

            $group_info_set = [
                'member'  => $member,
                'group'   => $grp_id,
                'mode'    => $_POST['mode'][$grp_id] ?? 'RA',
                'exten'   => $p_exten,
                'timeout' => $_POST['timeout'][$grp_id] ?? '',
                'ovr'     => $_POST['ovr'][$grp_id] ?? '0',
                'bnl'     => $_POST['bnl'][$grp_id] ?? '0',
                'bnt'     => $_POST['bnt'][$grp_id] ?? '',
            ];
            $new_notice_msg_g[$grp_id] = $ami->setGroupInfo($group_info_set);
        }
    }

    // ===== ピックアップグループ設定の処理 =====

    // 個別保存ボタンが押された場合
    if (isset($_POST['update_pgrp'])) {
        $p_pgrp = key($_POST['update_pgrp']);
        $p_member = $_POST['pgrp_member'][$p_pgrp] ?? '';
        $new_notice_msg_p[$p_pgrp] = $ami->setPgrpMember($p_pgrp, $p_member);
    }
    // 一括保存ボタンが押された場合
    elseif (isset($_POST['update_all_pgrps'])) {
        $p_members = $_POST['pgrp_member'] ?? [];
        foreach ($p_members as $pgrp_id => $member) {
            $new_notice_msg_p[$pgrp_id] = $ami->setPgrpMember($pgrp_id, $member);
        }
    }

    // セッションに保存してリダイレクト
    $_SESSION['notice_msg_g'] = $new_notice_msg_g;
    $_SESSION['notice_msg_p'] = $new_notice_msg_p;

    header('Location: index.php?page=group-config-page');
    exit;
}

?>
<h2>内線グループ設定</h2>

<form action="" method="post">
<table class="absp-table">
    <thead>
        <tr>
            <th>番号</th>
            <th>所属内線(カンマ区切り)</th>
            <th>内線番号</th>
            <th>モード</th>
            <th>タイムアウト</th>
            <th>話中検出</th>
            <th>キュー</th>
            <th>待機</th>
            <th>操作</th>
            <th>状態</th>
        </tr>
    </thead>
    <tbody>
        <?php for ($i = 1; $i <= $max_group; $i++): ?>
            <?php
            // クラスから情報を取得
            $group_info = $ami->getGroupInfo($i);
            
            $member = $group_info['member'] ?? '';
            $timeout = $member != '' ? ($group_info['timeout'] ?? '') : '';
            $mode = $member != '' ? ($group_info['mode'] ?? 'RA') : '';
            $exten = $member != '' ? ($group_info['exten'] ?? '') : '';
            $ovr = $member != '' ? ($group_info['ovr'] ?? '0') : '0';
            $bnl = $member != '' ? ($group_info['bnl'] ?? '0') : '0';
            $bnt = $member != '' ? ($group_info['bnt'] ?? '') : '';
            
            $msg = $notice_msg_g[$i] ?? '';
            ?>
            <tr>
                <td style="text-align: right;">G<?= $i ?></td>
                <td><input type="text" name="grp_member[<?= $i ?>]" value="<?= htmlspecialchars($member, ENT_QUOTES, 'UTF-8') ?>" class="input-middle"></td>
                <td><input type="text" name="exten[<?= $i ?>]" value="<?= htmlspecialchars($exten, ENT_QUOTES, 'UTF-8') ?>" class="input-short"></td>
                <td>
                    <select name="mode[<?= $i ?>]" class="input-short">
                        <option value="RA" <?= ($mode == 'RA') ? 'selected' : '' ?>>RA</option>
                        <option value="RR" <?= ($mode == 'RR') ? 'selected' : '' ?>>RR</option>
                        <option value="RM" <?= ($mode == 'RM') ? 'selected' : '' ?>>RM</option>
                    </select>
                </td>
                <td><input type="text" name="timeout[<?= $i ?>]" value="<?= htmlspecialchars($timeout, ENT_QUOTES, 'UTF-8') ?>" class="input-xshort"></td>
                <td>
                    <select name="ovr[<?= $i ?>]" class="input-short">
                        <option value="0" <?= ($ovr == '0') ? 'selected' : '' ?>>有効</option>
                        <option value="1" <?= ($ovr == '1') ? 'selected' : '' ?>>無効</option>
                    </select>
                </td>
                <td>
                    <select name="bnl[<?= $i ?>]" class="input-xmiddle">
                        <option value="0" <?= ($bnl == '0') ? 'selected' : '' ?>>いいえ</option>
                        <option value="1" <?= ($bnl == '1') ? 'selected' : '' ?>>はい</option>
                    </select>
                </td>
                <td><input type="text" name="bnt[<?= $i ?>]" value="<?= htmlspecialchars($bnt, ENT_QUOTES, 'UTF-8') ?>" class="input-xshort"></td>
                <td>
                    <button type="submit" name="update_group[<?= $i ?>]" value="save" class="btn btn-row">設定</button>
                </td>
                <td class="notice-message">
                    <?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?>
                </td>
            </tr>
        <?php endfor; ?>
    </tbody>
</table>
<?php // <div style="text-align: right; margin-top: 10px;"> ?>
<?php //    <button type="submit" name="update_all_groups" value="save_all" class="btn btn-primary">内線グループ設定をすべて保存</button> ?>
<?php // </div> ?>
</form>

<hr>

<h3>ピックアップグループ設定</h3>
<form action="#pgrp_form" method="POST">
<table class="absp-table">
    <thead>
        <tr>
            <th>番号</th>
            <th>所属内線</th>
            <th>操作</th>
            <th>状態</th>
        </tr>
    </thead>
    <tbody>
        <?php for ($i = 1; $i <= $max_pgroup; $i++): ?>
            <?php
            // クラスから情報を取得
            $pgrp_member = $ami->getPgrpMember($i);
            $msg = $notice_msg_p[$i] ?? '';
            ?>
            <tr>
                <td style="text-align: right;"><?= $i ?></td>
                <td><input type="text" class="input-middle" name="pgrp_member[<?= $i ?>]" value="<?= htmlspecialchars($pgrp_member, ENT_QUOTES, 'UTF-8') ?>"></td>
                <td>
                    <button type="submit" name="update_pgrp[<?= $i ?>]" value="save" class="btn btn-row">設定</button>
                </td>
                <td class="notice-message">
                    <?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?>
                </td>
            </tr>
        <?php endfor; ?>
    </tbody>
</table>
<?php // <div style="text-align: right; margin-top: 10px;"> ?>
<?php //   <button type="submit" name="update_all_pgrps" value="save_all" class="btn btn-primary">ピックアップグループ設定をすべて保存</button> ?>
<?php // </div> ?>
<div id="pgrp_form"></div>
</form>
