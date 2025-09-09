<?php
if (!defined('ABS_PANEL_INCLUDED')) {
    die("Direct access is not permitted.");
}
$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);
?>
<h2>トップページ</h2>
<p>左のメニューから設定したい項目を選択してください。各メニュー項目の概要は以下で確認できます。</p>

<?php if ($flash_message): ?>
<div class="notice-message" style="color: <?= $flash_message['type'] === 'error' ? '#f44336' : '#4CAF50' ?>; margin-bottom: 1.5em; font-weight: bold;">
    <?= htmlspecialchars($flash_message['text'], ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>

<div class="help-accordion">
    <div class="help-item">
        <div class="help-title">内線設定</div>
        <div class="help-content">
            <p><strong>内線情報設定:</strong> 内線番号の割り当て、発信権限などを設定します。</p>
            <p><strong>内線グループ設定:</strong> 内線のグループを作成し、グループ着信などに使用します。</p>
            <p><strong>FAユーザ設定:</strong> フリーアドレス用のユーザーとPINを設定します。</p>
        </div>
    </div>

    <div class="help-item">
        <div class="help-title">発信設定</div>
        <div class="help-content">
            <p><strong>発信経路:</strong> 発信時の処理フローを確認し、各設定ページへジャンプします。</p>
            <p><strong>発信設定:</strong> 0発信などのプレフィクスや特番に関する設定を行います。</p>
            <p><strong>短縮ダイヤル管理:</strong> 共通の短縮ダイヤルを設定します。</p>
        </div>
    </div>
    
    <div class="help-item">
        <div class="help-title">着信設定</div>
        <div class="help-content">
            <p><strong>着信経路:</strong> 着信時の処理フローを確認し、各設定ページへジャンプします。</p>
            <p><strong>ダイヤルイン設定:</strong> ダイヤルイン番号に応じて着信先の内線やグループを設定します。</p>
            <p><strong>キー着信設定:</strong> キーシステム利用時の着信動作を設定します。</p>
            <p><strong>IVR設定:</strong> 自動音声応答（IVR）機能に関する設定を行います。</p>
        </div>
    </div>
    
    <div class="help-item">
        <div class="help-title">PBX動作設定</div>
        <div class="help-content">
            <p><strong>キーシステム設定:</strong> キーボタンの動作など、キーシステムに関する設定を行います。</p>
            <p><strong>時間外制御設定:</strong> 営業時間や休日の設定と、時間外の着信動作を設定します。</p>
            <p><strong>留守録設定:</strong> 時間外アナウンスの音声や、留守番電話に関する設定を行います。</p>
            <p><strong>発信者名管理:</strong> 電話番号と表示される名前を紐付けます。</p>
            <p><strong>PBX機能詳細設定:</strong> PBX動作に関わる詳細な設定を行います。</p>
        </div>
    </div>

    <div class="help-item">
        <div class="help-title">管理機能</div>
        <div class="help-content">
            <p><strong>着信記録管理:</strong> 着信履歴の有効/無効や、世代管理を行います。</p>
            <p><strong>着信拒否管理:</strong> 着信を拒否したい番号のリストを管理します。</p>
            <p><strong>システム設定:</strong> Web UIのユーザー管理などを行います。</p>
            <p><strong>コマンド実行:</strong> AsteriskのCLIコマンドを直接実行します（注意が必要）。</p>
        </div>
    </div>

    <div class="help-item">
        <div class="help-title">ツール</div>
        <div class="help-content">
            <p><strong>ファイル編集:</strong> Asteriskの設定ファイルを直接編集します（危険なため非推奨）。</p>
            <p><strong>トランク設定:</strong> PJSIPトランク用の設定ファイルをテンプレートから生成します。</p>
            <p><strong>電話機設定ファイル:</strong> 電話機用の設定ファイル(プロビジョニング)を生成します。</p>
        </div>
    </div>
</div>
