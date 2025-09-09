<?php
if (!defined('ABS_PANEL_INCLUDED')) {
    die("Direct access is not permitted.");
}

// POST時処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $dp_msg = '';
    $notice_msg = []; // 配列として初期化

    if (isset($_POST['update_peer'])) {
        $p_peer = $_POST['update_peer']; //押されたボタンのvalueからピア名を取得

        // その行のデータを取得
        $p_exten = $_POST['exten'][$p_peer] ?? '';
        $p_p_exten = $_POST['p_exten'][$p_peer] ?? '';
        $p_limit = $_POST['limit'][$p_peer] ?? '0';
        $p_ogcid = $_POST['ogcid'][$p_peer] ?? '';
        $p_pgrp = $_POST['pgrp'][$p_peer] ?? '';
        $p_macadd = trim($_POST['macadd'][$p_peer] ?? '');

        $peer_info_set = [ 'peer' => $p_peer, 'exten' => $p_exten, 'p_exten' => $p_p_exten, 'limit' => $p_limit, 'ogcid' => $p_ogcid, 'pgrp' => $p_pgrp ];

        // FDとの重複チェック
        $e_exists = false;
        if (trim($p_exten) !== '') {
            $entry = AbspFunctions\get_db_family('ABS/FAP/UID');
            if (is_array($entry)) {
                foreach ($entry as $line) {
                    list($uid, $ent) = explode('/', $line, 2);
                    list($cat, $val) = explode(':', $ent, 2);
                    if (trim($cat) == 'EXT' && trim($val) == $p_exten) { $e_exists = true; break; }
                }
            }
        }
        if ($e_exists) {
            $notice_msg[$p_peer] = "内線番号重複(FD)";
        } else {
            $notice_msg[$p_peer] = AbspFunctions\set_peer_info($peer_info_set);
            // MACアドレスの処理は個別保存の場合も実行
            if ($p_macadd != '') {
                // ... (MACアドレス整形ロジック) ...
                AbspFunctions\put_db_item("ABS/PINFO/$p_peer", 'MAC', strtoupper($p_macadd));
            } else {
                AbspFunctions\del_db_item("ABS/PINFO/$p_peer", 'MAC');
            }
        }
    }
    // 一括保存ボタンが押された場合
    elseif (isset($_POST['update_all_peers'])) {
        $p_extens = $_POST['exten'] ?? [];

        // 1. 既存のFD内線リストを取得
        $fd_extens = [];
        $entry = AbspFunctions\get_db_family('ABS/FAP/UID');
        if (is_array($entry)) {
            foreach ($entry as $line) {
                list($uid, $ent) = explode('/', $line, 2);
                list($cat, $val) = explode(':', $ent, 2);
                if (trim($cat) == 'EXT') {
                    $fd_extens[] = trim($val); // FD内線番号を配列に格納
                }
            }
        }

        // 2. 今回POSTされたデータ内での重複をチェック
        // 空の入力はチェック対象外にする
        $non_empty_extens = array_filter($p_extens, function($val) {
            return trim($val) !== '';
        });

        // 各内線番号の出現回数をカウント
        $exten_counts = array_count_values($non_empty_extens);
        $self_duplicates = [];
        foreach ($exten_counts as $exten => $count) {
            if ($count > 1) {
                $self_duplicates[] = $exten; // 2回以上出現した番号を重複リストに追加
            }
        }

        // (一括保存の場合も、同様に重複チェックや保存処理をループで行う)
        foreach ($p_extens as $p_peer => $p_exten) {
            $trimmed_exten = trim($p_exten);
            if ($trimmed_exten === '') {
                $p_p_exten = $_POST['p_exten'][$p_peer] ?? '';
                $p_limit = $_POST['limit'][$p_peer] ?? '0';
                $p_ogcid = $_POST['ogcid'][$p_peer] ?? '';
                $p_pgrp = $_POST['pgrp'][$p_peer] ?? '';
                $p_macadd = trim($_POST['macadd'][$p_peer] ?? '');
            
                $peer_info_set = [ 'peer' => $p_peer, 'exten' => $p_exten, 'p_exten' => $p_p_exten, 'limit' => $p_limit, 'ogcid' => $p_ogcid, 'pgrp' => $p_pgrp ];
            
                $notice_msg[$p_peer] = AbspFunctions\set_peer_info($peer_info_set);
		continue;
            }

            // チェック実行
            if (in_array($trimmed_exten, $self_duplicates)) {
                $notice_msg[$p_peer] = "一括保存内で重複";
                continue; // 重複しているので保存せず、次の行へ
            }

            if (in_array($trimmed_exten, $fd_extens)) {
                $notice_msg[$p_peer] = "内線番号重複(FD)";
                continue; // 重複しているので保存せず、次の行へ
            }
            // 重複がなかった場合のみ、保存処理を実行
            $p_p_exten = $_POST['p_exten'][$p_peer] ?? '';
            $p_limit = $_POST['limit'][$p_peer] ?? '0';
            $p_ogcid = $_POST['ogcid'][$p_peer] ?? '';
            $p_pgrp = $_POST['pgrp'][$p_peer] ?? '';
            $p_macadd = trim($_POST['macadd'][$p_peer] ?? '');
            
            $peer_info_set = [ 'peer' => $p_peer, 'exten' => $p_exten, 'p_exten' => $p_p_exten, 'limit' => $p_limit, 'ogcid' => $p_ogcid, 'pgrp' => $p_pgrp ];
            
            $notice_msg[$p_peer] = AbspFunctions\set_peer_info($peer_info_set);

            if ($p_macadd != '') {
                // ... (MACアドレス整形ロジック) ...
                AbspFunctions\put_db_item("ABS/PINFO/$p_peer", 'MAC', strtoupper($p_macadd));
            } else {
                AbspFunctions\del_db_item("ABS/PINFO/$p_peer", 'MAC');
            }
        }
    }
    // 鳴動パターンなど、他のフォームの処理
    elseif (isset($_POST['function'])) {
        if ($_POST['function'] == 'rgptset') {
            AbspFunctions\put_db_item('ABS/EXTOPT', 'RGPT', $_POST['rgpt']);
        }
        if ($_POST['function'] == 'dpset') {
            if ($_POST['dpdest'] != "") {
                AbspFunctions\put_db_item('ABS/DOOR', 'RGPT', $_POST['dprgpt']);
                AbspFunctions\put_db_item('ABS/DOOR', 'RING', $_POST['dpdest']);
                AbspFunctions\put_db_item('ABS/DOOR', 'CID', $_POST['dpcid']);
                AbspFunctions\put_db_item('ABS/DOOR', 'CIDN', $_POST['dpcidn']);
            }
        }
    }

    $_SESSION['notice_msg'] = $notice_msg;

    header('Location: index.php?page=ext-config-page');
    exit;
}

// GET時処理
$notice_msg = $_SESSION['notice_msg'] ?? [];
$dp_msg = $_SESSION['dp_msg'] ?? '';

unset($_SESSION['notice_msg']);
unset($_SESSION['dp_msg']);

?>
<h2>内線情報設定</h2>

<div class="table-container">
<form action="" method="post">
    <table class="absp-table">
        <thead>
            <tr>
                <th>エンドポイント</th>
                <th>内線番号</th>
                <th>規制値</th>
                <th>発信CID</th>
                <th>PickUp</th>
                <th>MACアドレス</th>
                <th>操作</th>
                <th>状態</th>
            </tr>
        </thead>
        <tbody>
            <?php for ($i = 1; $i <= $max_sip_phones; $i++): ?>
                <?php
                $peer_info = AbspFunctions\get_peer_info("phone$i");
                $peer = $peer_info['peer'];
                $exten = $peer_info['exten'] ?? '';
                $limit_val = $exten != '' ? ($peer_info['limit'] ?? '0') : '0';
                $ogcid = $exten != '' ? ($peer_info['ogcid'] ?? '') : '';
                $pgrp = $exten != '' ? ($peer_info['pgrp'] ?? '') : '';
                $macadd = AbspFunctions\get_db_item("ABS/PINFO/$peer", 'MAC');
                $n_msg = $notice_msg[$peer] ?? '';
                $br_ind = ($i >= $brphone_min && $i <= $brphone_max) ? "(B)" : "";
                ?>
                <tr>
                    <td>
                        <?= htmlspecialchars("phone{$i} {$br_ind}", ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td>
                        <input type="text" class="input-short" name="exten[<?= htmlspecialchars($peer, ENT_QUOTES, 'UTF-8') ?>]" value="<?= htmlspecialchars($exten, ENT_QUOTES, 'UTF-8') ?>">
                    </td>
                    <td>
                        <select class="input-xshort" name="limit[<?= htmlspecialchars($peer, ENT_QUOTES, 'UTF-8') ?>]">
                            <?php for ($l = 0; $l <= 3; $l++): ?>
                            <option value="<?= $l ?>" <?= ($limit_val == $l) ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endfor; ?>
                        </select>
                    </td>
                    <td>
                        <input type="text" class="input-short" name="ogcid[<?= htmlspecialchars($peer, ENT_QUOTES, 'UTF-8') ?>]" value="<?= htmlspecialchars($ogcid, ENT_QUOTES, 'UTF-8') ?>">
                    </td>
                    <td>
                        <input type="text" class="input-xshort" name="pgrp[<?= htmlspecialchars($peer, ENT_QUOTES, 'UTF-8') ?>]" value="<?= htmlspecialchars($pgrp, ENT_QUOTES, 'UTF-8') ?>">
                    </td>
                    <td>
                        <input type="text" class="input-middle" name="macadd[<?= htmlspecialchars($peer, ENT_QUOTES, 'UTF-8') ?>]" value="<?= htmlspecialchars($macadd, ENT_QUOTES, 'UTF-8') ?>">
                    </td>
                    <td>
                        <input type="hidden" name="p_exten[<?= htmlspecialchars($peer, ENT_QUOTES, 'UTF-8') ?>]" value="<?= htmlspecialchars($exten, ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" name="update_peer" value="<?= htmlspecialchars($peer, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-row">設定</button>
                    </td>
                    <td class="notice-message">
                        <?= htmlspecialchars($n_msg, ENT_QUOTES, 'UTF-8') ?>
                    </td>
                </tr>
            <?php endfor; ?>
        </tbody>
    </table>

    <div style="text-align: right; margin-top: 10px;">
        <button type="submit" name="update_all_peers" value="save_all" class="btn btn-primary">内線情報設定をすべて保存</button>
    </div>
</form>
</div>

<p style="font-size: 0.9em;">
    MACアドレスは電話機設定ファイル自動生成に使用されます<br>
    (B)はブラウザフォン用のエンドポイントです
</p>

<h3>内線時鳴動パターン</h3>
<?php
    $rgpt_selected = array('0'=>'', '1'=>'', '2'=>'', '3'=>'', '4'=>'', '5'=>'');
    $rgpt = AbspFunctions\get_db_item('ABS/EXTOPT', 'RGPT');
    if($rgpt == '') $rgpt = '0';
    $rgpt_selected["$rgpt"] = "selected";
?>
<form action="" method="post">
    <select name="rgpt" class="input-xshort">
        <?php for ($r = 0; $r <= 5; $r++): ?>
        <option value="<?= $r ?>" <?= ($rgpt == $r) ? 'selected' : '' ?>><?= $r ?></option>
        <?php endfor; ?>
    </select>
    <input type="hidden" name="function" value="rgptset">
    <input type="submit" class="btn" value="設定">
</form>

<h3>ドアホン(受付電話)設定</h3>
<?php
//ドアホン着信先
    $dpdest = AbspFunctions\get_db_item('ABS/DOOR', 'RING');
    $dpcid  = AbspFunctions\get_db_item('ABS/DOOR', 'CID');
    $dpcidn = AbspFunctions\get_db_item('ABS/DOOR', 'CIDN');

//ドアホン鳴動パターン
    $dprgpt_selected = array('0'=>'', '1'=>'', '2'=>'', '3'=>'', '4'=>'', '5'=>'');
    $dprgpt = AbspFunctions\get_db_item('ABS/DOOR', 'RGPT');
    if($dprgpt == '') $rgpt = '0';
    $dprgpt_selected["$dprgpt"] = "selected";
?>
<form action="" method="post">
    <div class="form-inline-group">
        <label for="dpdest">着信先：</label>
        <input type="text" class="input-short" id="dpdest" name="dpdest" value="<?= htmlspecialchars($dpdest, ENT_QUOTES, 'UTF-8') ?>" >

        <label for="dprgpt">鳴動パターン:</label>
        <select id="dprgpt" name="dprgpt" class="input-xshort">
            <?php for ($r = 0; $r <= 5; $r++): ?>
            <option value="<?= $r ?>" <?= ($dprgpt == $r) ? 'selected' : '' ?>><?= $r ?></option>
            <?php endfor; ?>
        </select>

        <label for="dpcidn">発信者名：</label>
        <input type="text" id="dpcidn" name="dpcidn" value="<?= htmlspecialchars($dpcidn, ENT_QUOTES, 'UTF-8') ?>" class="input-short">

        <label for="dpcid">発信者番号：</label>
        <input type="text" id="dpcid" name="dpcid" value="<?= htmlspecialchars($dpcid, ENT_QUOTES, 'UTF-8') ?>" class="input-short">

        <input type="submit" class="btn" value="設定">
    </div>

    <input type="hidden" name="function" value="dpset">
    <?= htmlspecialchars($dp_msg, ENT_QUOTES, 'UTF-8') ?>
    <br>
    <small>(グループ着信時はGを付けてください)</small>
</form>
