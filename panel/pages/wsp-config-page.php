<?php
/**
 * wsp-config-page.php
 * * WebSocket内線設定ページ
 */

if (!defined('ABS_PANEL_INCLUDED')) {
    die("Direct access is not permitted.");
}

global $ami;

// 最大スロット数定義
$max_wsp_slots = isset($max_wsp_slots) ? $max_wsp_slots : 32;

// POST処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $notice_msg = [];
    $global_msg = '';

    // 1. URI・プレフィクス設定の保存
    if (isset($_POST['update_global'])) {
        $uri = trim($_POST['wsp_uri'] ?? '');
        $prefix = trim($_POST['wsp_prefix'] ?? '');
        $dial_arg = trim($_POST['wsp_dial_arg'] ?? '');
        
        $ami->putDbItem('ABS/WSP', 'URI', $uri);
        $ami->putDbItem('ABS/WSP', 'EPFX', $prefix);
        $ami->putDbItem('ABS/WSP', 'DARG', $dial_arg);
        
        $global_msg = '基本設定を保存しました。';
    }

    // 2. 設定ファイル生成 (CLIコマンド実行)
    // AsteriskのDialplanをキックして設定ファイルを再生成させる
    elseif (isset($_POST['gen_config'])) {
        $cmd = 'channel originate Local/s@sub-wsp-gen application noop';
        $ami->execCliCommand($cmd);
        
        $global_msg = '設定ファイル生成コマンドを実行しました';
    }

    // 3. 個別内線設定の保存
    elseif (isset($_POST['update_wsp'])) {
        $slot_id = $_POST['update_wsp']; // 例: WSP01
        
        $new_exten = trim($_POST['exten'][$slot_id] ?? '');
        $limit = $_POST['limit'][$slot_id] ?? '0';
        $ogcid = $_POST['ogcid'][$slot_id] ?? '';

        // 現在のマッピングを取得
        $current_exten = $ami->getDbItem('ABS/WSP/MAP', $slot_id);

        $is_duplicate = false;
        $dup_reason = "";

        if ($new_exten !== '') {
            // 【チェック1】FD(FreeDesk)内線との重複チェック
            $fap_entries = $ami->getFamilyDB('ABS/FAP/UID');
            if (is_array($fap_entries)) {
                foreach ($fap_entries as $line) {
                    // "UID/KEY : VALUE" 形式をパース
                    $parts = explode('/', $line, 2);
                    if (count($parts) < 2) continue;
                    
                    $remain = $parts[1]; // KEY : VALUE
                    list($cat_part, $val_part) = explode(':', $remain, 2);
                    
                    if (trim($cat_part) == 'EXT' && trim($val_part) == $new_exten) {
                        $is_duplicate = true;
                        $dup_reason = "内線番号重複(FD)";
                        break;
                    }
                }
            }

            // 【チェック2】通常内線(phoneX) および 他のWSPとの重複チェック
            if (!$is_duplicate) {
                // ABS/EXT を引いて、既に誰かが使っていないか確認
                $existing_endpoint_name = $ami->getDbItem('ABS/EXT', $new_exten);
                
                if ($existing_endpoint_name !== '') {
                    // A. 通常内線 (phoneX) が使っている場合 (PJSIP/phoneX または phoneX)
                    if (strpos($existing_endpoint_name, 'phone') !== false) {
                        $is_duplicate = true;
                        $dup_reason = "内線番号重複(通常内線)";
                    }
                    // B. 他のWSPスロットやその他のエンドポイントが使っている場合
                    else {
                        // ABS/WSP/MAP を全検索して、別のスロットがこの番号を持っていないか確認
                        $wsp_map = $ami->getFamilyDB('ABS/WSP/MAP');
                        if (is_array($wsp_map)) {
                            foreach ($wsp_map as $line) {
                                // line format: "WSP01 : 2001" (AbspManagerの仕様による)
                                $parts = explode(':', $line, 2);
                                if (count($parts) < 2) continue;

                                $mapped_slot = trim($parts[0]);
                                $val_ext = trim($parts[1]);
                                
                                // 番号が一致し、かつ自分(slot_id)ではない場合
                                if ($val_ext == $new_exten && $mapped_slot !== $slot_id) {
                                    $is_duplicate = true;
                                    $dup_reason = "内線番号重複($mapped_slot)";
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($is_duplicate) {
            $notice_msg[$slot_id] = $dup_reason;
        } else {
            // 新しい設定の保存
            if ($new_exten !== '') {
                $new_endpoint_name = "WSP" . $new_exten;

                //注：内線(EXT)と逆引き(ERV)はAsteriskが生成するのでここでは
                //    マッピング情報のみ更新
                // スロットと内線番号のマッピング保存
                $ami->putDbItem('ABS/WSP/MAP', $slot_id, $new_exten);

                // 規制値・CID: もマッピングに保存
                $ami->putDbItem("ABS/WSP/MAP/". $slot_id, 'LIMIT', $limit);
                if ($ogcid !== '') {
                    $ami->putDbItem("ABS/WSP/MAP/" . $slot_id, 'OGCID', $ogcid);
                } else {
                    $ami->delDbItem("ABS/WSP/MAP/" . $slot_id, 'OGCID');
                }
                
                $notice_msg[$slot_id] = "設定完了: EP=$new_endpoint_name";
            } else {
                // 入力が空の場合は設定解除のみ実行済み
                $notice_msg[$slot_id] = "設定解除";
            }
        }
    }

    $_SESSION['wsp_notice'] = $notice_msg;
    $_SESSION['wsp_global_msg'] = $global_msg;
    
    // リダイレクトして再読み込み
    header('Location: index.php?page=wsp-config-page');
    exit;
}

// GET時: データ取得
$notice_msg = $_SESSION['wsp_notice'] ?? [];
$global_msg = $_SESSION['wsp_global_msg'] ?? '';
unset($_SESSION['wsp_notice']);
unset($_SESSION['wsp_global_msg']);

// 基本設定の取得
$wsp_uri = $ami->getDbItem('ABS/WSP', 'URI');
$wsp_prefix = $ami->getDbItem('ABS/WSP', 'EPFX');
$wsp_dial_arg = $ami->getDbItem('ABS/WSP', 'DARG');

?>

<h2>WebSocket内線設定</h2>

            <?php if ($global_msg): ?>
                <span style="color: var(--primary-color); margin-left: 10px; font-weight: bold;"><?= htmlspecialchars($global_msg, ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
<div style="margin-bottom: 20px; padding: 15px; background-color: var(--surface-color); border: 1px solid var(--border-color); border-radius: 4px;">
    <form action="" method="post" class="form-inline-group" style="justify-content: space-between;">
        <div class="form-inline-group">
            <label>URI:</label>
            <input type="text" name="wsp_uri" value="<?= htmlspecialchars($wsp_uri, ENT_QUOTES, 'UTF-8') ?>" placeholder="ws://localhost:8088/ws" class="input-middle">
            
            <label style="margin-left: 10px;">プレフィクス:</label>
            <input type="text" name="wsp_prefix" value="<?= htmlspecialchars($wsp_prefix, ENT_QUOTES, 'UTF-8') ?>" placeholder="ex: wsp-ext-" class="input-short2">

            <label style="margin-left: 10px;">ダイヤルオプション:</label>
            <input type="text" name="wsp_dial_arg" value="<?= htmlspecialchars($wsp_dial_arg, ENT_QUOTES, 'UTF-8') ?>" placeholder="ex: c(slin16)n" class="input-middle">
            
            <button type="submit" name="update_global" value="1" class="btn">設定</button>
            
        </div>

    </form>
</div>
        
<div style="margin-bottom: 20px; padding: 15px; background-color: var(--surface-color); border: 1px solid var(--border-color); border-radius: 4px;">
    <form action="" method="post" class="form-inline-group" style="justify-content: space-between;">
        <div>
            内線情報設定後Asteriskに設定ファイルを生成させます : 
            <button type="submit" name="gen_config" value="1" class="btn btn-primary">設定ファイル生成</button>
        </div>
    </form>
</div>

<hr style="border: 0; border-top: 1px solid var(--border-color); margin: 20px 0;">

<div class="table-container">
    <form action="" method="post">
        <table class="absp-table">
            <thead>
                <tr>
                    <th>識別名</th>
                    <th>内線番号</th>
                    <th>規制値</th>
                    <th>発信CID</th>
                    <th>操作</th>
                    <th>状態</th>
                </tr>
            </thead>
            <tbody>
                <?php for ($i = 1; $i <= $max_wsp_slots; $i++): ?>
                    <?php
                    $slot_id = "WSP" . sprintf('%02d', $i); // WSP01, WSP02...
                    
                    // 現在のマッピングから内線番号を取得
                    $exten = $ami->getDbItem('ABS/WSP/MAP', $slot_id);
                    
                    // 現在のマッピングから情報を取得
                    $limit_val = '0';
                    $ogcid_val = '';
                    
                    if ($exten !== '') {
                        $limit_val = $ami->getDbItem("ABS/WSP/MAP/" . $slot_id, 'LIMIT');
                        if ($limit_val === '') $limit_val = '0';
                        $ogcid_val = $ami->getDbItem("ABS/WSP/MAP/" . $slot_id, 'OGCID');
                    }
                    
                    $msg = $notice_msg[$slot_id] ?? '';
                    ?>
                    <tr>
                        <td>
                            <b><?= htmlspecialchars($slot_id, ENT_QUOTES, 'UTF-8') ?></b>
                        </td>
                        <td>
                            <input type="text" class="input-short" name="exten[<?= $slot_id ?>]" value="<?= htmlspecialchars($exten, ENT_QUOTES, 'UTF-8') ?>">
                        </td>
                        <td>
                            <select class="input-xshort" name="limit[<?= $slot_id ?>]">
                                <?php for ($l = 0; $l <= 3; $l++): ?>
                                <option value="<?= $l ?>" <?= ($limit_val == $l) ? 'selected' : '' ?>><?= $l ?></option>
                                <?php endfor; ?>
                            </select>
                        </td>
                        <td>
                            <input type="text" class="input-short" name="ogcid[<?= $slot_id ?>]" value="<?= htmlspecialchars($ogcid_val, ENT_QUOTES, 'UTF-8') ?>">
                        </td>
                        <td>
                            <button type="submit" name="update_wsp" value="<?= $slot_id ?>" class="btn btn-row">設定</button>
                        </td>
                        <td class="notice-message">
                            <?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?>
                        </td>
                    </tr>
                <?php endfor; ?>
            </tbody>
        </table>
    </form>
</div>
