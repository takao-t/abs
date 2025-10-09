<?php
if (!defined('ABS_PANEL_INCLUDED')) {
    die("Direct access is not permitted.");
}

// POSTリクエスト処理 (電話帳ファイルの生成)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // フォームから送信された値を取得
    $prefix = $_POST['prefix'] ?? '';
    $digit_threshold = (int)($_POST['digit_threshold'] ?? 8);

    // 入力値の基本的な検証(注:*571を使用するために無効化)
    //if (!empty($prefix)) {
    //    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'プレフィクスは数字で入力してください。'];
    //    header('Location: index.php?page=gs-xml-phonebook');
    //    exit;
    //}

    // Asterisk DBから電話帳データを取得
    $pb_entries = AbspFunctions\get_db_family('cidname');

    // 文字列としてXMLを生成
    $xml_content = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
    $xml_content .= '<AddressBook>' . PHP_EOL;

    if (!empty($pb_entries)) {
        foreach ($pb_entries as $entry) {
            // "番号 : 名前" の形式を分割
            $parts = array_map('trim', explode(':', $entry, 2));
            if (count($parts) < 2) continue;
            list($number, $name) = $parts;

            if (empty($number) || empty($name)) continue;

            // 指定桁数以上の番号にプレフィクスを付与
            $processed_number = $number;
            if (strlen($number) >= $digit_threshold) {
                $processed_number = $prefix . $number;
            }

            // XMLエンティティをエスケープ
            $safe_name = htmlspecialchars($name, ENT_XML1, 'UTF-8');

            $xml_content .= "  <Contact>" . PHP_EOL;
            $xml_content .= "    <LastName>{$safe_name}</LastName>" . PHP_EOL;
            $xml_content .= "    <FirstName></FirstName>" . PHP_EOL;
            $xml_content .= "    <Phone>" . PHP_EOL;
            $xml_content .= "      <phonenumber>{$processed_number}</phonenumber>" . PHP_EOL;
            $xml_content .= "      <accountindex>1</accountindex>" . PHP_EOL;
            $xml_content .= "    </Phone>" . PHP_EOL;
            $xml_content .= "    <Groups>" . PHP_EOL;
            $xml_content .= "      <groupid>0</groupid>" . PHP_EOL;
            $xml_content .= "    </Groups>" . PHP_EOL;
            $xml_content .= "  </Contact>" . PHP_EOL;
        }
    }

    $xml_content .= '</AddressBook>' . PHP_EOL;
    
    // XMLファイルとして保存
    $phonebook_file = PROV_PATH . '/' . PROV_GS . '/phonebook.xml';
    if (file_put_contents($phonebook_file, $xml_content)) {
        $_SESSION['flash_message'] = ['type' => 'success', 'text' => '電話帳ファイル (phonebook.xml) が正常に生成されました。'];
    } else {
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => '電話帳ファイルの生成に失敗しました。ディレクトリの書き込み権限を確認してください。'];
    }

    // PRGパターン(Post/Redirect/Get)で再読み込み
    header('Location: index.php?page=gs-xml-phonebook');
    exit;
}

// GETリクエスト処理 (ページの表示)

// セッションからフラッシュメッセージを取得し、すぐに削除
$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

// Asterisk DBから現在の電話帳データを取得して表示用に使用
$pb_entries = AbspFunctions\get_db_family('cidname');

?>
<h2>Grandstream 用電話帳生成</h2>

<?php
// フラッシュメッセージの表示
if ($flash_message) {
    $message_color = $flash_message['type'] === 'success' ? '#4CAF50' : '#f44336';
    echo '<div class="notice-message" style="color: ' . $message_color . ';">' . htmlspecialchars($flash_message['text'], ENT_QUOTES, 'UTF-8') . '</div>';
}
?>

<p style="font-size: 0.9em; color: var(--secondary-text-color); margin-top: 0;">
    Asterisk内部の電話帳(発信者名)情報を元に、Grandstream電話機用のXML形式の電話帳ファイルを生成します。
</p>

<h3>設定値の入力</h3>
<form method="POST" action="index.php?page=gs-xml-phonebook">
    <div class="form-inline-group">
        <label for="prefix">電話帳発信時のプレフィクス:</label>
        <input type="text" id="prefix" name="prefix" class="input-short3" maxlength="4">
    </div>
    <div class="form-inline-group" style="margin-top: 1em;">
        <label for="digit_threshold">外線として扱う番号の桁数:</label>
        <select id="digit_threshold" name="digit_threshold" class="input-short2">
            <?php for ($i = 8; $i <= 14; $i++): ?>
                <option value="<?= $i ?>" <?= ($i == 10) ? 'selected' : '' ?>><?= $i ?>桁以上</option>
            <?php endfor; ?>
        </select>
    </div>
    <div style="margin-top: 1.5em;">
        <button type="submit" class="btn btn-primary">電話帳生成</button>
    </div>
</form>

<hr style="margin: 2em 0;">

<h3>現在の電話帳データ (cidname)</h3>
<div class="table-container">
    <table class="absp-table">
        <thead>
            <tr>
                <th style="width: 200px;">電話番号</th>
                <th>名前</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($pb_entries)): ?>
                <?php foreach ($pb_entries as $entry): ?>
                    <?php
                        $parts = array_map('trim', explode(':', $entry, 2));
                        $number = $parts[0] ?? '';
                        $name = $parts[1] ?? '';
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($number, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="2">電話帳データは登録されていません。</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
