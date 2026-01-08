<?php
/**
 * ext-config-page.php
 */

if (!defined('ABS_PANEL_INCLUDED')) {
    die("Direct access is not permitted.");
}

global $ami;
global $max_sip_phones;     // config.php等で定義
global $brphone_min, $brphone_max;

// POST時処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $dp_msg = '';
    $notice_msg = [];

    // --- 個別更新処理 (name属性を update_endpoint に変更) ---
    if (isset($_POST['update_endpoint'])) {

        $p_endpoint_name = $_POST['update_endpoint'];
        
        // DB保存用のキー (例: PJSIP/phone1)
        $p_endpoint_key = "PJSIP/" . $p_endpoint_name;

        // その行のデータを取得 (キー変数も変更)
        $p_exten   = $_POST['exten'][$p_endpoint_name] ?? '';
        $p_p_exten = $_POST['p_exten'][$p_endpoint_name] ?? ''; // hidden値(変更前の内線番号)
        $p_limit   = $_POST['limit'][$p_endpoint_name] ?? '0';
        $p_ogcid   = $_POST['ogcid'][$p_endpoint_name] ?? '';
        $p_pgrp    = $_POST['pgrp'][$p_endpoint_name] ?? '';
        $p_macadd  = trim($_POST['macadd'][$p_endpoint_name] ?? '');

        // クラスメソッド用の配列を作成
        $endpoint_info_set = [ 
            'endpoint' => $p_endpoint_key, // PJSIP/phoneX
            'exten'    => $p_exten, 
            'p_exten'  => $p_p_exten, 
            'limit'    => $p_limit, 
            'ogcid'    => $p_ogcid, 
            'pgrp'     => $p_pgrp 
        ];

        // FD(Flexible Dialing)との重複チェック
        $e_exists = false;
        if (trim($p_exten) !== '') {
            $entry = $ami->getFamilyDB('ABS/FAP/UID');
            if (is_array($entry)) {
                foreach ($entry as $line) {
                    // データ形式: "1001/EXT : 501"
            
                    // 1. コロンで「キー部分」と「値部分」に分割
                    $parts = explode(':', $line, 2);
                    if(count($parts) < 2) continue;
            
                    $key_part = trim($parts[0]); // "1001/EXT"
                    $val_part = trim($parts[1]); // "501" (内線番号)

                    // 2. キー部分をスラッシュで分割してカテゴリ(EXT)を抽出
                    $key_segments = explode('/', $key_part);
                    if(count($key_segments) < 2) continue; // UID/CAT 形式でなければスキップ

                    $cat = trim($key_segments[1]); // "EXT"

                    // 3. カテゴリがEXT、かつ内線番号が一致するか判定
                     if ($cat == 'EXT' && $val_part == $p_exten) { 
                        $e_exists = true; 
                        break; 
                     }
                }
            }
        }

        if ($e_exists) {
            $notice_msg[$p_endpoint_name] = "内線番号重複(FD)";
        } else {
            // 保存実行
            $notice_msg[$p_endpoint_name] = $ami->setEndpointInfo($endpoint_info_set);

            // MACアドレスの処理 (物理名 phone1 に紐付け)
            if ($p_macadd != '') {
                $valid_mac = $ami->normalizeMacAddress($p_macadd);
    
                if ($valid_mac) {
                    $ami->putDbItem("ABS/PINFO/$p_endpoint_name", 'MAC', $valid_mac);
                } else {
                    $notice_msg[$p_endpoint_name] .= " [MAC形式エラー]";
                }
            } else {
                $ami->delDbItem("ABS/PINFO/$p_endpoint_name", 'MAC');
            }
        }
    }
    // --- 一括保存処理 (name属性を update_all に変更) ---
    elseif (isset($_POST['update_all'])) {

        $p_extens = $_POST['exten'] ?? [];

        // 1. 既存のFD内線リストを取得
        $fd_extens = [];
        $entry = $ami->getFamilyDB('ABS/FAP/UID');
        if (is_array($entry)) {
            foreach ($entry as $line) {
                $parts = explode(':', $line, 2);
                if(count($parts) < 2) continue;
                
                $key_part = trim($parts[0]); // "1001/EXT"
                $val_part = trim($parts[1]); // "501" (内線番号)
                // 2. キー部分をスラッシュで分割してカテゴリ(EXT)を抽出
                $key_segments = explode('/', $key_part);
                if(count($key_segments) < 2) continue; // UID/CAT 形式でなければスキップ

                $cat = trim($key_segments[1]); // "EXT"
                
                if (trim($cat) == 'EXT') {
                    $fd_extens[] = trim($val_part);
                }
            }
        }

        // 2. 重複チェック用リスト作成
        $non_empty_extens = array_filter($p_extens, function($val) {
            return trim($val) !== '';
        });

        $exten_counts = array_count_values($non_empty_extens);
        $self_duplicates = [];
        foreach ($exten_counts as $exten => $count) {
            if ($count > 1) {
                $self_duplicates[] = $exten;
            }
        }

        // 一括ループ処理 (変数を $p_endpoint_name に変更)
        foreach ($p_extens as $p_endpoint_name => $p_exten) {
            
            // PJSIP対応
            $p_endpoint_key = "PJSIP/" . $p_endpoint_name;

            $trimmed_exten = trim($p_exten);
            
            // 共通パラメータ取得
            $p_p_exten = $_POST['p_exten'][$p_endpoint_name] ?? '';
            $p_limit   = $_POST['limit'][$p_endpoint_name] ?? '0';
            $p_ogcid   = $_POST['ogcid'][$p_endpoint_name] ?? '';
            $p_pgrp    = $_POST['pgrp'][$p_endpoint_name] ?? '';
            $p_macadd  = trim($_POST['macadd'][$p_endpoint_name] ?? '');
            
            $endpoint_info_set = [ 
                'endpoint' => $p_endpoint_key, 
                'exten'    => $p_exten, 
                'p_exten'  => $p_p_exten, 
                'limit'    => $p_limit, 
                'ogcid'    => $p_ogcid, 
                'pgrp'     => $p_pgrp 
            ];

            // 空入力の場合は削除
            if ($trimmed_exten === '') {
                $notice_msg[$p_endpoint_name] = $ami->setEndpointInfo($endpoint_info_set);
                $ami->delDbItem("ABS/PINFO/$p_endpoint_name", 'MAC');
                continue;
            }

            // 重複チェック
            if (in_array($trimmed_exten, $self_duplicates)) {
                $notice_msg[$p_endpoint_name] = "一括保存内で重複";
                continue;
            }

            if (in_array($trimmed_exten, $fd_extens)) {
                $notice_msg[$p_endpoint_name] = "内線番号重複(FD)";
                continue;
            }

            // 保存実行
            $notice_msg[$p_endpoint_name] = $ami->setEndpointInfo($endpoint_info_set);

            // MAC保存
            if ($p_macadd != '') {
                $ami->putDbItem("ABS/PINFO/$p_endpoint_name", 'MAC', strtoupper($p_macadd));
            } else {
                $ami->delDbItem("ABS/PINFO/$p_endpoint_name", 'MAC');
            }
        }
    }
    // --- その他の設定 ---
    elseif (isset($_POST['function'])) {

        if ($_POST['function'] == 'rgptset') {
            $ami->putDbItem('ABS/EXTOPT', 'RGPT', $_POST['rgpt']);
        }
        if ($_POST['function'] == 'dpset') {
            if ($_POST['dpdest'] != "") {
                $ami->putDbItem('ABS/DOOR', 'RGPT', $_POST['dprgpt']);
                $ami->putDbItem('ABS/DOOR', 'RING', $_POST['dpdest']);
                $ami->putDbItem('ABS/DOOR', 'CID',  $_POST['dpcid']);
                $ami->putDbItem('ABS/DOOR', 'CIDN', $_POST['dpcidn']);
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

<h2>内線情報設定 (PJSIP)</h2>

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
                $endpoint_name = "phone{$i}"; 
                $endpoint_key  = "PJSIP/" . $endpoint_name; // DB検索用

                // 情報を取得
                $endpoint_info = $ami->getEndpointInfo($endpoint_key);
                
                $exten = $endpoint_info['exten'] ?? '';
                $limit_val = $exten != '' ? ($endpoint_info['limit'] ?? '0') : '0';
                $ogcid = $exten != '' ? ($endpoint_info['ogcid'] ?? '') : '';
                $pgrp  = $exten != '' ? ($endpoint_info['pgrp']  ?? '') : '';
                
                // MAC取得 (テクノロジなしのキーを使用)
                $raw_mac = $ami->getDbItem("ABS/PINFO/$endpoint_name", 'MAC');
                $macadd_display = $ami->formatMacAddress($raw_mac);
                
                $n_msg = $notice_msg[$endpoint_name] ?? '';
                $br_ind = (isset($brphone_min) && $i >= $brphone_min && $i <= $brphone_max) ? "(B)" : "";
                ?>
                <tr>
                    <td>
                        <?= htmlspecialchars("{$endpoint_name} {$br_ind}", ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td>
                        <input type="text" class="input-short" name="exten[<?= htmlspecialchars($endpoint_name, ENT_QUOTES, 'UTF-8') ?>]" value="<?= htmlspecialchars($exten, ENT_QUOTES, 'UTF-8') ?>">
                    </td>
                    <td>
                        <select class="input-xshort" name="limit[<?= htmlspecialchars($endpoint_name, ENT_QUOTES, 'UTF-8') ?>]">
                            <?php for ($l = 0; $l <= 3; $l++): ?>
                            <option value="<?= $l ?>" <?= ($limit_val == $l) ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endfor; ?>
                        </select>
                    </td>
                    <td>
                        <input type="text" class="input-xmiddle" name="ogcid[<?= htmlspecialchars($endpoint_name, ENT_QUOTES, 'UTF-8') ?>]" value="<?= htmlspecialchars($ogcid, ENT_QUOTES, 'UTF-8') ?>">
                    </td>
                    <td>
                        <input type="text" class="input-xshort" name="pgrp[<?= htmlspecialchars($endpoint_name, ENT_QUOTES, 'UTF-8') ?>]" value="<?= htmlspecialchars($pgrp, ENT_QUOTES, 'UTF-8') ?>">
                    </td>
                    <td>
                        <input type="text" class="input-middle" name="macadd[<?= htmlspecialchars($endpoint_name, ENT_QUOTES, 'UTF-8') ?>]" value="<?= htmlspecialchars($macadd_display, ENT_QUOTES, 'UTF-8') ?>">
                    </td>
                    <td>
                        <input type="hidden" name="p_exten[<?= htmlspecialchars($endpoint_name, ENT_QUOTES, 'UTF-8') ?>]" value="<?= htmlspecialchars($exten, ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" name="update_endpoint" value="<?= htmlspecialchars($endpoint_name, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-row">設定</button>
                    </td>
                    <td class="notice-message">
                        <?= htmlspecialchars($n_msg, ENT_QUOTES, 'UTF-8') ?>
                    </td>
                </tr>
            <?php endfor; ?>
        </tbody>
    </table>

    <div style="text-align: right; margin-top: 10px;">
        <button type="submit" name="update_all" value="save_all" class="btn btn-primary">内線情報設定をすべて保存</button>
    </div>
</form>
</div>

<p style="font-size: 0.9em;">
    MACアドレスは電話機設定ファイル自動生成に使用されます。<br>
    (B)はブラウザフォン用のエンドポイントです。<br>
    ※保存時はシステム内部で自動的に <b>PJSIP/phoneX</b> として登録されます。
</p>

<h3>内線時鳴動パターン</h3>
<?php
    $rgpt = $ami->getDbItem('ABS/EXTOPT', 'RGPT');
    if($rgpt == '') $rgpt = '0';
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
    $dpdest = $ami->getDbItem('ABS/DOOR', 'RING');
    $dpcid  = $ami->getDbItem('ABS/DOOR', 'CID');
    $dpcidn = $ami->getDbItem('ABS/DOOR', 'CIDN');
    $dprgpt = $ami->getDbItem('ABS/DOOR', 'RGPT');
    if($dprgpt == '') $dprgpt = '0';
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
