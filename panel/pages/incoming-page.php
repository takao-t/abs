<?php
if (!defined('ABS_PANEL_INCLUDED')) {
    die("Direct access is not permitted.");
}
?>

<h2>着信経路</h2>

<div class="flow-container">

    <div class="flow-section">
        <div class="flow-path">
            <div class="flow-item"><a href="index.php?help=incoming-1-1.html" class="btn">着信 (全番号)</a></div>
            <div class="flow-arrow"></div>
            <div class="flow-item"><a href="index.php?page=call-log-page#inlog" class="btn">着信記録</a></div>
        </div>
    </div>

    <div class="flow-arrow-down"></div>

    <div class="flow-section">
        <div class="flow-path">
            <div class="flow-item"><a href="index.php?page=bl-config-page" class="btn">着信拒否チェック</a></div>
            <div class="flow-arrow"></div>
            <div class="flow-item"><a href="index.php?page=call-log-page" class="btn">着信拒否記録</a></div>
            <div class="flow-arrow"></div>
            <div class="flow-item"><a href="index.php?page=bl-config-page#blcontext" class="btn">着信拒否context</a></div>
        </div>
    </div>

    <div class="flow-arrow-down"></div>

    <div class="flow-section">
        <div class="flow-path">
            <div class="flow-item"><a href="index.php?page=cid-config-page" class="btn">発信者名参照</a></div>
        </div>
    </div>

    <div class="flow-arrow-down"></div>

    <div class="flow-section">
        <div class="flow-path">
            <div class="flow-item"><a href="index.php?page=tcs-config-page" class="btn">時間外制御チェック</a></div>
            <div class="flow-arrow"></div>
            <div class="flow-item"><a href="index.php?page=vm-config-page" class="btn">留守録設定</a></div>
        </div>
    </div>

    <div class="flow-arrow-down"></div>

    <div class="flow-section">
        <div class="flow-path">
            <div class="flow-item"><a href="index.php?page=ivr-config-page" class="btn">IVR処理チェック</a></div>
            <div class="flow-arrow"></div>
            <div class="flow-item"><a href="index.php?page=ivr-config-page" class="btn">IVR処理</a></div>
        </div>
    </div>

    <div class="flow-arrow-down"></div>

    <div class="flow-section">
        <div class="flow-path">
            <div class="flow-item"><a href="index.php?page=did-config-page" class="btn">ダイヤルイン着信</a></div>
            <div class="flow-arrow"></div>
            <div class="flow-branch">
                 <div class="flow-path" style="margin-bottom: 0;">
                    <div class="flow-item"><a href="index.php?page=ext-config-page" class="btn">着信先内線</a></div>
                </div>
                 <div class="flow-path" style="margin-bottom: 0;">
                    <div class="flow-item"><a href="index.php?page=group-config-page" class="btn">着信先グループ</a></div>
                </div>
            </div>
        </div>
    </div>

    <div class="flow-arrow-down"></div>

    <div class="flow-section">
        <div class="flow-path">
            <div class="flow-item"><a href="index.php?page=keyin-config-page" class="btn">キー着信</a></div>
            <div class="flow-arrow"></div>
            <div class="flow-item"><a href="index.php?page=keysys-config-page" class="btn">キーシステム設定</a></div>
        </div>
    </div>
</div>
