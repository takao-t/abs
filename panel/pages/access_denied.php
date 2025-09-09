<?php
if (!defined('ABS_PANEL_INCLUDED')) {
    die("Direct access is not permitted.");
}
?>
<h2>アクセスが拒否されました</h2>
<div class="notice-message" style="border: 1px solid #f44336; padding: 1em; border-radius: 4px;">
    <p style="margin-top:0;">
        <strong>あなたのIPアドレス (<?= htmlspecialchars($_SERVER['REMOTE_ADDR'], ENT_QUOTES, 'UTF-8') ?>) からのアクセスは許可されていません。</strong>
    </p>
    <p style="margin-bottom:0;">
        管理者に連絡して、ACL(アクセス制御リスト)にあなたのIPアドレスを追加するよう依頼してください。
    </p>
</div>
