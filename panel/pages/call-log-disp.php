<?php
if (!defined('ABS_PANEL_INCLUDED')) {
    die("Direct access is not permitted.");
}

// POST時処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['function']) && $_POST['function'] == 'bladd') {
    $p_cid = trim($_POST['blcid'] ?? '');
    if (!empty($p_cid) && isset($_POST['blchecked']) && $_POST['blchecked'] === 'YES') {
        $nowdt = new DateTime('NOW');
        $tmpdt = $nowdt->format('Y-m-d/H:i:s');
        AbspFunctions\put_db_item('ABS/blocklist', $p_cid, $tmpdt);
        $_SESSION['flash_message'] = ['type' => 'success', 'text' => "{$p_cid} を着信拒否リストに登録しました。"];
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// GET時処理
$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

// ページネーション設定
$items_per_page = 20;
$current_page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$offset = ($current_page - 1) * $items_per_page;

// DBファイルパスの決定
$dbver = trim(AbspFunctions\get_db_item('ABS', 'CLOGVER'));
$dbfile = CLOGDB . (!empty($dbver) ? ".{$dbver}" : '');

$log_entries = [];
$total_items = 0;
$db_error = null;

if (!file_exists($dbfile)) {
    $db_error = "ログDBファイルがありません。";
} else {
    try {
        $logdb = new SQLite3($dbfile, SQLITE3_OPEN_READONLY);
        
        // 総件数を取得
        $total_items = $logdb->querySingle("SELECT count(*) FROM abslog WHERE KIND='INCOMING'");
        $total_pages = ceil($total_items / $items_per_page);
        if ($current_page > $total_pages && $total_pages > 0) {
             // 存在しないページが指定されたら最後のページへ
            $current_page = $total_pages;
            $offset = ($current_page - 1) * $items_per_page;
        }

        // 現在のページのデータを取得
        $qstr = "SELECT * FROM abslog WHERE KIND='INCOMING' ORDER BY ID DESC LIMIT :limit OFFSET :offset";
        $stmt = $logdb->prepare($qstr);
        $stmt->bindValue(':limit', $items_per_page, SQLITE3_INTEGER);
        $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
        $res = $stmt->execute();
        
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            // 着信先の詳細情報を取得
            $dest_info = '';
            if (AbspFunctions\get_db_item('ABS/TRUNK/' . $row['DESTNUM'] , 'KEY') !== "") $dest_info = '(キー着信)';
            elseif (AbspFunctions\get_db_item('ABS/DID' , $row['DESTNUM']) !== "") $dest_info = "(ダイヤルイン)";
            elseif (AbspFunctions\get_db_item('ABS/IVR/DIR/' . $row['DESTNUM'], 'CTX') !== "") $dest_info = "(IVRダイレクト)";
            elseif (AbspFunctions\get_db_item('ABS/IVR/NUM' , $row['DESTNUM']) !== "") $dest_info = "(IVR)";
            
            $row['dest_info'] = $dest_info;
            $row['cid_name'] = AbspFunctions\get_db_item('cidname', $row['NUMBER']) ?: '';
            $row['is_blocked'] = AbspFunctions\get_db_item('ABS/blocklist', $row['NUMBER']) !== "";
            $log_entries[] = $row;
        }
        $logdb->close();

    } catch (Exception $e) {
        $db_error = "ログDBファイルを開けません: " . $e->getMessage();
    }
}

?>
<h2>着信履歴</h2>

<?php if ($flash_message): ?>
<div class="notice-message" style="color: <?= $flash_message['type'] === 'error' ? '#f44336' : '#4CAF50' ?>; margin-bottom: 1.5em; font-weight: bold;">
    <?= htmlspecialchars($flash_message['text'], ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>

<?php if ($db_error): ?>
    <p style="color: #f44336; font-weight: bold;"><?= htmlspecialchars($db_error, ENT_QUOTES, 'UTF-8') ?></p>
<?php else: ?>
    <div class="table-container">
        <table class="absp-table">
            <thead>
                <tr>
                    <th>日時</th>
                    <th>発信者番号</th>
                    <th>発信者名</th>
                    <th>着信先</th>
                    <th style="width: 150px;">拒否登録</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($log_entries)): ?>
                    <tr><td colspan="5" style="text-align: center; padding: 20px;">着信履歴はありません。</td></tr>
                <?php else: ?>
                    <?php foreach ($log_entries as $entry): ?>
                    <tr>
                        <td><?= htmlspecialchars($entry['TIMESTAMP'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <a href="index.php?page=cid-config-page&post_pnum=<?= urlencode($entry['NUMBER']) ?>" style="<?= $entry['is_blocked'] ? 'color: #f44336; font-weight: bold;' : '' ?>">
                                <?= htmlspecialchars($entry['NUMBER'], ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($entry['cid_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($entry['DESTNUM'] . ' ' . $entry['dest_info'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?php if (!$entry['is_blocked']): ?>
                            <form action="" method="POST" class="form-inline-group" style="gap: 0.5em;">
                                <input type="hidden" name="function" value="bladd">
                                <input type="hidden" name="blcid" value="<?= htmlspecialchars($entry['NUMBER'], ENT_QUOTES, 'UTF-8') ?>">
                                <input type="checkbox" name="blchecked" value="YES" id="cb_<?= htmlspecialchars($entry['ID'], ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" class="btn btn-row">登録</button>
                            </form>
                            <?php else: ?>
                                <span style="color: #f44336;">登録済み</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="form-inline-group" style="justify-content: space-between; margin-top: 1em;">
        <div>
            <?php if ($total_items > 0): ?>
            <span><?= $total_items ?>件中 <?= $offset + 1 ?> - <?= min($offset + $items_per_page, $total_items) ?>件表示 (<?= $current_page ?> / <?= $total_pages ?>ページ)</span>
            <?php endif; ?>
        </div>
        <div class="form-inline-group">
            <a href="?page=call-log-disp&p=1" class="btn <?= $current_page <= 1 ? 'disabled' : '' ?>">最初へ</a>
            <a href="?page=call-log-disp&p=<?= $current_page - 1 ?>" class="btn <?= $current_page <= 1 ? 'disabled' : '' ?>">前へ</a>
            <a href="?page=call-log-disp&p=<?= $current_page + 1 ?>" class="btn <?= $current_page >= $total_pages ? 'disabled' : '' ?>">次へ</a>
            <a href="?page=call-log-disp&p=<?= $total_pages ?>" class="btn <?= $current_page >= $total_pages ? 'disabled' : '' ?>">最後へ</a>
        </div>
    </div>
<?php endif; ?>
<br>
<a href="index.php?page=call-log-page" class="btn">戻る</a>
<style>.btn.disabled { pointer-events: none; opacity: 0.5; }</style>
