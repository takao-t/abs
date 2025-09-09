<?php
if (!defined('ABS_PANEL_INCLUDED')) {
    die("Direct access is not permitted.");
}

// POST時処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $flash_message = ['type' => 'success', 'text' => '設定を保存しました。'];
    $function = $_POST['function'] ?? '';

    switch ($function) {
        case 'cllog': // 着信ログ設定
            if (isset($_POST['clogsw']) && $_POST['clogsw'] === 'on') {
                AbspFunctions\put_db_item('ABS', 'ILOG', '1');
            } else {
                AbspFunctions\del_db_item('ABS', 'ILOG');
            }
            break;

        case 'bllog': // 拒否ログ設定
            if (isset($_POST['blogsw']) && $_POST['blogsw'] === 'on') {
                AbspFunctions\put_db_item('ABS/BLC', 'LOG', '1');
            } else {
                AbspFunctions\del_db_item('ABS/BLC', 'LOG');
            }
            break;

        case 'dologrot': // ログ・ローテーション実行
            if (isset($_POST['logrchk']) && $_POST['logrchk'] === 'YES') {
                AbspFunctions\exec_cli_command('channel originate Local/s@create-logdb application NoCDR');
                $flash_message['text'] = "ローテーションを実行しました。";
            } else {
                 $flash_message = ['type' => 'error', 'text' => '実行するにはチェックボックスをオンにしてください。'];
            }
            break;

        case 'logverctl': // ログバージョン切り替え
            $p_dbver = trim($_POST['dbver'] ?? '');
            if ($p_dbver !== '' && ctype_digit($p_dbver)) {
                AbspFunctions\put_db_item('ABS', 'CLOGVER', $p_dbver);
            } else {
                AbspFunctions\del_db_item('ABS', 'CLOGVER'); // 空が選択されたら現在ログ
            }
            break;
    }

    if ($flash_message) {
        $_SESSION['flash_message'] = $flash_message;
    }
    header('Location: index.php?page=call-log-page');
    exit;
}

// GET時処理
$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

// 設定値の取得
$clogver_setting = AbspFunctions\get_db_item('ABS', 'CLOGVER') ?: '';
$clogsw_setting = AbspFunctions\get_db_item('ABS', 'ILOG') === '1';
$blogsw_setting = AbspFunctions\get_db_item('ABS/BLC', 'LOG') === '1';

// ログ概要の取得
$log_summary_list = [];
$dbfile_base = CLOGDB; // config.phpで定義

for ($i = 0; $i < 10; $i++) {
    $summary = [
        'num' => ($i == 0) ? '現在' : $i,
        'count' => 'ファイルなし',
        'start' => '---',
        'end' => '---'
    ];
    $dbfile = ($i == 0) ? $dbfile_base : "{$dbfile_base}.{$i}";

    if (file_exists($dbfile)) {
        try {
            $logdb = new SQLite3($dbfile, SQLITE3_OPEN_READONLY);
            $count_total = $logdb->querySingle("SELECT count(*) FROM abslog");

            if ($count_total > 0) {
                $summary['count'] = $count_total;
                $summary['start'] = $logdb->querySingle("SELECT TIMESTAMP FROM abslog ORDER BY ID ASC LIMIT 1");
                $summary['end'] = $logdb->querySingle("SELECT TIMESTAMP FROM abslog ORDER BY ID DESC LIMIT 1");
            } else {
                $summary['count'] = 0;
            }
            $logdb->close();
        } catch (Exception $e) {
            // ファイルはあるが読めない場合など
            $summary['count'] = 'エラー';
        }
    }
    $log_summary_list[] = $summary;
}

?>
<h2 id="inlog">着信記録管理</h2>

<?php if ($flash_message): ?>
<div class="notice-message" style="color: <?= $flash_message['type'] === 'error' ? '#f44336' : '#4CAF50' ?>; margin-bottom: 1.5em; font-weight: bold;">
    <?= htmlspecialchars($flash_message['text'], ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>

<h3>記録表示</h3>
<form action="" method="post" class="form-inline-group">
    <input type="hidden" name="function" value="logverctl">
    <label for="dbver">表示対象の履歴世代:</label>
    <select name="dbver" id="dbver" class="input-xmiddle">
        <option value="" <?= ($clogver_setting == '') ? 'selected' : '' ?>>現在</option>
        <?php for($i = 1; $i < 10; $i++): ?>
        <option value="<?= $i ?>" <?= ($clogver_setting == $i) ? 'selected' : '' ?>><?= $i ?></option>
        <?php endfor; ?>
    </select>
    <button type="submit" class="btn">変更</button>
</form>

<div class="form-inline-group" style="margin-top: 1em;">
    <a href="index.php?page=call-log-disp" class="btn btn-primary">着信履歴表示</a>
    <a href="index.php?page=block-log-disp" class="btn btn-primary">着信拒否履歴表示</a>
</div>


<h3>記録の概要</h3>
<div class="table-container">
    <table class="absp-table">
        <thead>
            <tr>
                <th>世代番号</th>
                <th>期間（開始）</th>
                <th>期間（終了）</th>
                <th>件数</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($log_summary_list as $summary): ?>
            <tr>
                <td><?= htmlspecialchars($summary['num'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($summary['start'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($summary['end'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($summary['count'], ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>


<h3>記録設定</h3>
<form action="" method="post" class="form-inline-group">
    <input type="hidden" name="function" value="cllog">
    <input type="checkbox" id="clogsw" name="clogsw" value="on" <?= $clogsw_setting ? 'checked' : '' ?>>
    <label for="clogsw">着信記録を有効にする</label>
    <button type="submit" class="btn">設定</button>
</form>

<form action="" method="post" class="form-inline-group">
    <input type="hidden" name="function" value="bllog">
    <input type="checkbox" id="blogsw" name="blogsw" value="on" <?= $blogsw_setting ? 'checked' : '' ?>>
    <label for="blogsw">着信拒否記録を有効にする</label>
    <button type="submit" class="btn">設定</button>
</form>

<h3>ログ・ローテーションの実行</h3>
<p style="font-size: 0.9em; color: var(--secondary-text-color); margin-top: 0;">
    現在の着信履歴DBを過去の世代に切り替えます（9世代まで保存）。<br>
    <strong style="color: #f44336;">注意: これを実行すると即時にログDBが切り替わります。また、初めて着信履歴を使用する場合も実行してください。</strong>
</p>
<form action="" method="post" class="form-inline-group">
    <input type="hidden" name="function" value="dologrot">
    <input type="checkbox" id="logrchk" name="logrchk" value="YES">
    <label for="logrchk">上記注意を確認し、ローテーションを実行します</label>
    <button type="submit" class="btn">実行</button>
</form>
