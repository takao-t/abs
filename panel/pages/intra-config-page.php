<?php
if (!defined('ABS_PANEL_INCLUDED')) {
    die("Direct access is not permitted.");
}

global $ami;

// POST時処理
$flash_message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['function'])) {
    $function = $_POST['function'];

    switch ($function) {
        case 'iopsetdigits':
            if (isset($_POST['iop_digits'])) {
                $ami->putDbItem('ABS/IOP', 'DIGITS', trim($_POST['iop_digits']));
                $flash_message = ['type' => 'success', 'text' => '拠点番号の桁数を更新しました。'];
            }
            break;

        case 'setuphereinfo':
            // 自局情報設定
            $p_iop_here = trim($_POST['iop_here'] ?? '');
            $p_iop_here_name = trim($_POST['iop_here_name'] ?? '');
            $p_iop_here_node = trim($_POST['iop_here_node'] ?? '');
            $p_iop_here_user = trim($_POST['iop_here_user'] ?? '');
            $p_iop_here_pass = trim($_POST['iop_here_pass'] ?? '');

            $ami->putDbItem('ABS/IOP', 'HERE', $p_iop_here);
            $ami->putDbItem("ABS/IOP/$p_iop_here", 'NAME', $p_iop_here_name);

            $filename = ASTDIR . '/pjsip_trunk_intra_me.conf';
            $fcontent = "[$p_iop_here_node]\n";
            $fcontent .= "type = auth\n";
            $fcontent .= "auth_type = userpass\n";
            $fcontent .= "username = $p_iop_here_user\n";
            $fcontent .= "password = $p_iop_here_pass\n";
            file_put_contents($filename, $fcontent);
            $flash_message = ['type' => 'success', 'text' => '自局情報を更新しました。'];
            break;

        case 'iopentdel':
            // 対向情報削除
            if (isset($_POST['delent'])) {
                $p_delent = $_POST['delent'];
                $ami->delDbTree("ABS/IOP/$p_delent");
                $flash_message = ['type' => 'success', 'text' => "拠点[{$p_delent}]を削除しました。"];
            }
            break;

        case 'ioppadd':
            // 対向情報追加
            $p_iopp_num = trim($_POST['iopp_num'] ?? '');
            $p_iopp_name = trim($_POST['iopp_name'] ?? '');
            $p_iopp_ident = trim($_POST['iopp_ident'] ?? '');
            $p_iopp_addr_full = trim($_POST['iopp_addr'] ?? '');
            $p_iopp_user = trim($_POST['iopp_user'] ?? '');
            $p_iopp_pass = trim($_POST['iopp_pass'] ?? '');
            $p_iopp_here_node = trim($_POST['iopp_here_node'] ?? '');

            $p_iopp_addr = $p_iopp_addr_full;
            $p_iopp_port = '';
            if (strpos($p_iopp_addr_full, ':') !== false) {
                list($p_iopp_addr, $p_iopp_port) = explode(':', $p_iopp_addr_full, 2);
                $p_iopp_port = ':' . $p_iopp_port;
            }
            
            // テンプレートファイルの内容を取得 (テンプレートのパスは環境に合わせて要調整)
            $template_path = __DIR__ . '/templates/pjsip_trunk_intra.tmpl';
            if (file_exists($template_path)) {
                $content = file_get_contents($template_path);
                $content = str_replace('##TRUNKNAME##', $p_iopp_ident, $content);
                $content = str_replace('##IPADDR##', $p_iopp_addr, $content);
                $content = str_replace('##PORT##', $p_iopp_port, $content);
                $content = str_replace('##USERNAME##', $p_iopp_user, $content);
                $content = str_replace('##PASSWORD##', $p_iopp_pass, $content);
                $content = str_replace('##HEREAUTH##', $p_iopp_here_node, $content);

                $svfilename = ASTDIR . '/pjsip_trunk_intra_' . $p_iopp_ident . '.conf';
                file_put_contents($svfilename, $content);

                $ami->putDbItem("ABS/IOP/$p_iopp_num", 'NAME', $p_iopp_name);
                $ami->putDbItem("ABS/IOP/$p_iopp_num", 'TECH', 'PJSIP');
                $ami->putDbItem("ABS/IOP/$p_iopp_num", 'TRUNK', $p_iopp_ident);
                
                $tfnam = basename($svfilename);
                $flash_message = ['type' => 'success', 'text' => "{$tfnam} に接続情報を保存しました。pjsip.confに #include {$tfnam} を追加してください。"];
            } else {
                 $flash_message = ['type' => 'error', 'text' => 'テンプレートファイルが見つかりません。'];
            }
            break;

        case 'rgptset':
            // 鳴動パターン設定
            $ami->putDbItem('ABS/IOP', 'RGPT', $_POST['rgpt']);
            $flash_message = ['type' => 'success', 'text' => '鳴動パターンを更新しました。'];
            break;
    }

    if ($flash_message) {
        $_SESSION['flash_message'] = $flash_message;
    }
    header('Location: index.php?page=intra-config-page');
    exit;
}

// フラッシュメッセージの取得と表示
$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);


// GET時処理

// --- 自局情報の取得 ---
$iop_digits = $ami->getDbItem('ABS/IOP', 'DIGITS') ?? '2';
$iop_here = $ami->getDbItem('ABS/IOP', 'HERE') ?? '';
$iop_here_name = !empty($iop_here) ? $ami->getDbItem("ABS/IOP/$iop_here", 'NAME') : '';

$iop_here_node = '';
$iop_here_user = '';
$iop_here_pass = '';
$me_config_file = ASTDIR . '/pjsip_trunk_intra_me.conf';
if (file_exists($me_config_file)) {
    $me_config = file($me_config_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($me_config as $line) {
        if (preg_match('/^\[(.*?)\]$/', $line, $matches)) {
            $iop_here_node = $matches[1];
        } elseif (preg_match('/^username\s*=\s*(.*)/', $line, $matches)) {
            $iop_here_user = $matches[1];
        } elseif (preg_match('/^password\s*=\s*(.*)/', $line, $matches)) {
            $iop_here_pass = $matches[1];
        }
    }
}

// --- 鳴動パターンの取得 ---
$rgpt = $ami->getDbItem('ABS/IOP', 'RGPT') ?? '0';

// --- 対向拠点一覧の取得 ---
$iops_raw = $ami->getDbFamily('ABS/IOP');
$iop_list = [];
foreach ($iops_raw as $line) {
    if (strpos($line, '/') !== false) {
        list($inum, $itemp) = explode('/', $line, 2);
        if (strpos($itemp, ':') !== false) {
            list($iitem, $ivalue) = explode(':', $itemp, 2);
            $iop_list[trim($inum)][trim($iitem)] = trim($ivalue);
        }
    }
}
ksort($iop_list); // 拠点番号でソート

?>
<h2>拠点間接続設定</h2>
<p style="color: var(--secondary-text-color);">PJSIPでの固定IPアドレスによる拠点間接続を設定します。</p>

<?php if ($flash_message): ?>
<div class="notice-message" style="color: <?= $flash_message['type'] === 'error' ? '#f44336' : '#4CAF50' ?>; border: 1px solid currentColor; padding: 1em; margin-bottom: 1.5em; border-radius: 4px;">
    <?= htmlspecialchars($flash_message['text'], ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>


<h3>基本設定</h3>
<form action="index.php?page=intra-config-page" method="post" class="form-inline-group" style="margin-bottom: 1.5em;">
    <input type="hidden" name="function" value="iopsetdigits">
    <label for="iop_digits">拠点番号の桁数:</label>
    <input type="text" id="iop_digits" name="iop_digits" value="<?= htmlspecialchars($iop_digits, ENT_QUOTES, 'UTF-8') ?>" class="input-xshort">
    <button type="submit" class="btn">設定</button>
</form>

<form action="index.php?page=intra-config-page" method="post" class="form-inline-group">
    <input type="hidden" name="function" value="rgptset">
    <label for="rgpt">拠点間着信時の鳴動パターン:</label>
    <select name="rgpt" id="rgpt">
        <?php for ($i = 0; $i <= 5; $i++): ?>
            <option value="<?= $i ?>" <?= ($rgpt == $i) ? 'selected' : '' ?>><?= $i ?></option>
        <?php endfor; ?>
    </select>
    <button type="submit" class="btn">設定</button>
</form>


<h3 style="margin-top: 2em;">自局情報</h3>
<form action="index.php?page=intra-config-page" method="post">
    <input type="hidden" name="function" value="setuphereinfo">
    <table class="absp-table" style="max-width: 800px;">
        <tbody>
            <tr>
                <td style="width: 150px;">拠点番号</td>
                <td><input type="text" name="iop_here" value="<?= htmlspecialchars($iop_here, ENT_QUOTES, 'UTF-8') ?>" class="input-xshort"></td>
                <td style="font-size: 0.9em; color: var(--secondary-text-color);">ここの拠点番号。数字のみ。桁数に注意 (例: 2桁なら02)。</td>
            </tr>
            <tr>
                <td>拠点名</td>
                <td><input type="text" name="iop_here_name" value="<?= htmlspecialchars($iop_here_name, ENT_QUOTES, 'UTF-8') ?>" class="input-middle2"></td>
                <td>ここの拠点名。日本語可。</td>
            </tr>
            <tr>
                <td>識別名</td>
                <td><input type="text" name="iop_here_node" value="<?= htmlspecialchars($iop_here_node, ENT_QUOTES, 'UTF-8') ?>" class="input-middle2"></td>
                <td>ここの識別名。英数字のみ。</td>
            </tr>
            <tr>
                <td>ユーザ名</td>
                <td><input type="text" name="iop_here_user" value="<?= htmlspecialchars($iop_here_user, ENT_QUOTES, 'UTF-8') ?>" class="input-middle2"></td>
                <td>外部からの接続を受け入れる際のユーザ名。英数字のみ。</td>
            </tr>
            <tr>
                <td>パスワード</td>
                <td><input type="text" name="iop_here_pass" value="<?= htmlspecialchars($iop_here_pass, ENT_QUOTES, 'UTF-8') ?>" class="input-middle2"></td>
                <td>外部からの接続を受け入れる際のパスワード。英数字のみ。</td>
            </tr>
        </tbody>
    </table>
    <button type="submit" class="btn" style="margin-top: 1em;">自局情報を設定</button>
</form>


<h3 style="margin-top: 2em;">対向拠点一覧</h3>
<div class="table-container">
    <table class="absp-table">
        <thead>
            <tr>
                <th>拠点番号</th>
                <th>拠点名</th>
                <th>テクノロジ</th>
                <th>トランク</th>
                <th style="width: 80px;">操作</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($iop_list)): ?>
                <tr><td colspan="5" style="text-align: center; color: var(--secondary-text-color);">対向拠点は登録されていません。</td></tr>
            <?php else: ?>
                <?php foreach($iop_list as $num => $details): ?>
                    <?php if (empty($details['NAME'])) continue; // NAMEがないエントリは表示しない ?>
                    <tr>
                        <td><?= htmlspecialchars($num, ENT_QUOTES, 'UTF-8') ?><?= ($num == $iop_here) ? ' (自局)' : '' ?></td>
                        <td><?= htmlspecialchars($details['NAME'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($details['TECH'] ?? '---', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($details['TRUNK'] ?? '---', ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <form action="index.php?page=intra-config-page" method="post" onsubmit="return confirm('拠点[<?= htmlspecialchars($num, ENT_QUOTES, 'UTF-8') ?>]を削除しますか？');">
                                <input type="hidden" name="function" value="iopentdel">
                                <input type="hidden" name="delent" value="<?= htmlspecialchars($num, ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" class="btn btn-row">削除</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>


<h3 style="margin-top: 2em;">対向拠点追加</h3>
<p style="font-size: 0.9em; color: var(--secondary-text-color); margin-top: 0;">
    対向を追加する前に、自局情報が正しく設定されていることを確認してください。
</p>
<form action="index.php?page=intra-config-page" method="post">
    <input type="hidden" name="function" value="ioppadd">
    <table class="absp-table" style="max-width: 800px;">
        <tbody>
            <tr>
                <td style="width: 150px;">自局識別名 (参考)</td>
                <td><input type="text" name="iopp_here_node" value="<?= htmlspecialchars($iop_here_node, ENT_QUOTES, 'UTF-8') ?>" class="input-middle2" readonly></td>
                <td style="font-size: 0.9em; color: var(--secondary-text-color);">自局の識別情報です (変更は自局情報から)。</td>
            </tr>
            <tr>
                <td>拠点番号</td>
                <td><input type="text" name="iopp_num" class="input-xshort"></td>
                <td>接続先の拠点番号。数字のみ。桁数に注意。</td>
            </tr>
            <tr>
                <td>拠点名</td>
                <td><input type="text" name="iopp_name" class="input-middle2"></td>
                <td>接続先の拠点名。日本語可。</td>
            </tr>
            <tr>
                <td>IPアドレス:ポート</td>
                <td><input type="text" name="iopp_addr" class="input-middle"></td>
                <td>接続先のIPアドレス。例: 192.168.1.100:5060</td>
            </tr>
            <tr>
                <td>識別名 (トランク名)</td>
                <td><input type="text" name="iopp_ident" class="input-middle2"></td>
                <td>接続先の識別名。英数字のみ。</td>
            </tr>
            <tr>
                <td>認証ユーザ名</td>
                <td><input type="text" name="iopp_user" class="input-middle2"></td>
                <td>接続先の認証ユーザ名。英数字のみ。</td>
            </tr>
            <tr>
                <td>認証パスワード</td>
                <td><input type="text" name="iopp_pass" class="input-middle2"></td>
                <td>接続先の認証パスワード。英数字のみ。</td>
            </tr>
        </tbody>
    </table>
    <button type="submit" class="btn" style="margin-top: 1em;">対向拠点を追加</button>
</form>
