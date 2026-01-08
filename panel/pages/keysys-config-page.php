<?php
if (!defined('ABS_PANEL_INCLUDED')) {
    die("Direct access is not permitted.");
}

// =========================================================
// 設定・定義
// =========================================================
$max_keys = isset($max_keys) ? $max_keys : 32;
$notice_msg = [];

// キー種別定義
$key_type_opts = [
    ''      => 'なし',
    'NTTE'  => 'NTT東',
    'NTTW'  => 'NTT西',
    'BASIX' => 'BASIX',
    'UAREA' => 'ユーザ定義',
];

// モニターモード定義
$monitor_opts = [
    'B' => 'Bin',
    'S' => 'Spy',
];

// =========================================================
// POST時処理
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = '';

    // 個別キー保存
    if (isset($_POST['update_key'])) {
        $p_key = key($_POST['update_key']);
        
        $key_info_set = [
            'key'   => $p_key,
            'label' => $_POST['label'][$p_key] ?? '',
            'tech'  => 'PJSIP', // 固定
            'trunk' => $_POST['trunk'][$p_key] ?? '',
            'type'  => $_POST['key_type'][$p_key] ?? '',
            'ogcid' => $_POST['ogcid'][$p_key] ?? '',
            'rgrp'  => $_POST['rgrp'][$p_key] ?? '',
            'rgpt'  => $_POST['rgpt'][$p_key] ?? '0',
            'bpin'  => $_POST['bpin'][$p_key] ?? '',
            'mmd'   => $_POST['mmd'][$p_key] ?? 'B',
        ];
        $notice_msg[$p_key] = $ami->setKeyInfo($key_info_set);
    }
    // 手動トランク設定
    elseif (isset($_POST['function']) && $_POST['function'] == 'trunkadd') {
        $p_trunk = trim($_POST['trunk']);
        $p_key = $_POST['keynum'];
        // $ami->putDbItem を使用
        $ami->putDbItem("KEYTEL/KEYSYS$p_key", 'TRUNK', $p_trunk);
        $notice_msg[$p_key] = "手動設定しました";
    }

    $_SESSION['flash_message'] = $message;
    header('Location: index.php?page=keysys-config-page');
    exit;
}

$flash_message = $_SESSION['flash_message'] ?? '';
unset($_SESSION['flash_message']);

// =========================================================
// データ取得 (View用データの準備)
// =========================================================

// 1. リストの取得
// ■■ 変更点: 新しい共通メソッドを使用 ■■
$target_list = $ami->getExtAndGroupList();
$trunk_list  = $ami->getTrunkList();

// 2. 表示用データの構築 (LogicとViewの分離)
$keys_display_data = [];

for ($i = 1; $i <= $max_keys; $i++) {
    // DBから情報取得
    $info = $ami->getKeyInfo($i);
    
    $trunk_val = $info['trunk'] ?? '';
    
    // トランクの手動入力判定 (DBには値があるが、現在の登録トランク一覧にない場合)
    $is_manual_trunk = ($trunk_val !== '' && !in_array($trunk_val, $trunk_list));

    $keys_display_data[$i] = [
        'id'        => $i,
        'label'     => $info['label'] ?? '',
        'trunk'     => $trunk_val,
        'is_manual' => $is_manual_trunk,
        'type'      => $info['type'] ?? '',
        'ogcid'     => $info['ogcid'] ?? '',
        'rgrp'      => $info['rgrp'] ?? '',
        'rgpt'      => $info['rgpt'] ?? '0',
        'bpin'      => $info['bpin'] ?? '',
        'mmd'       => $info['mmd'] ?? 'B',
        'msg'       => $notice_msg[$i] ?? ''
    ];
}
?>

<h2>キーシステム設定</h2>

<div class="table-container">
<form action="" method="post">
    <table class="absp-table">
        <thead>
            <tr>
                <th>番号</th>
                <th>ラベル</th>
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
            <?php foreach ($keys_display_data as $key_data): ?>
                <?php $i = $key_data['id']; ?>
                <tr>
                    <td style="text-align: right;">KEY<?= $i ?></td>
                    
                    <td><input type="text" name="label[<?= $i ?>]" value="<?= htmlspecialchars($key_data['label'], ENT_QUOTES, 'UTF-8') ?>" class="input-short"></td>
                    
                    <td>
                        <?php if (!$key_data['is_manual']): ?>
                            <select name="trunk[<?= $i ?>]" class="input-short2">
                                <option value="">未設定</option>
                                <?php foreach ($trunk_list as $trunk_item): ?>
                                    <option value="<?= htmlspecialchars($trunk_item, ENT_QUOTES, 'UTF-8') ?>" <?= ($key_data['trunk'] === $trunk_item) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($trunk_item, ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <input type="text" name="trunk[<?= $i ?>]" value="<?= htmlspecialchars($key_data['trunk'], ENT_QUOTES, 'UTF-8') ?>" class="input-short2">
                        <?php endif; ?>
                    </td>

                    <td>
                        <select name="key_type[<?= $i ?>]" class="input-em6">
                            <?php foreach ($key_type_opts as $val => $text): ?>
                                <option value="<?= $val ?>" <?= ($key_data['type'] === $val) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($text, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>

                    <td><input type="text" class="input-middle2" name="ogcid[<?= $i ?>]" value="<?= htmlspecialchars($key_data['ogcid'], ENT_QUOTES, 'UTF-8') ?>"></td>

                    <td>
                        <select name="rgrp[<?= $i ?>]" class="input-short">
                            <option value="">なし</option>
                            <?php foreach ($target_list as $target): ?>
                                <option value="<?= htmlspecialchars($target['value'], ENT_QUOTES, 'UTF-8') ?>" <?= ($key_data['rgrp'] === $target['value']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($target['label'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>

                    <td>
                        <select name="rgpt[<?= $i ?>]" class="input-short">
                            <?php for($r = 0; $r <= 5; $r++): ?>
                                <option value="<?= $r ?>" <?= ((string)$key_data['rgpt'] === (string)$r) ? 'selected' : '' ?>><?= $r ?></option>
                            <?php endfor; ?>
                        </select>
                    </td>

                    <td><input type="text" name="bpin[<?= $i ?>]" value="<?= htmlspecialchars($key_data['bpin'], ENT_QUOTES, 'UTF-8') ?>" class="input-xshort"></td>

                    <td>
                        <select name="mmd[<?= $i ?>]" class="input-xshort">
                            <?php foreach ($monitor_opts as $val => $text): ?>
                                <option value="<?= $val ?>" <?= ($key_data['mmd'] === $val) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($text, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>

                    <td>
                        <button type="submit" name="update_key[<?= $i ?>]" value="save" class="btn btn-row">設定</button>
                    </td>

                    <td class="notice-message">
                        <?= htmlspecialchars($key_data['msg'], ENT_QUOTES, 'UTF-8') ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</form>
</div>

<hr>

<h3>手動トランク設定</h3>
<form action="" method="post">
    <div class="form-inline-group">
        <label for="keynum_select">キー:</label>
        <select id="keynum_select" name="keynum">
            <?php for($i=1; $i<=$max_keys; $i++): ?>
                <option value="<?= $i ?>">KEY<?= $i ?></option>
            <?php endfor; ?>
        </select>

        <label for="trunk_name_input">トランク名:</label>
        <input type="text" id="trunk_name_input" name="trunk" class="input-short">
        
        <input type="hidden" name="function" value="trunkadd">
        <input type="submit" class="btn" value="設定">
    </div>
</form>
