<?php
if (!defined('ABS_PANEL_INCLUDED')) {
    die("Direct access is not permitted.");
}

// キャンセル処理
if (isset($_GET['action']) && $_GET['action'] === 'cancel_menu_edit') {
    unset($_SESSION['edit_menu_data']);
    // URLからクエリパラメータを削除してリダイレクト
    header('Location: index.php?page=ivr-config-page');
    exit;
}
// POST時処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $flash_message = ['type' => 'success', 'text' => '設定を保存しました。'];
    $function = $_POST['function'] ?? '';

    switch ($function) {
        // --- IVR処理番号 ---
        case 'add_number':
        case 'update_number':
            $p_ivrnumber = trim($_POST['ivrnumber'] ?? '');
            $p_ivrtype = $_POST['ivrtype'] ?? 'ivr';
            $p_ivritem = $_POST['ivritem'] ?? '';
            $p_ivrvalue = trim($_POST['ivrvalue'] ?? '');
            $is_valid = true;

            if (empty($p_ivrnumber)) {
                $flash_message = ['type' => 'error', 'text' => '着信番号は必須です。'];
                $is_valid = false;
            } elseif ($p_ivrnumber !== 'any' && !ctype_digit($p_ivrnumber)) {
                $flash_message = ['type' => 'error', 'text' => '着信番号は数字、または "any" で入力してください。'];
                $is_valid = false;
            }

            if ($is_valid) {
                AbspFunctions\put_db_item("ABS/IVR/NUM", $p_ivrnumber, "YES");
                if ($p_ivrtype == "direct" && !empty($p_ivritem)) {
                    AbspFunctions\put_db_item("ABS/IVR/DIR/$p_ivrnumber", "CTX", $p_ivritem);
                    AbspFunctions\put_db_item("ABS/IVR/DIR/$p_ivrnumber", "VAL", $p_ivrvalue);
                } else {
                    AbspFunctions\del_db_tree("ABS/IVR/DIR/$p_ivrnumber");
                }
                $flash_message['text'] = ($function == 'add_number') ? "IVR処理番号 {$p_ivrnumber} を追加しました。" : "IVR処理番号 {$p_ivrnumber} を更新しました。";
            } else {
                $_SESSION['form_data_number'] = $_POST;
            }
            break;

        case 'delete_number':
            if (isset($_POST['delcb']) && $_POST['delcb'] === 'yes') {
                $p_d_ivrnumber = $_POST['d_ivrnumber'];
                AbspFunctions\del_db_item("ABS/IVR/NUM", $p_d_ivrnumber);
                AbspFunctions\del_db_tree("ABS/IVR/DIR/$p_d_ivrnumber");
                $flash_message['text'] = "IVR処理番号 {$p_d_ivrnumber} を削除しました。";
            } else {
                $flash_message = ['type' => 'error', 'text' => '削除するにはチェックボックスをオンにしてください。'];
            }
            break;

        case 'edit_number':
            $_SESSION['edit_number_data'] = $_POST;
            $flash_message = null;
            break;

        // --- IVRメニュー項目 ---
        case 'add_menu':
        case 'update_menu':
            $p_tone = $_POST['tone'] ?? '';
            $p_ivritem = $_POST['ivritem'] ?? '';
            $p_ivrvalue = trim($_POST['ivrvalue'] ?? '');

            if ($p_tone !== '' && !empty($p_ivritem)) {
                AbspFunctions\put_db_item("ABS/IVR/MENU/$p_tone", "CTX", $p_ivritem);
                AbspFunctions\put_db_item("ABS/IVR/MENU/$p_tone", "VAL", $p_ivrvalue);
                 $flash_message['text'] = ($function == 'add_menu') ? "メニュー項目（トーン {$p_tone}）を追加しました。" : "メニュー項目（トーン {$p_tone}）を更新しました。";
            } else {
                 $flash_message = ['type' => 'error', 'text' => 'トーンと処理は必須です。'];
                 $_SESSION['form_data_menu'] = $_POST;
            }
            break;

        case 'delete_menu':
            if (isset($_POST['delcb']) && $_POST['delcb'] === 'yes') {
                $p_d_tone = $_POST['d_tone'];
                AbspFunctions\del_db_tree("ABS/IVR/MENU/$p_d_tone");
                $flash_message['text'] = "メニュー項目（トーン {$p_d_tone}）を削除しました。";
            } else {
                $flash_message = ['type' => 'error', 'text' => '削除するにはチェックボックスをオンにしてください。'];
            }
            break;

        case 'edit_menu':
            $_SESSION['edit_menu_data'] = $_POST;
            $flash_message = null;
            break;

        // --- その他設定 ---
        case 'sftimeset':
            $p_sftim = ctype_digit($_POST['sfts'] ?? '') ? $_POST['sfts'] : '300';
            AbspFunctions\put_db_item("ABS/IVR", "TIM", $p_sftim);
            $flash_message['text'] = 'セーフティタイマを設定しました。';
            break;

        case 'rpinset':
            $p_rpin = ctype_digit($_POST['rpin'] ?? '') ? $_POST['rpin'] : '0000';
            AbspFunctions\put_db_item("ABS/IVR", "RPIN", $p_rpin);
            $flash_message['text'] = 'メニュー音声録音PINを設定しました。';
            break;
    }

    if ($flash_message) {
        $_SESSION['flash_message'] = $flash_message;
    }
    header('Location: index.php?page=ivr-config-page');
    exit;
}

// GET時処理

// 音声フォーマット変換
exec('audio/convert.sh abs-ivrmenu > /dev/null 2>&1');

$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

// IVR処理の選択肢
$ivr_selection = [
    "ivr-item1" => "通常着信処理", "ivr-item2" => "特定内線着信", "ivr-item3" => "特定キー着信",
    "ivr-item4" => "留守番録音", "ivr-item5" => "保留音", "ivr-item6" => "エコーバック",
    "ivr-item7" => "音声再生", "ivr-item8" => "音声会議", "ivr-item9" => "FAX受信",
    "ivr-item10" => "カスタム2", "ivr-item11" => "カスタム3", "ivr-item12" => "カスタム4",
];
$ivr_opt_list_html = "";
foreach ($ivr_selection as $key => $value) {
    $ivr_opt_list_html .= "<option value=\"{$key}\">{$value}</option>\n";
}

// --- IVR処理番号フォームの準備 ---
$form_defaults_number = ['ivrnumber' => '', 'ivrtype' => 'ivr', 'ivritem' => '', 'ivrvalue' => ''];
$form_values_number = $_SESSION['form_data_number'] ?? $form_defaults_number;
unset($_SESSION['form_data_number']);
$is_edit_mode_number = false;
if (isset($_SESSION['edit_number_data'])) {
    $is_edit_mode_number = true;
    $form_values_number = array_merge($form_defaults_number, $_SESSION['edit_number_data']);
    unset($_SESSION['edit_number_data']);
}

// --- IVR処理番号一覧の取得 ---
$number_list = [];
$db_entries = AbspFunctions\get_db_family('ABS/IVR/NUM');
if (is_array($db_entries)) {
    foreach ($db_entries as $line) {
        list($pnum, ) = explode(' : ', $line, 2);
        $pnum = trim($pnum);
        $ivritem = AbspFunctions\get_db_item("ABS/IVR/DIR/$pnum", 'CTX');
        if (!empty($ivritem)) {
            $number_list[] = [
                'number' => $pnum,
                'type' => 'ダイレクト',
                'item_key' => $ivritem,
                'item_text' => $ivr_selection[$ivritem] ?? '不明',
                'value' => AbspFunctions\get_db_item("ABS/IVR/DIR/$pnum", 'VAL'),
            ];
        } else {
            $number_list[] = ['number' => $pnum, 'type' => 'IVR', 'item_key' => '', 'item_text' => '', 'value' => ''];
        }
    }
}

// --- IVRメニュー項目フォームの準備 ---
$form_defaults_menu = ['tone' => '', 'ivritem' => '', 'ivrvalue' => ''];
$form_values_menu = $_SESSION['form_data_menu'] ?? $form_defaults_menu;
unset($_SESSION['form_data_menu']);
$is_edit_mode_menu = false;
if (isset($_SESSION['edit_menu_data'])) {
    $is_edit_mode_menu = true;
    $form_values_menu = array_merge($form_defaults_menu, $_SESSION['edit_menu_data']);
    unset($_SESSION['edit_menu_data']);
}

// --- IVRメニュー項目一覧の取得 ---
$menu_list = [];
for ($i = 0; $i <= 9; $i++) {
    $ivritem = AbspFunctions\get_db_item("ABS/IVR/MENU/$i", 'CTX');
    if (!empty($ivritem)) {
        $menu_list[] = [
            'tone' => $i,
            'item_key' => $ivritem,
            'item_text' => $ivr_selection[$ivritem] ?? '不明',
            'value' => AbspFunctions\get_db_item("ABS/IVR/MENU/$i", 'VAL'),
        ];
    }
}

// --- その他設定の取得 ---
$sft_setting = AbspFunctions\get_db_item("ABS/IVR", "TIM") ?: '300';
$rpin_setting = AbspFunctions\get_db_item("ABS/IVR", "RPIN") ?: '0000';

?>
<h2>IVR設定</h2>

<?php if ($flash_message): ?>
<div class="notice-message" style="color: <?= $flash_message['type'] === 'error' ? '#f44336' : '#4CAF50' ?>; margin-bottom: 1.5em; font-weight: bold;">
    <?= htmlspecialchars($flash_message['text'], ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>


<h3 id="number-list">IVR処理番号設定</h3>
<p style="font-size: 0.9em; color: var(--secondary-text-color); margin-top: 0;">
    ここで指定した着信番号への着信のみがIVR処理の対象となります。「any」を指定すると全着信が対象です。
</p>
<form action="" method="POST" id="number-form">
    <input type="hidden" name="function" value="<?= $is_edit_mode_number ? 'update_number' : 'add_number' ?>">
    <div class="form-inline-group">
        <label for="ivrnumber">着信番号:</label>
        <input type="text" id="ivrnumber" name="ivrnumber" value="<?= htmlspecialchars($form_values_number['ivrnumber'], ENT_QUOTES, 'UTF-8') ?>" class="input-middle2" <?= $is_edit_mode_number ? 'readonly style="background-color: #444;"' : '' ?> placeholder="例: 0312345678 or any">
        
        <label for="ivrtype">種別:</label>
        <select id="ivrtype" name="ivrtype" class="input-xmiddle">
            <option value="ivr" <?= ($form_values_number['ivrtype'] == 'ivr') ? 'selected' : '' ?>>IVR</option>
            <option value="direct" <?= ($form_values_number['ivrtype'] == 'direct') ? 'selected' : '' ?>>ダイレクト</option>
        </select>

        <span class="direct-options">
            <label for="ivritem_num">処理:</label>
            <select id="ivritem_num" name="ivritem" class="input-middle">
                <option value=""></option>
                <?php foreach ($ivr_selection as $key => $value): ?>
                <option value="<?= $key ?>" <?= ($form_values_number['ivritem'] == $key) ? 'selected' : '' ?>><?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </span>
        <span class="direct-options">
            <label for="ivrvalue_num">設定値:</label>
            <input type="text" id="ivrvalue_num" name="ivrvalue" value="<?= htmlspecialchars($form_values_number['ivrvalue'], ENT_QUOTES, 'UTF-8') ?>" class="input-short">
        </span>

        <button type="submit" class="btn btn-primary"><?= $is_edit_mode_number ? '更新' : '追加' ?></button>
        <?php if ($is_edit_mode_number): ?>
            <a href="index.php?page=ivr-config-page&action=cancel_menu_edit" class="btn">キャンセル</a>
        <?php endif; ?>
    </div>
</form>

<div class="table-container">
    <table class="absp-table">
        <thead>
            <tr><th>着信番号</th><th>種別</th><th>処理</th><th>設定値</th><th>操作</th></tr>
        </thead>
        <tbody>
            <?php if (empty($number_list)): ?>
                <tr><td colspan="5" style="text-align: center; padding: 20px;">登録されているIVR処理番号はありません。</td></tr>
            <?php else: ?>
                <?php foreach ($number_list as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['number'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($item['type'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($item['item_text'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($item['value'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <div class="form-inline-group" style="gap: 0.5em;">
                            <form action="" method="POST" style="margin: 0;">
                                <input type="hidden" name="function" value="edit_number">
                                <input type="hidden" name="ivrnumber" value="<?= htmlspecialchars($item['number'], ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="ivrtype" value="<?= ($item['type'] == 'IVR') ? 'ivr' : 'direct' ?>">
                                <input type="hidden" name="ivritem" value="<?= htmlspecialchars($item['item_key'], ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="ivrvalue" value="<?= htmlspecialchars($item['value'], ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" class="btn btn-row">編集</button>
                            </form>
                            <form action="" method="POST" style="margin: 0;" onsubmit="if(!this.delcb.checked) { alert('削除するにはチェックボックスをオンにしてください。'); return false; } return confirm('IVR処理番号 <?= htmlspecialchars($item['number'], ENT_QUOTES, 'UTF-8') ?> を本当に削除しますか？');">
                                <input type="hidden" name="function" value="delete_number">
                                <input type="hidden" name="d_ivrnumber" value="<?= htmlspecialchars($item['number'], ENT_QUOTES, 'UTF-8') ?>">
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('ivrtype');
    const directOptions = document.querySelectorAll('.direct-options');

    function toggleDirectOptions() {
        const shouldShow = typeSelect.value === 'direct';
        directOptions.forEach(el => {
            el.style.display = shouldShow ? '' : 'none';
        });
    }
    typeSelect.addEventListener('change', toggleDirectOptions);
    toggleDirectOptions();
});
</script>


<h3 id="menu-list">IVRメニュー項目設定</h3>
<p style="font-size: 0.9em; color: var(--secondary-text-color); margin-top: 0;">
    着信者がダイヤルした番号（トーン）に応じた処理を割り当てます。
</p>
<form action="" method="POST">
    <input type="hidden" name="function" value="<?= $is_edit_mode_menu ? 'update_menu' : 'add_menu' ?>">
    <div class="form-inline-group">
        <label for="tone">トーン:</label>
        <select id="tone" name="tone" class="input-xshort" <?= $is_edit_mode_menu ? 'disabled' : '' ?>>
             <?php for ($i = 0; $i <= 9; $i++): ?>
                <option value="<?= $i ?>" <?= ((string)$form_values_menu['tone'] === (string)$i) ? 'selected' : '' ?>><?= $i ?></option>
            <?php endfor; ?>
        </select>
        <?php if ($is_edit_mode_menu): // disabledなselectの値はPOSTされないため、hiddenで送信 ?>
        <input type="hidden" name="tone" value="<?= htmlspecialchars($form_values_menu['tone'], ENT_QUOTES, 'UTF-8') ?>">
        <?php endif; ?>

        <label for="ivritem_menu">処理:</label>
        <select id="ivritem_menu" name="ivritem" class="input-middle">
            <option value=""></option>
             <?php foreach ($ivr_selection as $key => $value): ?>
                <option value="<?= $key ?>" <?= ($form_values_menu['ivritem'] == $key) ? 'selected' : '' ?>><?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
        
        <label for="ivrvalue_menu">設定値:</label>
        <input type="text" id="ivrvalue_menu" name="ivrvalue" value="<?= htmlspecialchars($form_values_menu['ivrvalue'], ENT_QUOTES, 'UTF-8') ?>" class="input-short">

        <button type="submit" class="btn btn-primary"><?= $is_edit_mode_menu ? '更新' : '追加' ?></button>
        <?php if ($is_edit_mode_menu): ?>
            <a href="index.php?page=ivr-config-page&action=cancel_menu_edit" class="btn">キャンセル</a>
        <?php endif; ?>
    </div>
</form>

<div class="table-container">
    <table class="absp-table">
        <thead>
            <tr><th>トーン</th><th>処理</th><th>設定値</th><th>操作</th></tr>
        </thead>
        <tbody>
            <?php if (empty($menu_list)): ?>
                <tr><td colspan="4" style="text-align: center; padding: 20px;">登録されているIVRメニュー項目はありません。</td></tr>
            <?php else: ?>
                <?php foreach ($menu_list as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['tone'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($item['item_text'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($item['value'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <div class="form-inline-group" style="gap: 0.5em;">
                            <form action="" method="POST" style="margin: 0;">
                                <input type="hidden" name="function" value="edit_menu">
                                <input type="hidden" name="tone" value="<?= htmlspecialchars($item['tone'], ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="ivritem" value="<?= htmlspecialchars($item['item_key'], ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="value" value="<?= htmlspecialchars($item['value'], ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" class="btn btn-row">編集</button>
                            </form>
                            <form action="" method="POST" style="margin: 0;" onsubmit="if(!this.delcb.checked) { alert('削除するにはチェックボックスをオンにしてください。'); return false; } return confirm('メニュー項目（トーン <?= htmlspecialchars($item['tone'], ENT_QUOTES, 'UTF-8') ?>）を本当に削除しますか？');">
                                <input type="hidden" name="function" value="delete_menu">
                                <input type="hidden" name="d_tone" value="<?= htmlspecialchars($item['tone'], ENT_QUOTES, 'UTF-8') ?>">
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

<h3>その他設定</h3>
<form action="" method="POST" class="form-inline-group" style="margin-bottom: 1.5em;">
    <input type="hidden" name="function" value="sftimeset">
    <label for="sfts">セーフティタイマ:</label>
    <input type="text" id="sfts" name="sfts" value="<?= htmlspecialchars($sft_setting, ENT_QUOTES, 'UTF-8') ?>" class="input-xshort">
    <span>秒</span>
    <button type="submit" class="btn">設定</button>
</form>

<form action="" method="POST" class="form-inline-group">
    <input type="hidden" name="function" value="rpinset">
    <label for="rpin">メニュー音声録音PIN:</label>
    <input type="text" id="rpin" name="rpin" value="<?= htmlspecialchars($rpin_setting, ENT_QUOTES, 'UTF-8') ?>" class="input-short">
    <button type="submit" class="btn">設定</button>
</form>

<h3>IVRメニュー音声管理</h3>
<div style="padding: 1em; background-color: var(--surface-color); border: 1px solid var(--border-color); border-radius: 6px;">
    <h4>現在の音声</h4>
    <audio controls src="audio/abs-ivrmenu.mp3?t=<?= time() ?>" style="width: 100%;">
      Your browser does not support the audio element.
    </audio>
    <p style="font-size: 0.8em; color: var(--secondary-text-color); text-align: right;">
        最終更新: <?= file_exists('audio/abs-ivrmenu.mp3') ? date("Y-m-d H:i:s", filemtime('audio/abs-ivrmenu.mp3')) : '不明' ?>
    </p>

    <hr style="border-color: var(--border-color); margin: 1.5em 0;">

    <h4>音声の更新方法</h4>
    <p style="font-size: 0.9em; color: var(--secondary-text-color);">
        IVRのメニュー音声を更新するには、以下のいずれかの方法があります。
    </p>
    <ul>
        <li><strong>方法1: 電話機からの録音</strong><br>
            1. 指定の内線番号（*870番）にダイヤルします。<br>
            2. 上記で設定した「メニュー音声録音PIN」（4桁）を入力し、#を押します。<br>
            3. アナウンスに従い、発信音の後にメッセージを録音し、最後に#を押します。<br>
            4. 録音が完了したら、このページを再読み込みして上のプレイヤーで内容を確認してください。
        </li>
        <li><strong>方法2: 音声ファイルのアップロード</strong><br>
            PCで作成した音声ファイル（`abs-ivrmenu.mp3`）を、所定のアップロード機能（もしあれば）またはFTP等でサーバーに転送し、既存の音声ファイルを上書きしてください。
        </li>
    </ul>
</div>
