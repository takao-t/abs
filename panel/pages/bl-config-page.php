<?php
if (!defined('ABS_PANEL_INCLUDED')) {
    die("Direct access is not permitted.");
}

// POST時処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $flash_message = ['type' => 'success', 'text' => '設定を保存しました。'];
    $function = $_POST['function'] ?? '';

    switch ($function) {
        case 'newadd': // 新規追加
            $p_blnumber = trim($_POST['blnumber'] ?? '');
            if (ctype_digit($p_blnumber)) {
                $nowdt = new DateTime('NOW');
                $tmpdt = $nowdt->format('Y-m-d/H:i:s');
                AbspFunctions\put_db_item('ABS/blocklist', $p_blnumber, $tmpdt);
                $flash_message['text'] = "番号 {$p_blnumber} を着信拒否リストに追加しました。";
            } else {
                $flash_message = ['type' => 'error', 'text' => '番号は数字のみで指定してください。'];
            }
            break;

        case 'entdel': // 一括削除
            if (!empty($_POST['delcb'])) {
                $deleted_count = 0;
                foreach ($_POST['delcb'] as $entry) {
                    AbspFunctions\del_db_item('ABS/blocklist', $entry);
                    $deleted_count++;
                }
                $flash_message['text'] = "{$deleted_count}件の番号を削除しました。";
            } else {
                $flash_message = ['type' => 'info', 'text' => '削除する番号が選択されていません。'];
            }
            break;

        case 'alcont': // 許可リストのみ着信
            if (isset($_POST['alist']) && $_POST['alist'] === 'on') {
                AbspFunctions\put_db_item('ABS/BLC', 'ALIST', '1');
            } else {
                AbspFunctions\del_db_item('ABS/BLC', 'ALIST');
            }
            break;

        case 'anonupdate': // 匿名着信設定
            if (isset($_POST['anonopt']) && $_POST['anonopt'] === 'on') {
                AbspFunctions\put_db_item('ABS', 'ANB', '1');
            } else {
                AbspFunctions\del_db_item('ABS', 'ANB');
            }
            break;
            
        case 'bhnumber': // ゴミ箱内線登録
            $p_bh_ext = trim($_POST['bhnum'] ?? '');
            $c_bh_ext = AbspFunctions\get_db_item('ABS/ERV', 'trashbin');
            
            if ($p_bh_ext === '') { // 入力が空なら削除
                if ($c_bh_ext !== "") {
                    AbspFunctions\del_db_item('ABS/EXT', $c_bh_ext);
                    AbspFunctions\del_db_item('ABS/ERV', 'trashbin');
                }
            } elseif (AbspFunctions\get_db_item('ABS/EXT', $p_bh_ext) !== "") {
                $flash_message = ['type' => 'error', 'text' => 'その内線番号は既に使用されています。'];
            } else {
                if ($c_bh_ext !== "") AbspFunctions\del_db_item('ABS/EXT', $c_bh_ext);
                AbspFunctions\put_db_item('ABS/LOCALTECH', 'trashbin', 'Local');
                AbspFunctions\put_db_item('ABS/EXT', 'trashbin', $p_bh_ext);
                AbspFunctions\put_db_item('ABS/ERV', 'trashbin', $p_bh_ext);
            }
            break;

        case 'blccupdate': // 拒否時カスタムcontext
            AbspFunctions\put_db_item('ABS', 'BLC', trim($_POST['blcc'] ?? ''));
            break;
    }

    if ($flash_message) {
        $_SESSION['flash_message'] = $flash_message;
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// GET時処理
$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

// 各種設定値の取得
$alist_setting = AbspFunctions\get_db_item('ABS/BLC', 'ALIST') === '1';
$anon_setting = AbspFunctions\get_db_item('ABS', 'ANB') === '1';
$bhnum_setting = AbspFunctions\get_db_item('ABS/ERV', 'trashbin') ?: '';
$blcc_setting = AbspFunctions\get_db_item('ABS', 'BLC') ?: '';

// 拒否リスト全件取得
$blocklist_all = [];
$db_entries = AbspFunctions\get_db_family('ABS/blocklist');
if (is_array($db_entries)) {
    foreach ($db_entries as $line) {
        list($pnum, $pdate) = explode(' : ', $line, 2);
        $blocklist_all[] = ['number' => trim($pnum), 'date' => trim($pdate)];
    }
}
// 日付の降順（新しいものが上）にソート
usort($blocklist_all, function($a, $b) {
    return strcmp($b['date'], $a['date']);
});


// ページネーション処理
$items_per_page = 10;
$total_items = count($blocklist_all);
$total_pages = ceil($total_items / $items_per_page);
$current_page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
if ($current_page > $total_pages && $total_pages > 0) $current_page = $total_pages;
$offset = ($current_page - 1) * $items_per_page;
$paginated_list = array_slice($blocklist_all, $offset, $items_per_page);

?>
<h2>着信拒否管理</h2>

<?php if ($flash_message): ?>
<div class="notice-message" style="color: <?= $flash_message['type'] === 'error' ? '#f44336' : '#4CAF50' ?>; margin-bottom: 1.5em; font-weight: bold;">
    <?= htmlspecialchars($flash_message['text'], ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>

<h3>拒否番号 追加</h3>
<form action="" method="POST" class="form-inline-group">
    <input type="hidden" name="function" value="newadd">
    <label for="blnumber">番号:</label>
    <input type="text" id="blnumber" name="blnumber" class="input-middle2">
    <button type="submit" class="btn btn-primary">追加</button>
</form>

<h3>登録済み番号一覧</h3>
<form action="" method="POST">
    <input type="hidden" name="function" value="entdel">
    <div class="table-container">
        <table class="absp-table">
            <thead>
                <tr>
                    <th style="width: 50px;">削除</th>
                    <th>番号</th>
                    <th>登録日時</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($paginated_list)): ?>
                    <tr><td colspan="3" style="text-align: center; padding: 20px;">登録されている番号はありません。</td></tr>
                <?php else: ?>
                    <?php foreach($paginated_list as $entry): ?>
                    <tr>
                        <td><input type="checkbox" name="delcb[]" value="<?= htmlspecialchars($entry['number'], ENT_QUOTES, 'UTF-8') ?>"></td>
                        <td><?= htmlspecialchars($entry['number'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($entry['date'], ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($total_items > 0): ?>
        <button type="submit" class="btn" style="margin-top: 1em;" onclick="return confirm('選択した番号を本当に削除しますか？');">選択した項目を削除</button>
    <?php endif; ?>
</form>

<div class="form-inline-group" style="justify-content: space-between; margin-top: 1em;">
    <div>
        <?php if ($total_items > 0): ?>
        <span><?= $total_items ?>件中 <?= $offset + 1 ?> - <?= min($offset + $items_per_page, $total_items) ?>件表示 (<?= $current_page ?> / <?= $total_pages ?>ページ)</span>
        <?php endif; ?>
    </div>
    <div class="form-inline-group">
        <a href="?page=bl-config-page&p=1" class="btn <?= $current_page <= 1 ? 'disabled' : '' ?>">最初へ</a>
        <a href="?page=bl-config-page&p=<?= $current_page - 1 ?>" class="btn <?= $current_page <= 1 ? 'disabled' : '' ?>">前へ</a>
        <a href="?page=bl-config-page&p=<?= $current_page + 1 ?>" class="btn <?= $current_page >= $total_pages ? 'disabled' : '' ?>">次へ</a>
        <a href="?page=bl-config-page&p=<?= $total_pages ?>" class="btn <?= $current_page >= $total_pages ? 'disabled' : '' ?>">最後へ</a>
    </div>
</div>
<style>.btn.disabled { pointer-events: none; opacity: 0.5; }</style>

<h3 id='blcontext'>各種設定</h3>
<form action="" method="post" class="form-inline-group">
    <input type="hidden" name="function" value="alcont">
    <input type="checkbox" id="alist" name="alist" value="on" <?= $alist_setting ? 'checked' : '' ?>>
    <label for="alist">許可リスト（発信者名登録済み）の番号のみ着信を許可する</label>
    <button type="submit" class="btn">設定</button>
</form>

<form action="" method="post" class="form-inline-group">
    <input type="hidden" name="function" value="anonupdate">
    <input type="checkbox" id="anonopt" name="anonopt" value="on" <?= $anon_setting ? 'checked' : '' ?>>
    <label for="anonopt">匿名（anonymous）着信を拒否する</label>
    <button type="submit" class="btn">設定</button>
</form>

<form action="" method="post" class="form-inline-group">
    <input type="hidden" name="function" value="bhnumber">
    <label for="bhnum">ゴミ箱内線:</label>
    <input type="text" id="bhnum" name="bhnum" class="input-short" value="<?= htmlspecialchars($bhnum_setting, ENT_QUOTES, 'UTF-8') ?>">
    <button type="submit" class="btn">設定</button>
</form>

<form action="" method="post" class="form-inline-group">
    <input type="hidden" name="function" value="blccupdate">
    <label for="blcc">着信拒否時のカスタムcontext:</label>
    <input type="text" id="blcc" name="blcc" class="input-middle" value="<?= htmlspecialchars($blcc_setting, ENT_QUOTES, 'UTF-8') ?>">
    <button type="submit" class="btn">設定</button>
</form>
