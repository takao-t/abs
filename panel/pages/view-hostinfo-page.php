<?php
if (!defined('ABS_PANEL_INCLUDED')) {
    die("Direct access is not permitted.");
}

/**
 * 'ip addr show'コマンドを実行し、ネットワークインターフェースの情報を構造化された配列で返す
 * @return array
 */
function get_network_interfaces(): array {
    $interfaces = [];
    $cmd_output = [];
    exec('ip addr show', $cmd_output);

    $current_interface = null;
    foreach ($cmd_output as $line) {
        // 新しいインターフェース定義の始まりを検出 (例: "2: eth0:")
        if (preg_match('/^\d+:\s+([^:]+):/', $line, $matches)) {
            $interface_name = $matches[1];
            $current_interface = &$interfaces[$interface_name];
            $current_interface['name'] = $interface_name;
            $current_interface['ips'] = [];
            $current_interface['mac'] = 'N/A';
        }

        if ($current_interface) {
            // MACアドレスを検出
            if (preg_match('/link\/ether\s+([0-9a-f:]+)/', $line, $matches)) {
                $current_interface['mac'] = $matches[1];
            }
            // IPv4アドレスを検出
            if (preg_match('/inet\s+([0-9\.]+)\/\d+/', $line, $matches)) {
                $current_interface['ips'][] = $matches[1];
            }
        }
    }
    return $interfaces;
}

$network_interfaces = get_network_interfaces();
$system_info = php_uname();
$uptime_info = exec('uptime');

?>
<h2>ホスト情報</h2>

<h3>ネットワークインターフェース一覧 🌐</h3>
<div class="table-container">
    <table class="absp-table">
        <thead>
            <tr>
                <th>インターフェース名</th>
                <th>IPアドレス</th>
                <th>MACアドレス</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($network_interfaces)): ?>
                <tr><td colspan="3" style="text-align: center; padding: 20px;">ネットワークインターフェース情報を取得できませんでした。</td></tr>
            <?php else: ?>
                <?php foreach($network_interfaces as $interface): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($interface['name'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <td><?= htmlspecialchars(implode(', ', $interface['ips']), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($interface['mac'], ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<h3>システム情報</h3>
<div class="table-container">
    <table class="absp-table">
         <thead>
            <tr>
                <th style="width: 150px;">項目</th>
                <th>情報</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>システム</td>
                <td><?= htmlspecialchars($system_info, ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
            <tr>
                <td>Uptime</td>
                <td><?= htmlspecialchars($uptime_info, ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
        </tbody>
    </table>
</div>

<a href="index.php?page=system-config-page" class="btn" style="margin-top: 1em;">戻る</a>
