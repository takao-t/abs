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

        case 'tech_settings':
            $ami->putDbItem('ABS', 'EXTTECH', $_POST['exttech'] ?? 'PJSIP');
            $ami->putDbItem('ABS', 'E1XXUSE', $_POST['e1xxuse'] ?? 'NO');
            $ami->putDbItem('ABS', 'MDIGITS', $_POST['mdigits'] ?? '10');
            break;

        case 'feature_settings':
            $cpbt = ctype_digit($_POST['cpbt'] ?? '') ? $_POST['cpbt'] : 60;
            $ami->putDbItem('ABS', 'CPBT', $cpbt < 30 ? 30 : $cpbt);
            
            $spbt = ctype_digit($_POST['spbt'] ?? '') ? $_POST['spbt'] : 60;
            $ami->putDbItem('ABS', 'SPBT', $spbt < 30 ? 30 : $spbt);
            $ami->putDbItem('ABS', 'SPBU', $_POST['sppuse'] ?? '0');
            
            $ami->putDbItem('ABS/KHBL', 'USE', $_POST['khbuse'] ?? '0');
            $khbtime = ctype_digit($_POST['khbtime'] ?? '') ? $_POST['khbtime'] : 3;
            $ami->putDbItem('ABS/KHBL', 'TIME', $khbtime < 2 ? 2 : $khbtime);
            break;
            
        case 'localring':
            $p_lr_ext = trim($_POST['extnum'] ?? '');
            $c_lr_ext = $ami->getDbItem('ABS/ERV', 'localring');
            $ami->putDbItem('ABS/LOCALTECH', 'localring', 'Local');
            
            if ($p_lr_ext == '') {
                if ($c_lr_ext != '') {
                    $ami->delDbItem('ABS/EXT', $c_lr_ext);
                    $ami->delDbItem('ABS/ERV', 'localring');
                    $flash_message['text'] = '鳴動内線を削除しました。';
                }
            } elseif ($ami->getDbItem('ABS/EXT', $p_lr_ext) !== "") {
                $flash_message = ['type' => 'error', 'text' => '内線番号が重複しています。'];
            } else {
                if ($c_lr_ext != '') $ami->delDbItem('ABS/EXT', $c_lr_ext);
                $ami->putDbItem('ABS/EXT', $p_lr_ext, 'localring');
                $ami->putDbItem('ABS/ERV', 'localring', $p_lr_ext);
                $flash_message['text'] = '鳴動内線を設定しました。';
            }
            break;

        case 'mcastset':
            $p_mcext = trim($_POST['mcext'] ?? '');
            $p_mclimit = $_POST['mclimit'] ?? '1';
            $p_mctarget = trim($_POST['mctarget'] ?? '');
            $c_mcext = $ami->getDbItem('ABS/ERV', 'mcast1');
            $ami->putDbItem('ABS/LOCALTECH', 'mcast1', 'Local');
            
            if ($p_mcext == '') {
                if($c_mcext != ''){
                    $ami->delDbItem('ABS/EXT', $c_mcext);
                    $ami->delDbItem('ABS/ERV', 'mcast1');
                    $ami->delDbItem('ABS/MCAST1', 'TARGET');
                    $flash_message['text'] = 'マルチキャスト・ページングを削除しました。';
                }
            } elseif ($ami->getDbItem('ABS/EXT', $p_mcext) !== "" && $p_mcext != $c_mcext){
                $flash_message = ['type' => 'error', 'text' => '内線番号が重複しています。'];
            } elseif ($p_mctarget == '') {
                $flash_message = ['type' => 'error', 'text' => '送信先IPアドレスが指定されていません。'];
            } else {
                if ($c_mcext != '') $ami->delDbItem('ABS/EXT', $c_mcext);
                $ami->putDbItem('ABS/EXT', $p_mcext, 'mcast1');
                $ami->putDbItem('ABS/ERV', 'mcast1', $p_mcext);
                $ami->putDbItem('ABS/MCAST1', 'LMT', $p_mclimit);
                $ami->putDbItem('ABS/MCAST1', 'TARGET', $p_mctarget);
                $flash_message['text'] = 'マルチキャスト・ページングを設定しました。';
            }
            break;

        case 'area_settings':
            $ami->putDbItem('ABS/NTTE', 'AREA', $_POST['a_ntte'] ?? 'ntt-east.ne.jp');
            $ami->putDbItem('ABS/NTTW', 'AREA', $_POST['a_nttw'] ?? 'ntt-west.ne.jp');
            $ami->putDbItem('ABS/BASIX', 'AREA', $_POST['a_basix'] ?? 'asterisk.basix.ne.jp');
            $ami->putDbItem('ABS/UAREA', 'AREA', $_POST['a_user'] ?? '');
            break;

        case 'radioconf': // 無線GW設定
            $ami->putDbItem('ABS', 'RADIOGW', $_POST['radiogw'] ?? '');
            break;

        case 'keysysinit':
            $ami->execCliCommand('channel originate Local/s@keysinit application NoOp');
            $flash_message['text'] = 'キーシステムの初期化を実行しました。';
            break;
    }

    if ($flash_message) {
        $_SESSION['flash_message'] = $flash_message;
    }
    header('Location: index.php?page=pbx-detail-page');
    exit;
}

// GET時処理
$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);
$user_update_msg = $_SESSION['user_update_msg'] ?? []; unset($_SESSION['user_update_msg']);
$user_add_msg = $_SESSION['user_add_msg'] ?? ''; unset($_SESSION['user_add_msg']);

// 各種設定値の取得
$tech_setting = $ami->getDbItem('ABS', 'EXTTECH');
if ($tech_setting === '') $tech_setting = 'PJSIP';

$e1xx_setting = $ami->getDbItem('ABS', 'E1XXUSE');
if ($e1xx_setting === '') $e1xx_setting = 'NO';

$mdigits_setting = $ami->getDbItem('ABS','MDIGITS');
if ($mdigits_setting === '') $mdigits_setting = '10';

$cpbt_setting = $ami->getDbItem('ABS', 'CPBT');
if ($cpbt_setting === '') $cpbt_setting = '60';

$spbt_setting = $ami->getDbItem('ABS', 'SPBT');
if ($spbt_setting === '') $spbt_setting = '60';

$sppuse_setting = $ami->getDbItem('ABS', 'SPBU');
if ($sppuse_setting === '') $sppuse_setting = '0';

$khbuse_setting = $ami->getDbItem('ABS/KHBL', 'USE');
if ($khbuse_setting === '') $khbuse_setting = '0';

$khbtime_setting = $ami->getDbItem('ABS/KHBL', 'TIME');
if ($khbtime_setting === '') $khbtime_setting = '3';

$lr_ext_setting = $ami->getDbItem('ABS/ERV', 'localring');
$mc_ext_setting = $ami->getDbItem('ABS/ERV', 'mcast1');

$mc_limit_setting = $ami->getDbItem('ABS/MCAST1', 'LMT');
if ($mc_limit_setting === '') $mc_limit_setting = '1';

$mc_target_setting = $ami->getDbItem('ABS/MCAST1', 'TARGET');

$a_ntte_setting = $ami->getDbItem('ABS/NTTE', 'AREA');
if ($a_ntte_setting === '') $a_ntte_setting = 'ntt-east.ne.jp';

$a_nttw_setting = $ami->getDbItem('ABS/NTTW', 'AREA');
if ($a_nttw_setting === '') $a_nttw_setting = 'ntt-west.ne.jp';

$a_basix_setting = $ami->getDbItem('ABS/BASIX', 'AREA');
if ($a_basix_setting === '') $a_basix_setting = 'asterisk.basix.ne.jp';

$a_user_setting = $ami->getDbItem('ABS/UAREA', 'AREA');
$radiogw_setting = $ami->getDbItem('ABS', 'RADIOGW');

?>
<h2>PBX機能詳細設定</h2>

<?php if ($flash_message): ?>
<div class="notice-message" style="color: <?= $flash_message['type'] === 'error' ? '#f44336' : '#4CAF50' ?>; margin-bottom: 1.5em; font-weight: bold;">
    <?= htmlspecialchars($flash_message['text'], ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>

<h3>情報確認</h3>
<div class="form-inline-group">
    <a href="index.php?page=view-exten-page" class="btn">内線情報</a>
    <a href="index.php?page=view-endpoint-page" class="btn">端末情報</a>
</div>

<h3>基本テクノロジ設定</h3>
<form action="" method="post">
    <input type="hidden" name="function" value="tech_settings">
    <div class="form-inline-group" style="margin-bottom: 0.8em;">
        <label for="exttech">内線テクノロジ:</label>
        <select id="exttech" name="exttech">
            <option value="PJSIP" <?= ($tech_setting == 'PJSIP') ? 'selected' : '' ?>>PJSIP</option>
        </select>
    </div>
    <div class="form-inline-group" style="margin-bottom: 0.8em;">
        <label for="e1xxuse">1xx特番発信機能:</label>
        <select id="e1xxuse" name="e1xxuse">
            <option value="NO" <?= ($e1xx_setting == 'NO') ? 'selected' : '' ?>>使わない</option>
            <option value="YES" <?= ($e1xx_setting == 'YES') ? 'selected' : '' ?>>使う</option>
        </select>
    </div>
    <div class="form-inline-group">
        <label for="mdigits">自局電話番号桁数:</label>
        <input type="text" id="mdigits" name="mdigits" class="input-xshort" value="<?= htmlspecialchars($mdigits_setting, ENT_QUOTES, 'UTF-8') ?>">
        <span>桁</span>
    </div>
    <button type="submit" class="btn btn-primary" style="margin-top: 1em;">基本テクノロジ設定を保存</button>
</form>

<h3>通話機能設定</h3>
<form action="" method="post">
    <input type="hidden" name="function" value="feature_settings">
    <div class="form-inline-group" style="margin-bottom: 0.8em;">
        <label for="cpbt">コールパーク呼び戻し時間:</label>
        <input type="text" id="cpbt" name="cpbt" class="input-xshort" value="<?= htmlspecialchars($cpbt_setting, ENT_QUOTES, 'UTF-8') ?>">
        <span>秒</span>
    </div>
    <div class="form-inline-group" style="margin-bottom: 0.8em;">
        <label for="spbt">セルフパーク呼び戻し時間:</label>
        <input type="text" id="spbt" name="spbt" class="input-xshort" value="<?= htmlspecialchars($spbt_setting, ENT_QUOTES, 'UTF-8') ?>">
        <span>秒</span>
        <label for="sppuse" style="font-weight: normal;">ピックアップ:</label>
        <select id="sppuse" name="sppuse">
            <option value="1" <?= ($sppuse_setting == '1') ? 'selected' : '' ?>>使う</option>
            <option value="0" <?= ($sppuse_setting == '0') ? 'selected' : '' ?>>使わない</option>
        </select>
    </div>
    <div class="form-inline-group">
        <label for="khbuse">キー保留時点滅機能:</label>
        <select id="khbuse" name="khbuse">
            <option value="1" <?= ($khbuse_setting == '1') ? 'selected' : '' ?>>使う</option>
            <option value="0" <?= ($khbuse_setting == '0') ? 'selected' : '' ?>>使わない</option>
        </select>
        <label for="khbtime" style="font-weight: normal;">間隔:</label>
        <input type="text" id="khbtime" name="khbtime" class="input-xshort" value="<?= htmlspecialchars($khbtime_setting, ENT_QUOTES, 'UTF-8') ?>">
        <span>秒</span>
    </div>
    <button type="submit" class="btn btn-primary" style="margin-top: 1em;">通話機能設定を保存</button>
</form>

<h3>特殊内線設定</h3>
<form action="" method="post" class="form-inline-group">
    <input type="hidden" name="function" value="localring">
    <label for="extnum">鳴動内線 (localring):</label>
    <input type="text" id="extnum" name="extnum" class="input-short" value="<?= htmlspecialchars($lr_ext_setting, ENT_QUOTES, 'UTF-8') ?>">
    <button type="submit" class="btn">設定</button>
</form>
<p style="font-size: 0.9em; color: var(--secondary-text-color); margin-top: 0.5em;">
    鳴動のみを行う特殊な内線です。空欄で設定すると削除されます。
</p>

<form action="" method="post" class="form-inline-group">
    <input type="hidden" name="function" value="mcastset">
    <label>マルチキャスト・ページング:</label>
    <label for="mcext" style="font-weight: normal;">内線番号</label>
    <input type="text" id="mcext" name="mcext" class="input-short" value="<?= htmlspecialchars($mc_ext_setting, ENT_QUOTES, 'UTF-8') ?>">
    <label for="mclimit" style="font-weight: normal;">規制値</label>
    <select id="mclimit" name="mclimit" class="input-xshort">
        <option value="1" <?= ($mc_limit_setting == '1') ? 'selected' : '' ?>>1</option>
        <option value="2" <?= ($mc_limit_setting == '2') ? 'selected' : '' ?>>2</option>
        <option value="3" <?= ($mc_limit_setting == '3') ? 'selected' : '' ?>>3</option>
    </select>
    <label for="mctarget" style="font-weight: normal;">送信先 (IP:Port)</label>
    <input type="text" id="mctarget" name="mctarget" class="input-middle" value="<?= htmlspecialchars($mc_target_setting, ENT_QUOTES, 'UTF-8') ?>">
    <button type="submit" class="btn">設定</button>
</form>
<p style="font-size: 0.9em; color: var(--secondary-text-color); margin-top: 0.5em;">
    指定内線にダイヤルすると、マルチキャストでページングします。内線番号を空欄で設定すると削除されます。
</p>

<h3>システム・初期化</h3>
<form action="" method="post" class="form-inline-group" style="margin-bottom: 1em;" id="keysysinit-form">
    <input type="hidden" name="function" value="keysysinit">
    <p style="margin: 0;">キーシステムの状態を初期化します。<strong style="color: #f44336;">通話中には実行しないでください。</strong></p>
    <button type="submit" class="btn">初期化実行</button>
</form>

<form action="" method="post">
    <input type="hidden" name="function" value="area_settings">
    <h4>エリア管理</h4>
    <div class="table-container">
        <table class="absp-table" style="width: auto;">
            <tbody>
                <tr><td>NTT東</td><td><input type="text" name="a_ntte" class="input-middle" value="<?= htmlspecialchars($a_ntte_setting, ENT_QUOTES, 'UTF-8') ?>"></td></tr>
                <tr><td>NTT西</td><td><input type="text" name="a_nttw" class="input-middle" value="<?= htmlspecialchars($a_nttw_setting, ENT_QUOTES, 'UTF-8') ?>"></td></tr>
                <tr><td>BASIX</td><td><input type="text" name="a_basix" class="input-middle" value="<?= htmlspecialchars($a_basix_setting, ENT_QUOTES, 'UTF-8') ?>"></td></tr>
                <tr><td>ユーザ定義</td><td><input type="text" name="a_user" class="input-middle" value="<?= htmlspecialchars($a_user_setting, ENT_QUOTES, 'UTF-8') ?>"></td></tr>
            </tbody>
        </table>
    </div>
    <button type="submit" class="btn btn-primary" style="margin-top: 1em;">エリア設定を保存</button>
</form>

<h3>無線GW設定</h3>
<form action="" method="post" class="form-inline-group">
    <input type="hidden" name="function" value="radioconf">
    <label for="radiogw">エンドポイントとポート(複数時はカンマ区切り):</label>
    <input type="text" id="radiogw" name="radiogw" class="input-middle" value="<?= htmlspecialchars($radiogw_setting, ENT_QUOTES, 'UTF-8') ?>" placeholder="PJSIP/endpoint:Port,PJSIP/endpoint:Port...">
    <button type="submit" class="btn">設定</button>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // キーシステム初期化の確認
    const keysysForm = document.getElementById('keysysinit-form');
    if (keysysForm) {
        keysysForm.addEventListener('submit', function(e) {
            if (!confirm('本当にキーシステムを初期化しますか？')) {
                e.preventDefault();
            }
        });
    }
});
</script>
