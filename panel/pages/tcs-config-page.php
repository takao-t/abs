<?php
// index.php で生成された $ami インスタンスを使用
global $ami;

if (!defined('ABS_PANEL_INCLUDED') || !is_object($ami)) {
    die("Direct access is not permitted.");
}

// POST時処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $flash_message = ['type' => 'success', 'text' => '設定を保存しました。'];
    $function = $_POST['function'] ?? '';

    switch ($function) {
        case 'tccset': // 時間外制御設定
            $ami->putDbItem('ABS', 'TCC', $_POST['tccval'] ?? '0');
            break;

        case 'tdisset': // ダイヤルイン時間外制御設定
            $ami->putDbItem('ABS/DID', 'TCS', $_POST['tdiss'] ?? '1');
            break;

        case 'thdisset': // ダイヤルイン時休日制御
            $ami->putDbItem('ABS/DID', 'THS', $_POST['thdiss'] ?? '1');
            break;

        case 'tcspecset': // 時刻情報設定
            $days = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
            $selected_days = [];
            
            foreach($days as $day) {
                if(isset($_POST[$day])) {
                    $selected_days[] = $_POST[$day];
                }
            }
            // 配列を '&' で結合 (例: mon&tue&wed)
            $p_twday = implode('&', $selected_days);
            
            $p_tstart = $_POST['tstart'] ?? '09:00';
            $p_tend = $_POST['tend'] ?? '17:00';
            
            // AsteriskのTime書式: time,days,dayofmonth,month
            $p_tcspec = "{$p_tstart}-{$p_tend},{$p_twday},*,*";
            $ami->putDbItem('ABS', 'TCSPEC', $p_tcspec);
            break;

        case 'tchset': // 祝日・休日制御
            $ami->putDbItem('ABS', 'TCHC', $_POST['tchval'] ?? '0');
            break;

        case 'tctset': // トグル切り替え設定
            $ami->putDbItem('ABS', 'TCT', $_POST['tctval'] ?? '0');
            break;

        case 'tcpinset': // PINセット
            $ami->putDbItem('ABS', 'TCPIN', $_POST['tcpin'] ?? '');
            break;

        case 'wtiset': // 待機時間設定
            $ami->putDbItem('ABS', 'WTI', $_POST['wtival'] ?? '10');
            break;

        case 'tcovr': // 時間外迂回設定
            $ami->putDbItem('ABS', 'TCPBRP', trim($_POST['tcpbrp'] ?? '1'));
            $ami->putDbItem('ABS', 'TCOVRPIN', trim($_POST['tcovrpin'] ?? ''));
            $ami->putDbItem('ABS', 'TCOVREXT', trim($_POST['tcovrext'] ?? ''));
            break;
        
        default:
            $flash_message = null;
            break;
    }

    if ($flash_message) {
        $_SESSION['flash_message'] = $flash_message;
    }
    header('Location: index.php?page=tcs-config-page');
    exit;
}

// GET時処理
$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

// 各種設定値を取得
$tcc_setting = $ami->getDbItem('ABS', 'TCC');
if ($tcc_setting === '') $tcc_setting = '0';

$tdis_setting = $ami->getDbItem('ABS/DID', 'TCS');
if ($tdis_setting === '') $tdis_setting = '1';

$thdis_setting = $ami->getDbItem('ABS/DID', 'THS');
if ($thdis_setting === '') $thdis_setting = '1';

$tch_setting = $ami->getDbItem('ABS', 'TCHC');
if ($tch_setting === '') $tch_setting = '0';

$tct_setting = $ami->getDbItem('ABS', 'TCT');
if ($tct_setting === '') $tct_setting = '0';

$tcpin_setting = $ami->getDbItem('ABS', 'TCPIN');

$wti_setting = $ami->getDbItem('ABS', 'WTI');
if ($wti_setting === '') $wti_setting = '10';

$tcpbrp_setting = $ami->getDbItem('ABS', 'TCPBRP');
if ($tcpbrp_setting === '') $tcpbrp_setting = '1';

$tcovrpin_setting = $ami->getDbItem('ABS', 'TCOVRPIN');
$tcovrext_setting = $ami->getDbItem('ABS', 'TCOVREXT');

// 時刻・曜日設定のパース
$tcspec = $ami->getDbItem('ABS', 'TCSPEC');
$stime_setting = '09:00';
$etime_setting = '17:00';
$twday_setting = 'mon&tue&wed&thu&fri'; // デフォルト

if (!empty($tcspec)) {
    // データ形式: 09:00-17:00,mon&tue,*,*
    $parts = explode(',', $tcspec, 3);
    if (count($parts) >= 2) {
        $ttime = $parts[0];
        $twday_setting = $parts[1];
        
        $times = explode('-', $ttime, 2);
        if (count($times) == 2) {
            $stime_setting = $times[0];
            $etime_setting = $times[1];
        }
    }
}

// 時間外迂回先の選択肢取得 (Logic分離)
// 元のコードは 'group' 指定でしたが、新しい仕組みでは全ターゲット(内線、グループ等)を取得して表示します
$target_list = $ami->getTargetList();

?>
<h2>時間外制御設定</h2>

<?php if ($flash_message): ?>
<div class="notice-message" style="color: <?= $flash_message['type'] === 'error' ? '#f44336' : '#4CAF50' ?>; margin-bottom: 1.5em; font-weight: bold;">
    <?= htmlspecialchars($flash_message['text'], ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>

<h3>基本制御</h3>
<form action="" method="POST" class="form-inline-group">
    <input type="hidden" name="function" value="tccset">
    <label for="tccval">時間外制御:</label>
    <select id="tccval" name="tccval" class="input-middle">
        <option value="0" <?= ($tcc_setting == '0') ? 'selected' : '' ?>>時刻分岐しない</option>
        <option value="1" <?= ($tcc_setting == '1') ? 'selected' : '' ?>>時刻分岐あり。音声再生後切断。</option>
        <option value="2" <?= ($tcc_setting == '2') ? 'selected' : '' ?>>時刻分岐あり。要件録音あり。</option>
        <option value="3" <?= ($tcc_setting == '3') ? 'selected' : '' ?>>強制時間外設定。音声再生後切断。</option>
        <option value="4" <?= ($tcc_setting == '4') ? 'selected' : '' ?>>強制時間外設定。要件録音あり。</option>
        <option value="5" <?= ($tcc_setting == '5') ? 'selected' : '' ?>>時間分岐IVR</option>
        <option value="6" <?= ($tcc_setting == '6') ? 'selected' : '' ?>>強制時間外IVR</option>
    </select>
    <button type="submit" class="btn">設定</button>
</form>

<form action="" method="POST" class="form-inline-group">
    <input type="hidden" name="function" value="tdisset">
    <label for="tdiss">ダイヤルイン時:</label>
    <select id="tdiss" name="tdiss" class="input-middle">
        <option value="0" <?= ($tdis_setting == '0') ? 'selected' : '' ?>>時間外制御する</option>
        <option value="1" <?= ($tdis_setting == '1') ? 'selected' : '' ?>>時間外制御しない</option>
    </select>
    <button type="submit" class="btn">設定</button>
</form>


<h3>着信可能時間</h3>
<form action="" method="POST">
    <input type="hidden" name="function" value="tcspecset">
    <p style="font-size: 0.9em; color: var(--secondary-text-color); margin-top: 0;">
        着信を受け付ける曜日と時間帯を設定します。
    </p>
    <table class="absp-table" style="width: auto; margin-bottom: 1em;">
        <thead>
            <tr><th><label for="sun">日</label></th><th><label for="mon">月</label></th><th><label for="tue">火</label></th><th><label for="wed">水</label></th><th><label for="thu">木</label></th><th><label for="fri">金</label></th><th><label for="sat">土</label></th></tr>
        </thead>
        <tbody>
            <tr>
            <?php $days = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat']; ?>
            <?php foreach ($days as $day): ?>
                <td style="text-align: center;">
                    <input type="checkbox" id="<?= $day ?>" name="<?= $day ?>" value="<?= $day ?>" <?= (strpos($twday_setting, $day) !== false) ? 'checked' : '' ?>>
                </td>
            <?php endforeach; ?>
            </tr>
        </tbody>
    </table>
    <div class="form-inline-group">
        <label for="tstart">時間:</label>
        <input type="text" id="tstart" name="tstart" size="6" value="<?= htmlspecialchars($stime_setting, ENT_QUOTES, 'UTF-8') ?>" class="input-short" placeholder="HH:MM">
        <span>～</span>
        <input type="text" id="tend" name="tend" size="6" value="<?= htmlspecialchars($etime_setting, ENT_QUOTES, 'UTF-8') ?>" class="input-short" placeholder="HH:MM">
        <button type="submit" class="btn">設定</button>
    </div>
</form>


<h3>休日制御</h3>
<form action="" method="POST" class="form-inline-group">
    <input type="hidden" name="function" value="tchset">
    <label for="tchval">祝日・休日制御:</label>
    <select id="tchval" name="tchval" class="input-middle">
        <option value="0" <?= ($tch_setting == '0') ? 'selected' : '' ?>>行わない</option>
        <option value="1" <?= ($tch_setting == '1') ? 'selected' : '' ?>>行う (音声再生後切断)</option>
        <option value="2" <?= ($tch_setting == '2') ? 'selected' : '' ?>>行う (留守録あり)</option>
    </select>
    <button type="submit" class="btn">設定</button>
    <a href="index.php?page=holiday-config-page" class="btn">祝日・休日管理</a>
</form>

<form action="" method="POST" class="form-inline-group">
    <input type="hidden" name="function" value="thdisset">
    <label for="thdiss">ダイヤルイン時:</label>
    <select id="thdiss" name="thdiss" class="input-middle">
        <option value="0" <?= ($thdis_setting == '0') ? 'selected' : '' ?>>休日制御する</option>
        <option value="1" <?= ($thdis_setting == '1') ? 'selected' : '' ?>>休日制御しない</option>
    </select>
    <button type="submit" class="btn">設定</button>
</form>


<h3>手動オーバーライド</h3>
<p style="font-size: 0.9em; color: var(--secondary-text-color); margin-top: 0;">
    特番ダイヤルによる強制的な時間外モードの切り替え設定です。
</p>
<form action="" method="POST" class="form-inline-group">
    <input type="hidden" name="function" value="tctset">
    <label for="tctval">トグル切替機能:</label>
    <select id="tctval" name="tctval" class="input-middle">
        <option value="0" <?= ($tct_setting == '0') ? 'selected' : '' ?>>使用しない</option>
        <option value="1" <?= ($tct_setting == '1') ? 'selected' : '' ?>>時刻分岐あり。音声再生後切断。</option>
        <option value="2" <?= ($tct_setting == '2') ? 'selected' : '' ?>>時刻分岐あり。要件録音あり。</option>
        <option value="3" <?= ($tct_setting == '3') ? 'selected' : '' ?>>強制時間外設定。音声再生後切断。</option>
        <option value="4" <?= ($tct_setting == '4') ? 'selected' : '' ?>>強制時間外設定。要件録音あり。</option>
        <option value="5" <?= ($tct_setting == '5') ? 'selected' : '' ?>>時間分岐IVR</option>
        <option value="6" <?= ($tct_setting == '6') ? 'selected' : '' ?>>強制時間外IVR</option>
    </select>
    <button type="submit" class="btn">設定</button>
</form>

<form action="" method="POST" class="form-inline-group">
    <input type="hidden" name="function" value="tcpinset">
    <label for="tcpin">切替用PIN:</label>
    <input type="text" id="tcpin" name="tcpin" value="<?= htmlspecialchars($tcpin_setting, ENT_QUOTES, 'UTF-8') ?>" class="input-short">
    <button type="submit" class="btn">設定</button>
</form>

<form action="" method="POST">
    <input type="hidden" name="function" value="tcovr">
    <div class="form-inline-group">
        <label>時間外迂回:</label>
        <label for="tcpbrp" style="font-weight: normal;">応答繰り返し回数</label>
        <input type="text" id="tcpbrp" name="tcpbrp" value="<?= htmlspecialchars($tcpbrp_setting, ENT_QUOTES, 'UTF-8') ?>" class="input-xshort">
        <label for="tcovrpin" style="font-weight: normal;">PIN</label>
        <input type="text" id="tcovrpin" name="tcovrpin" value="<?= htmlspecialchars($tcovrpin_setting, ENT_QUOTES, 'UTF-8') ?>" class="input-short">
        
        <label for="tcovrext" style="font-weight: normal;">着信先</label>
        <select id="tcovrext" name="tcovrext">
            <option value="">選択なし</option>
            <?php foreach ($target_list as $target): ?>
                <option value="<?= htmlspecialchars($target['value'], ENT_QUOTES, 'UTF-8') ?>" <?= ($target['value'] == $tcovrext_setting) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($target['label'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <button type="submit" class="btn">設定</button>
    </div>
    <p style="font-size: 0.9em; color: var(--secondary-text-color); margin-top: 0.5em;">
        注意：応答繰り返しは「音声再生後切断」時にのみ有効です。留守録時のメッセージ再生は1回のみです。
    </p>
</form>


<h3>その他設定</h3>
<form action="" method="POST" class="form-inline-group">
    <input type="hidden" name="function" value="wtiset">
    <label for="wtival">自動応答前待機時間:</label>
    <input type="text" id="wtival" name="wtival" value="<?= htmlspecialchars($wti_setting, ENT_QUOTES, 'UTF-8') ?>" class="input-xshort">
    <span>秒</span>
    <button type="submit" class="btn">設定</button>
</form>
