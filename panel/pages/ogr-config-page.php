<?php
if (!defined('ABS_PANEL_INCLUDED')) {
    die("Direct access is not permitted.");
}

// POST時処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $flash_message = ['type' => 'success', 'text' => '設定を保存しました。'];
    $function = $_POST['function'] ?? '';

    switch ($function) {
        case 'ogpupdate':
            for ($i = 1; $i <= 2; $i++) {
                $p_ogp_num = $_POST["ogp_num_$i"];
                if ($p_ogp_num === '') {
                    AbspFunctions\del_db_tree("ABS/OGP$i");
                    AbspFunctions\del_db_item('ABS', "OGP$i");
                } else {
                    $p_ogp_route = $_POST["ogp_route_$i"];
                    $p_ogp_route_num = $_POST["ogp_route_num_$i"];
                    $p_ogp_ogcid = $_POST["ogp_ogcid_$i"];
                    if (ctype_digit($p_ogp_num)) {
                        AbspFunctions\put_db_item('ABS', "OGP$i", "$p_ogp_num");
                        AbspFunctions\del_db_item("ABS/OGP$i", 'KEY');
                        AbspFunctions\del_db_item("ABS/OGP$i", 'NKS');
                        AbspFunctions\put_db_item("ABS/OGP$i", $p_ogp_route, ($p_ogp_route == 'NKS') ? "1" : "$p_ogp_route_num");
                    }
                    if ($p_ogp_ogcid === '') {
                        AbspFunctions\del_db_item("ABS/OGP$i", 'OGCID');
                    } elseif (ctype_digit($p_ogp_ogcid)) {
                        AbspFunctions\put_db_item("ABS/OGP$i", 'OGCID', $p_ogp_ogcid);
                    }
                }
            }
            $p_aec_codes = $_POST['aec_codes'];
            if ($p_aec_codes === '') {
                AbspFunctions\del_db_item("ABS", "AEC");
            } else {
                AbspFunctions\put_db_item("ABS", "AEC", $p_aec_codes);
            }
            break;

        case 'nksupdate':
            for ($i = 1; $i <= 2; $i++) {
                // 直接入力(di)があればそちらを優先し、なければプルダウンの値を取得する
                $direct_input = trim($_POST["nks_trunk_di_$i"] ?? '');
                $dropdown_selection = trim($_POST["nks_trunk_$i"] ?? '');
                $p_nks_trunk = !empty($direct_input) ? $direct_input : $dropdown_selection;

                if (empty($p_nks_trunk)) {
                    AbspFunctions\del_db_tree("ABS/NKS$i");
                } else {
                    $p_nks_tech = $_POST["nks_tech_$i"];
                    $p_nks_type = $_POST["nks_type_$i"];
                    AbspFunctions\put_db_item("ABS/NKS$i", "TRUNK", $p_nks_trunk);
                    AbspFunctions\put_db_item("ABS/NKS$i", "TECH", $p_nks_tech);
                    if ($p_nks_type == 'none') {
                        AbspFunctions\del_db_item("ABS/NKS$i", "TYP");
                    } else {
                        AbspFunctions\put_db_item("ABS/NKS$i", "TYP", $p_nks_type);
                    }
                }
            }
            break;

        // (他のcaseは変更なし)
        case 'd56update':
            AbspFunctions\put_db_item('ABS', 'D56', (isset($_POST['d56opt']) && $_POST['d56opt'] == 'on') ? '1' : '');
            for ($i = 1; $i <= 4; $i++) {
                AbspFunctions\put_db_item('ABS/D57KEY', "$i", $_POST["d57key_$i"]);
            }
            break;

        case 'tsswadd':
            $p_tssw_cid = trim($_POST['tssw_cid']);
            $p_tssw_trunk = trim($_POST['tssw_trunkd'] ?: $_POST['tssw_trunks']);
            if (!empty($p_tssw_cid) && !empty($p_tssw_trunk)) {
                $p_tssw_tech = $_POST['tssw_tech'];
                $p_tssw_type = $_POST['tssw_type'];
                AbspFunctions\put_db_item('ABS/TSSW', $p_tssw_cid, $p_tssw_trunk);
                AbspFunctions\put_db_item("ABS/TSSW/$p_tssw_cid", 'TECH', $p_tssw_tech);
                AbspFunctions\put_db_item("ABS/TSSW/$p_tssw_cid", 'TYP', $p_tssw_type);
                $flash_message['text'] = 'トランクスイッチャを追加しました。';
            } else {
                 $flash_message = ['type' => 'error', 'text' => '発信CIDとトランクは必須です。'];
            }
            break;

        case 'tsswdel':
            $p_delcid = trim($_POST['delcid']);
            AbspFunctions\del_db_tree("ABS/TSSW/$p_delcid");
            AbspFunctions\del_db_item("ABS/TSSW", $p_delcid);
            $flash_message['text'] = 'トランクスイッチャを削除しました。';
            break;

        case 'tpfxadd':
            $p_pfx = trim($_POST['pfx']);
            $p_pfxtrunk = trim($_POST['pfxtrunk']);
            if ($p_pfx !== '' && !empty($p_pfxtrunk)) {
                AbspFunctions\put_db_item('ABS/TRUNK/PFX', $p_pfxtrunk, "$p_pfx");
                $flash_message['text'] = 'トランクプレフィクスを追加しました。';
            } else {
                $flash_message = ['type' => 'error', 'text' => 'トランク名とプレフィクスは必須です。'];
            }
            break;

        case 'tpfxdel':
            $p_pfxtrunk = trim($_POST['pfxtrunk']);
            AbspFunctions\del_db_item('ABS/TRUNK/PFX', $p_pfxtrunk);
            $flash_message['text'] = 'トランクプレフィクスを削除しました。';
            break;
    }

    $_SESSION['flash_message'] = $flash_message;
    header('Location: index.php?page=ogr-config-page');
    exit;
}

// GET時処理
$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

// OGP設定
$ogp_settings = [];
for ($i = 1; $i <= 2; $i++) {
    $ogp_num = AbspFunctions\get_ogp_num($i);
    $ogp_settings[$i] = [
        'num' => $ogp_num,
        'route' => ($ogp_num !== '') ? AbspFunctions\get_ogp_route($i) : 'NKS',
        'route_num' => ($ogp_num !== '') ? AbspFunctions\get_ogp_routenum($i) : '',
        'ogcid' => ($ogp_num !== '') ? AbspFunctions\get_ogp_ogcid($i) : '',
    ];
}
$aec_codes = AbspFunctions\get_aec_codes();

// NKS設定
$nks_settings = [];
$trunk_options_html_all = AbspFunctions\create_trunk_list('', ''); // 判定用に全ての選択肢を取得
for ($i = 1; $i <= 2; $i++) {
    $trunk = AbspFunctions\get_nks_trunk($i);

    // 保存済みのトランク名が、選択肢の中に含まれているかを確認
    $is_in_options = true;
    if (!empty($trunk)) {
        // value="trunk名" という文字列が含まれているかで判定
        if (strpos($trunk_options_html_all, "value=\"".htmlspecialchars($trunk, ENT_QUOTES, 'UTF-8')."\"") === false) {
            $is_in_options = false;
        }
    }

    $nks_settings[$i] = [
        'trunk' => $trunk,
        'tech' => !empty($trunk) ? AbspFunctions\get_nks_tech($i) : 'PJSIP',
        'type' => !empty($trunk) ? AbspFunctions\get_nks_type($i) : 'none',
        'is_trunk_in_options' => $is_in_options, // 判定結果を保存
    ];
}

// (特番設定以降は変更なし)
$d56_checked = AbspFunctions\get_db_item('ABS', "D56") == "1";
$d57_keys = [];
for ($i = 1; $i <= 4; $i++) {
    $d57_keys[$i] = AbspFunctions\get_db_item('ABS/D57KEY', "$i");
}
$tssw_list = AbspFunctions\create_tssw_list();
$trunk_options_html = AbspFunctions\create_trunk_list();
$tpfxs = AbspFunctions\get_db_family('ABS/TRUNK/PFX');

?>
<h2>発信設定</h2>

<?php if ($flash_message): ?>
<div class="notice-message" style="color: <?= $flash_message['type'] === 'error' ? '#f44336' : '#4CAF50' ?>; margin-bottom: 1.5em; font-weight: bold;">
    <?= htmlspecialchars($flash_message['text'], ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>

<form action="" method="post">
    <input type="hidden" name="function" value="ogpupdate">
    <h3 id="ogp">プレフィクス発信</h3>
    <div class="table-container">
        <table class="absp-table">
            <thead>
                <tr>
                    <th>番号</th>
                    <th>プレフィクス</th>
                    <th>経路</th>
                    <th>経路番号</th>
                    <th>発信CID</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ogp_settings as $i => $ogp): ?>
                <tr>
                    <td>プレフィクス<?= $i ?></td>
                    <td><input type="text" name="ogp_num_<?= $i ?>" value="<?= htmlspecialchars($ogp['num'], ENT_QUOTES, 'UTF-8') ?>" class="input-xshort"></td>
                    <td>
                        <select name="ogp_route_<?= $i ?>" class="input-em6">
                            <option value="NKS" <?= ($ogp['route'] == 'NKS') ? 'selected' : '' ?>>NKS</option>
                            <option value="KEY" <?= ($ogp['route'] == 'KEY') ? 'selected' : '' ?>>KEY</option>
                        </select>
                    </td>
                    <td><input type="text" name="ogp_route_num_<?= $i ?>" value="<?= htmlspecialchars($ogp['route_num'], ENT_QUOTES, 'UTF-8') ?>" class="input-short"></td>
                    <td><input type="text" name="ogp_ogcid_<?= $i ?>" value="<?= htmlspecialchars($ogp['ogcid'], ENT_QUOTES, 'UTF-8') ?>" class="input-short2"></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="form-inline-group" style="margin-top: 1em;">
        <label for="aec_codes">市内局番 (カンマ区切り):</label>
        <input type="text" id="aec_codes" name="aec_codes" value="<?= htmlspecialchars($aec_codes, ENT_QUOTES, 'UTF-8') ?>" class="input-middle">
    </div>
    <small>注意: NKS指定時には経路番号は'1'を指定してください。</small>
    <div style="margin-top: 1em;">
        <button type="submit" class="btn btn-primary">プレフィクスと市内局番を設定変更</button>
    </div>
</form>

<form action="" method="post">
    <input type="hidden" name="function" value="nksupdate">
    <h3 id="nks">ノーキーシステム設定</h3>
    <div class="table-container">
        <table class="absp-table">
            <thead>
                <tr>
                    <th>番号</th>
                    <th>TECH</th>
                    <th>トランク (選択または直接入力)</th>
                    <th>種別</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($nks_settings as $i => $nks): ?>
                <tr>
                    <td>NKS<?= $i ?> (プレフィクス<?= $i ?>に対応)</td>
                    <td>
                        <select name="nks_tech_<?= $i ?>" class="input-xmiddle">
                            <option value="PJSIP" <?= ($nks['tech'] == 'PJSIP') ? 'selected' : '' ?>>PJSIP</option>
                            <option value="SIP" <?= ($nks['tech'] == 'SIP') ? 'selected' : '' ?>>SIP</option>
                        </select>
                    </td>
                    <td>
                        <div class="form-inline-group" style="gap: 0.5em;">
                            <?php if ($nks['is_trunk_in_options']): ?>
                                <select name="nks_trunk_<?= $i ?>" class="input-middle">
                                    <?= AbspFunctions\create_trunk_list('', $nks['trunk']) ?>
                                </select>
                                <input type="text" name="nks_trunk_di_<?= $i ?>" class="input-middle" placeholder="新規直接入力...">
                            <?php else: ?>
                                <input type="text" name="nks_trunk_di_<?= $i ?>" class="input-middle" value="<?= htmlspecialchars($nks['trunk'], ENT_QUOTES, 'UTF-8') ?>">
                                <select name="nks_trunk_<?= $i ?>" class="input-middle">
                                    <option value="">↓ または選択肢から選ぶ</option>
                                    <?= $trunk_options_html_all ?>
                                </select>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <select name="nks_type_<?= $i ?>" class="input-middle">
                            <option value="none" <?= ($nks['type'] == 'none') ? 'selected' : '' ?>>なし</option>
                            <option value="NTTE" <?= ($nks['type'] == 'NTTE') ? 'selected' : '' ?>>NTT東</option>
                            <option value="NTTW" <?= ($nks['type'] == 'NTTW') ? 'selected' : '' ?>>NTT西</option>
                            <option value="BASIX" <?= ($nks['type'] == 'BASIX') ? 'selected' : '' ?>>BASIX</option>
                            <option value="UAREA" <?= ($nks['type'] == 'UAREA') ? 'selected' : '' ?>>ユーザ定義</option>
                        </select>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div style="margin-top: 1em;">
        <button type="submit" class="btn btn-primary">設定変更</button>
    </div>
    <small>注意: OGPでKEYが指定されている場合にはノーキーシステムは使用されません。</small>
</form>

<form action="#d56option" method="post">
    <input type="hidden" name="function" value="d56update">
    <h3 id="d56option">キー捕捉特番設定</h3>
    <div class="form-inline-group">
        <input type="checkbox" name="d56opt" value="on" id="d56opt" <?= $d56_checked ? 'checked' : '' ?>>
        <label for="d56opt">*56[キー番号]特番発信を有効にする</label>
    </div>
    <h4 style="margin-top: 1.5em; margin-bottom: 0.5em;">*57[番号]特番発信</h4>
    <div class="table-container">
        <table class="absp-table">
            <thead>
                <tr>
                    <th>番号</th>
                    <th>キー範囲 (ハイフン区切り)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($d57_keys as $i => $key_range): ?>
                <tr>
                    <td><?= $i ?></td>
                    <td><input type="text" name="d57key_<?= $i ?>" value="<?= htmlspecialchars($key_range, ENT_QUOTES, 'UTF-8') ?>" class="input-short"></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div style="margin-top: 1em;">
        <button type="submit" class="btn btn-primary">設定変更</button>
    </div>
</form>


<h2>トランク特殊設定</h2>

<h3 id="tssw">トランクスイッチャ</h3>
<div class="table-container">
    <table class="absp-table">
        <thead>
            <tr>
                <th>発信CID</th>
                <th>TECH</th>
                <th>トランク</th>
                <th>種別</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($tssw_list)): ?>
                <?php foreach ($tssw_list as $cid):
                    $trunk = AbspFunctions\get_db_item("ABS/TSSW", $cid);
                    $tech = AbspFunctions\get_db_item("ABS/TSSW/$cid", 'TECH');
                    $type = AbspFunctions\get_db_item("ABS/TSSW/$cid", 'TYP');
                ?>
                <tr>
                    <td><?= htmlspecialchars($cid, ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($tech, ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($trunk, ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <form action="" method="post" onsubmit="return confirm('本当に削除しますか？');">
                            <input type="hidden" name="delcid" value="<?= htmlspecialchars($cid, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="function" value="tsswdel">
                            <button type="submit" class="btn btn-row">削除</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5" style="text-align: center; padding: 1em;">設定されていません。</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<form action="" method="post">
    <input type="hidden" name="function" value="tsswadd">
    <h4>トランクスイッチャ追加</h4>
    <div class="table-container">
        <table class="absp-table">
            <thead>
                <tr>
                    <th>発信CID</th>
                    <th>TECH</th>
                    <th>トランク (選択または直接入力)</th>
                    <th>種別</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><input type="text" name="tssw_cid" class="input-short2"></td>
                    <td>
                        <select name="tssw_tech" class="input-xmiddle">
                            <option value="PJSIP">PJSIP</option>
                            <option value="SIP">SIP</option>
                        </select>
                    </td>
                    <td>
                        <div class="form-inline-group" style="gap: 0.5em;">
                           <select name="tssw_trunks" class="input-middle"><?= $trunk_options_html ?></select>
                           <input type="text" name="tssw_trunkd" class="input-middle" placeholder="直接入力...">
                        </div>
                    </td>
                    <td>
                        <select name="tssw_type" class="input-middle">
                            <option value="none">なし</option>
                            <option value="NTTE">NTT東</option>
                            <option value="NTTW">NTT西</option>
                            <option value="BASIX">BASIX</option>
                            <option value="UAREA">ユーザ定義</option>
                        </select>
                    </td>
                    <td><button type="submit" class="btn">追加</button></td>
                </tr>
            </tbody>
        </table>
    </div>
</form>

<h3 id="tpfx">トランクプレフィクス</h3>
<div class="table-container">
    <table class="absp-table">
        <thead>
            <tr>
                <th>トランク名</th>
                <th>プレフィクス</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($tpfxs)): ?>
                <?php foreach ($tpfxs as $line): 
                    list($trunk, $prefix) = explode(':', $line, 2);
                ?>
                <tr>
                    <td><?= htmlspecialchars(trim($trunk), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars(trim($prefix), ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <form action="" method="post" onsubmit="return confirm('本当に削除しますか？');">
                            <input type="hidden" name="function" value="tpfxdel">
                            <input type="hidden" name="pfxtrunk" value="<?= htmlspecialchars(trim($trunk), ENT_QUOTES, 'UTF-8') ?>">
                            <button type="submit" class="btn btn-row">削除</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="3" style="text-align: center; padding: 1em;">設定されていません。</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<form action="" method="post">
    <input type="hidden" name="function" value="tpfxadd">
    <h4>トランクプレフィクス追加</h4>
    <div class="form-inline-group">
        <label>トランク名: <input type="text" name="pfxtrunk" class="input-short2"></label>
        <label>プレフィクス: <input type="text" name="pfx" class="input-xshort"></label>
        <button type="submit" class="btn">設定</button>
    </div>
</form>
