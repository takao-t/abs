<?php
if (!defined('ABS_PANEL_INCLUDED')) {
    die("Direct access is not permitted.");
}

global $ami;

// ページネーション設定
$items_per_page = 20;
$current_page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$offset = ($current_page - 1) * $items_per_page;

// DBファイルパスの決定
$dbver = trim($ami->getDbItem('ABS', 'CLOGVER'));
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
        $total_items = $logdb->querySingle("SELECT count(*) FROM abslog WHERE KIND='BLOCKED'");
        $total_pages = ceil($total_items / $items_per_page);
        if ($current_page > $total_pages && $total_pages > 0) {
             // 存在しないページが指定されたら最後のページへ
            $current_page = $total_pages;
            $offset = ($current_page - 1) * $items_per_page;
        }

        // 現在のページのデータを取得
        $qstr = "SELECT * FROM abslog WHERE KIND='BLOCKED' ORDER BY ID DESC LIMIT :limit OFFSET :offset";
        $stmt = $logdb->prepare($qstr);
        $stmt->bindValue(':limit', $items_per_page, SQLITE3_INTEGER);
        $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
        $res = $stmt->execute();
        
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $log_entries[] = $row;
        }
        $logdb->close();

    } catch (Exception $e) {
        $db_error = "ログDBファイルを開けません: " . $e->getMessage();
    }
}

?>
<h2>着信拒否履歴</h2>

<?php if ($db_error): ?>
    <p style="color: #f44336; font-weight: bold;"><?= htmlspecialchars($db_error, ENT_QUOTES, 'UTF-8') ?></p>
<?php else: ?>
    <div class="table-container">
        <table class="absp-table">
            <thead>
                <tr>
                    <th>日時</th>
                    <th>発信者番号</th>
                    <th>着信先</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($log_entries)): ?>
                    <tr><td colspan="3" style="text-align: center; padding: 20px;">着信拒否履歴はありません。</td></tr>
                <?php else: ?>
                    <?php foreach ($log_entries as $entry): ?>
                    <tr>
                        <td><?= htmlspecialchars($entry['TIMESTAMP'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($entry['NUMBER'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($entry['DESTNUM'], ENT_QUOTES, 'UTF-8') ?></td>
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
            <a href="?page=block-log-disp&p=1" class="btn <?= $current_page <= 1 ? 'disabled' : '' ?>">最初へ</a>
            <a href="?page=block-log-disp&p=<?= $current_page - 1 ?>" class="btn <?= $current_page <= 1 ? 'disabled' : '' ?>">前へ</a>
            <a href="?page=block-log-disp&p=<?= $current_page + 1 ?>" class="btn <?= $current_page >= $total_pages ? 'disabled' : '' ?>">次へ</a>
            <a href="?page=block-log-disp&p=<?= $total_pages ?>" class="btn <?= $current_page >= $total_pages ? 'disabled' : '' ?>">最後へ</a>
        </div>
    </div>
<?php endif; ?>
<br>
<a href="index.php?page=call-log-page" class="btn">戻る</a>
<style>.btn.disabled { pointer-events: none; opacity: 0.5; }</style>
