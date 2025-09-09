<?php
if (!defined('ABS_PANEL_INCLUDED')) {
    die("Direct access is not permitted.");
}
?>

<h2>発信経路</h2>

<div class="flow-container">

    <div class="flow-section">
        <h3>キー発信</h3>
        <div class="flow-path">
            <div class="flow-item">
                <a href="index.php?help=outgoing-1-1.html" class="btn">外線キー押下捕捉</a>
            </div>
            <div class="flow-arrow"></div>
            <div class="flow-item">
                <a href="index.php?page=keysys-config-page" class="btn">キーシステム設定</a>
            </div>
        </div>
    </div>

    <div class="flow-section">
        <h3>プレフィクス発信</h3>
        <div class="flow-path">
            <div class="flow-item">
                <a href="index.php?help=outgoing-2-1.html" class="btn">プレフィクスあり</a>
            </div>
            <div class="flow-arrow"></div>
            <div class="flow-item">
                <a href="index.php?page=ogr-config-page#ogp" class="btn">プレフィクス設定</a>
            </div>
            <div class="flow-branch">
                <div class="flow-path" style="margin-bottom: 0;">
                    <div class="flow-connector">-KEY-></div>
                    <div class="flow-item">
                        <a href="index.php?page=keysys-config-page" class="btn">キーシステム設定</a>
                    </div>
                </div>
                <div class="flow-path" style="margin-bottom: 0;">
                    <div class="flow-connector">-NKS-></div>
                    <div class="flow-item">
                        <a href="index.php?page=ogr-config-page#nks" class="btn">ノーキーシステム</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="flow-section">
        <h3>特番捕捉発信</h3>
        <div class="flow-path">
            <div class="flow-item">
                <div class="btn-like">*56[キー番号]</div>
            </div>
            <div class="flow-arrow"></div>
            <div class="flow-item">
                <a href="index.php?page=ogr-config-page#d56option" class="btn">キー捕捉特番設定</a>
            </div>
            <div class="flow-arrow"></div>
            <div class="flow-item">
                <a href="index.php?page=keysys-config-page" class="btn">キーシステム設定</a>
            </div>
        </div>
        <div class="flow-path">
            <div class="flow-item">
                <div class="btn-like">*57[番号]</div>
            </div>
             <div class="flow-arrow"></div>
            <div class="flow-item">
                 <a href="index.php?help=outgoing-4-1.html" class="btn">*57特番ヘルプ</a>
            </div>
        </div>
    </div>
</div>
