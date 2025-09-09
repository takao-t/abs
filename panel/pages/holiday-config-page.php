<?php
if (!defined('ABS_PANEL_INCLUDED')) {
    die("Direct access is not permitted.");
}

// POST時処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $flash_message = ['type' => 'success', 'text' => '設定を保存しました。'];
    $function = $_POST['function'] ?? '';

    switch ($function) {
        case 'modh': // 祝・休設定（チェックボックス）
            AbspFunctions\exec_cli_command('database deltree HOLIDAYS/JAPAN');
            if (!empty($_POST['day'])) {
                foreach ($_POST['day'] as $d_line) {
                    AbspFunctions\put_db_item('HOLIDAYS/JAPAN', trim($d_line), '1');
                }
            }
            $flash_message['text'] = '休業日の設定を更新しました。';
            break;

        case 'addh': // 独自データ追加
            $p_ndate = trim($_POST['ndate'] ?? '');
            $p_nname = trim($_POST['nname'] ?? '休日');
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $p_ndate)) {
                AbspFunctions\put_db_item('HOLIDAYS/JAPANBASE', $p_ndate, $p_nname);
                 $flash_message['text'] = "独自データ {$p_ndate}: {$p_nname} を追加/更新しました。";
            } else {
                $flash_message = ['type' => 'error', 'text' => '日付はYYYY-MM-DD形式で入力してください。'];
            }
            break;

        case 'deldate': // データ削除
            $p_ddate = trim($_POST['ddate'] ?? '');
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $p_ddate)) {
                AbspFunctions\del_db_item('HOLIDAYS/JAPAN', $p_ddate);
                AbspFunctions\del_db_item('HOLIDAYS/JAPANBASE', $p_ddate);
                $flash_message['text'] = "データ {$p_ddate} を削除しました。";
            } else {
                $flash_message = ['type' => 'error', 'text' => '日付はYYYY-MM-DD形式で入力してください。'];
            }
            break;

        case 'gethbase': // 基本データ更新
            $url = $_POST['turl'] ?? '';
            $page = @file_get_contents($url);
            if ($page === false) {
                $flash_message = ['type' => 'error', 'text' => '祝日・休日情報の取得に失敗しました。URLを確認してください。'];
            } else {
                AbspFunctions\exec_cli_command('database deltree HOLIDAYS'); // 全データ初期化
                $page = mb_convert_encoding($page, "UTF-8", "SJIS");
                $page = str_replace('/', '-', $page);
                $holidays = explode("\n", $page);
                
                $this_y = date('Y');
                foreach ($holidays as $index => $line) {
                    if ($index === 0 || trim($line) === '') continue; // ヘッダと空行をスキップ
                    list($day, $name) = explode(',', str_replace('"', '', trim($line)), 2);
                    list($p_y, ) = explode('-', $day, 2);
                    if ($p_y >= $this_y) {
                        AbspFunctions\put_db_item('HOLIDAYS/JAPANBASE', $day, $name);
                        AbspFunctions\put_db_item('HOLIDAYS/JAPAN', $day, '1');
                    }
                }
                $flash_message['text'] = '祝日・休日情報を内閣府の公開データから更新しました。';
            }
            break;
    }

    if ($flash_message) {
        $_SESSION['flash_message'] = $flash_message;
    }
    header('Location: index.php?page=holiday-config-page');
    exit;
}

// GET時処理
$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

// 祝日リストの取得
$base_holidays = [];
$db_base_list = AbspFunctions\get_db_family('HOLIDAYS/JAPANBASE');

// 有効な祝日のリストを読み込み、高速アクセスのためにキーを日付にした配列を作成
$active_holidays = [];
$db_active_list = AbspFunctions\get_db_family('HOLIDAYS/JAPAN');
if (is_array($db_active_list)) {
    foreach ($db_active_list as $line) {
        $day = trim(explode(' : ', $line, 2)[0]);
        $active_holidays[$day] = true;
    }
}

if (is_array($db_base_list)) {
    foreach ($db_base_list as $line) {
        list($day, $name) = explode(' : ', $line, 2);
        $day = trim($day);
        $base_holidays[] = [
            'day' => $day,
            'name' => trim($name),
            'is_active' => isset($active_holidays[$day])
        ];
    }
}

// 念のため日付でソート
usort($base_holidays, function($a, $b) {
    return strcmp($a['day'], $b['day']);
});

?>
<h2>祝日・休日管理</h2>

<?php if ($flash_message): ?>
<div class="notice-message" style="color: <?= $flash_message['type'] === 'error' ? '#f44336' : '#4CAF50' ?>; margin-bottom: 1.5em; font-weight: bold;">
    <?= htmlspecialchars($flash_message['text'], ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>

<h3>祝日・休日設定</h3>
<p style="font-size: 0.9em; color: var(--secondary-text-color); margin-top: 0;">
    システムで実際に休業日として扱う日付にチェックを入れて、「設定」ボタンを押してください。
</p>
<form action="" method="post">
    <input type="hidden" name="function" value="modh">
    <div class="table-container">
        <table class="absp-table">
            <thead>
                <tr>
                    <th style="width: 80px;">休業日</th>
                    <th style="width: 150px;">日付</th>
                    <th>名称</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($base_holidays)): ?>
                    <tr><td colspan="3" style="text-align: center; padding: 20px;">データがありません。下の「再取得」ボタンからデータを取得してください。</td></tr>
                <?php else: ?>
                    <?php foreach($base_holidays as $holiday): ?>
                    <tr>
                        <td>
                            <input type="checkbox" name="day[]" value="<?= htmlspecialchars($holiday['day'], ENT_QUOTES, 'UTF-8') ?>" <?= $holiday['is_active'] ? 'checked' : '' ?>>
                        </td>
                        <td><?= htmlspecialchars($holiday['day'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($holiday['name'], ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <button type="submit" class="btn btn-primary" style="margin-top: 1em;">設定</button>
</form>


<h3>独自データ管理</h3>
<p style="font-size: 0.9em; color: var(--secondary-text-color); margin-top: 0;">
    会社の創立記念日など、独自の休日を追加・削除します。日付は YYYY-MM-DD 形式で入力してください。
</p>
<form action="" method="post" class="form-inline-group" style="margin-bottom: 0.5em;">
    <input type="hidden" name="function" value="addh">
    <label for="ndate">日付:</label>
    <input type="text" id="ndate" name="ndate" class="input-short2" placeholder="YYYY-MM-DD">
    <label for="nname">名称:</label>
    <input type="text" id="nname" name="nname" class="input-middle2">
    <button type="submit" class="btn">追加 / 更新</button>
</form>

<form action="" method="post" class="form-inline-group">
    <input type="hidden" name="function" value="deldate">
    <label for="ddate">削除する日付:</label>
    <input type="text" id="ddate" name="ddate" class="input-short2" placeholder="YYYY-MM-DD">
    <button type="submit" class="btn">削除</button>
</form>


<h3>祝日・休日情報 再取得</h3>
<p style="font-size: 0.9em; color: var(--secondary-text-color); margin-top: 0;">
    内閣府の公開情報（CSV）から最新の祝日データを取得し、現在の設定をすべて上書きします。<br>
    <strong style="color: #f44336;">注意: この操作を行うと、独自に追加したデータやチェックのON/OFF設定はすべて初期化されます。</strong>
</p>
<form action="" method="post" class="form-inline-group">
    <input type="hidden" name="function" value="gethbase">
    <label for="turl">取得元URL:</label>
    <input type="text" id="turl" name="turl" value="https://www8.cao.go.jp/chosei/shukujitsu/syukujitsu.csv" style="width: 30em;">
    <button type="submit" class="btn" onclick="return confirm('本当にすべての祝日・休日データを再取得してよろしいですか？現在の設定はすべて失われます。');">再取得</button>
</form>
