<?php
if (!defined('ABS_PANEL_INCLUDED')) {
    die("Direct access is not permitted.");
}

$notice_msg = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = '';

    //  個別キー保存の処理
    if (isset($_POST['update_key'])) {
        $p_key = key($_POST['update_key']);
        
        $key_info_set = [
            'key'   => $p_key,
            'label' => $_POST['label'][$p_key] ?? '',
            'tech'  => $_POST['tech'][$p_key] ?? 'PJSIP',
            'trunk' => $_POST['trunk'][$p_key] ?? '',
            'type'  => $_POST['key_type'][$p_key] ?? '',
            'ogcid' => $_POST['ogcid'][$p_key] ?? '',
            'rgrp'  => $_POST['rgrp'][$p_key] ?? '',
            'rgpt'  => $_POST['rgpt'][$p_key] ?? '0',
            'bpin'  => $_POST['bpin'][$p_key] ?? '',
            'mmd'   => $_POST['mmd'][$p_key] ?? 'B',
        ];
        $notice_msg[$p_key] = AbspFunctions\set_key_info($key_info_set);
    }
    // 全キー一括保存の処理
    elseif (isset($_POST['update_all_keys'])) {
        $labels = $_POST['label'] ?? [];
        foreach ($labels as $p_key => $p_label) {
            $key_info_set = [
                'key'   => $p_key,
                'label' => $p_label,
                'tech'  => $_POST['tech'][$p_key] ?? 'PJSIP',
                'trunk' => $_POST['trunk'][$p_key] ?? '',
                'type'  => $_POST['key_type'][$p_key] ?? '',
                'ogcid' => $_POST['ogcid'][$p_key] ?? '',
                'rgrp'  => $_POST['rgrp'][$p_key] ?? '',
                'rgpt'  => $_POST['rgpt'][$p_key] ?? '0',
                'bpin'  => $_POST['bpin'][$p_key] ?? '',
                'mmd'   => $_POST['mmd'][$p_key] ?? 'B',
            ];
            $notice_msg[$p_key] = AbspFunctions\set_key_info($key_info_set);
        }
    }
    //  手動トランク設定の処理
    elseif (isset($_POST['function']) && $_POST['function'] == 'trunkadd') {
        $p_trunk = trim($_POST['trunk']);
        $p_key = $_POST['keynum'];
        AbspFunctions\put_db_item("KEYTEL/KEYSYS$p_key", 'TRUNK', $p_trunk);
        $notice_msg[$p_key] = "手動設定しました";
    }

    $_SESSION['flash_message'] = $message;
    header('Location: index.php?page=keysys-config-page');
    exit;
}

$flash_message = $_SESSION['flash_message'] ?? '';
unset($_SESSION['flash_message'])
?>
<h2>キーシステム設定</h2>

<div class="table-container">
<form action="" method="post">
    <table class="absp-table">
        <thead>
            <tr>
                <th>番号</th>
                <th>ラベル</th>
                <th>TECH</th>
                <th>トランク</th>
                <th>種別</th>
                <th>発信CID</th>
                <th>着信</th>
                <th>RING</th>
                <th>割込PIN</th>
                <th>割込MD</th>
                <th>操作</th>
                <th>状態</th>
            </tr>
        </thead>
        <tbody>
            <?php for($i=1; $i<=$max_keys; $i++): ?>
                <?php
                $key_info = AbspFunctions\get_key_info($i);
                $label = $key_info['label'] ?? '';
                $tech = $key_info['tech'] ?? 'PJSIP';
                $trunk = $key_info['trunk'] ?? '';
                $ktype = $key_info['type'] ?? '';
                $ogcid = $key_info['ogcid'] ?? '';
                $rgrp = $key_info['rgrp'] ?? '';
                $rgpt = $key_info['rgpt'] ?? '0';
                $bpin = $key_info['bpin'] ?? '';
                $mmd = $key_info['mmd'] ?? 'B';
                $msg = $notice_msg[$i] ?? '';
                
                $selectors = AbspFunctions\create_target_list('group', $rgrp);
                $trunk_options = AbspFunctions\create_trunk_list('', $trunk);
                ?>
                <tr>
                    <td style="text-align: right;">KEY<?= $i ?></td>
                    <td><input type="text" name="label[<?= $i ?>]" value="<?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>" class="input-short"></td>
                    <td>
                        <select name="tech[<?= $i ?>]" class="input-em6">
                            <option value="PJSIP" <?= ($tech == 'PJSIP') ? 'selected' : '' ?>>PJSIP</option>
                            <option value="SIP" <?= ($tech == 'SIP') ? 'selected' : '' ?>>SIP</option>
                        </select>
                    </td>
                    <td>
                        <?php if($trunk == '' || strpos($trunk_options, $trunk) !== false): ?>
                            <select name="trunk[<?= $i ?>]" class="input-short2">
                                <?= $trunk_options ?>
                            </select>
                        <?php else: ?>
                            <input type="text" name="trunk[<?= $i ?>]" value="<?= htmlspecialchars($trunk, ENT_QUOTES, 'UTF-8') ?>" class="input-short2">
                        <?php endif; ?>
                    </td>
                    <td>
                        <select name="key_type[<?= $i ?>]" class="input-em6">
                            <option value="" <?= ($ktype == '') ? 'selected' : '' ?>>なし</option>
                            <option value="NTTE" <?= ($ktype == 'NTTE') ? 'selected' : '' ?>>NTT東</option>
                            <option value="NTTW" <?= ($ktype == 'NTTW') ? 'selected' : '' ?>>NTT西</option>
                            <option value="BASIX" <?= ($ktype == 'BASIX') ? 'selected' : '' ?>>BASIX</option>
                            <option value="UAREA" <?= ($ktype == 'UAREA') ? 'selected' : '' ?>>ユーザ定義</option>
                        </select>
                    </td>
                    <td><input type="text" class="input-middle2" name="ogcid[<?= $i ?>]" value="<?= htmlspecialchars($ogcid, ENT_QUOTES, 'UTF-8') ?>"></td>
                    <td>
                        <select name="rgrp[<?= $i ?>]" class="input-short">
                            <?= $selectors ?>
                        </select>
                    </td>
                    <td>
                        <select name="rgpt[<?= $i ?>]" class="input-short">
                            <?php for($r = 0; $r <= 5; $r++): ?>
                            <option value="<?= $r ?>" <?= ($rgpt == $r) ? 'selected' : '' ?>><?= $r ?></option>
                            <?php endfor; ?>
                        </select>
                    </td>
                    <td><input type="text" name="bpin[<?= $i ?>]" value="<?= htmlspecialchars($bpin, ENT_QUOTES, 'UTF-8') ?>" class="input-xshort"></td>
                    <td>
                        <select name="mmd[<?= $i ?>]" class="input-xshort">
                            <option value="B" <?= ($mmd == 'B') ? 'selected' : '' ?>>Bin</option>
                            <option value="S" <?= ($mmd == 'S') ? 'selected' : '' ?>>Spy</option>
                        </select>
                    </td>
                    <td>
                        <button type="submit" name="update_key[<?= $i ?>]" value="save" class="btn btn-row">設定</button>
                    </td>
                    <td class="notice-message">
                        <?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?>
                    </td>
                </tr>
            <?php endfor; ?>
        </tbody>
    </table>
    
<?php //    <div style="text-align: right; margin-top: 10px;"> ?>
<?php //        <button type="submit" name="update_all_keys" value="save_all" class="btn btn-primary">キーシステム設定をすべて保存</button> ?>
<?php //   </div> ?>
</form>
</div>

<hr>

<h3>手動トランク設定</h3>
<form action="" method="post">
    <div class="form-inline-group">
        <label for="keynum_select">キー:</label>
        <select id="keynum_select" name="keynum">
            <?php for($i=1;$i<=32;$i++): ?>
                <option value="<?= $i ?>">KEY<?= $i ?></option>
            <?php endfor; ?>
        </select>

        <label for="trunk_name_input">トランク名:</label>
        <input type="text" id="trunk_name_input" name="trunk" class="input-short">
        
        <input type="hidden" name="function" value="trunkadd">
        <input type="submit" class="btn" value="設定">
    </div>
</form>
