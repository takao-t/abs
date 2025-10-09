<?php
if (!defined('ABS_PANEL_INCLUDED')) {
    die("Direct access is not permitted.");
}

// メニュー構造、およびページのアクセス許可を定義する設定ファイル
$menuConfig = [
    // 左カラムメニューに表示する構造
    'structure' => [
        // TOP
        ['page' => 'top', 'text' => 'ABS Panel', 'type' => 'top'],

        // 内線セクション
        ['type' => 'divider'],
        [
            'title' => '内線設定',
            'type' => 'submenu',
            'items' => [
                ['page' => 'ext-config-page', 'text' => '内線情報設定'],
                ['page' => 'group-config-page', 'text' => '内線グループ設定'],
                ['page' => 'fap-config-page', 'text' => 'FAユーザ設定'],
            ]
        ],

        // 発信セクション
        ['type' => 'divider'],
        [
            'title' => '発信設定',
            'type' => 'submenu',
            'items' => [
                ['page' => 'outgoing-page', 'text' => '発信経路'],
                ['page' => 'ogr-config-page', 'text' => '発信設定'],
                ['page' => 'qd-config-page', 'text' => '短縮ダイヤル管理'],
            ]
        ],

        // 着信セクション
        ['type' => 'divider'],
        [
            'title' => '着信設定',
            'type' => 'submenu',
            'items' => [
                ['page' => 'incoming-page', 'text' => '着信経路'],
                ['page' => 'did-config-page', 'text' => 'ダイヤルイン設定'],
                ['page' => 'keyin-config-page', 'text' => 'キー着信設定'],
                ['page' => 'ivr-config-page', 'text' => 'IVR設定'],
            ]
        ],

        // PBX動作設定セクション
        ['type' => 'divider'],
        [
            'title' => 'PBX動作設定',
            'type' => 'submenu',
            'items' => [
                ['page' => 'keysys-config-page', 'text' => 'キーシステム設定'],
                ['page' => 'tcs-config-page', 'text' => '時間外制御設定'],
                ['page' => 'vm-config-page', 'text' => '留守録設定'],
                ['page' => 'cid-config-page', 'text' => '発信者名管理'],
                ['page' => 'pbx-detail-page', 'text' => 'PBX機能詳細設定'],
            ]
        ],

        // 管理機能セクション
        ['type' => 'divider'],
        [
            'title' => '管理機能',
            'type' => 'submenu',
            'items' => [
                ['page' => 'call-log-page', 'text' => '着信記録管理'],
                ['page' => 'bl-config-page', 'text' => '着信拒否管理'],
                ['page' => 'system-config-page', 'text' => 'システム設定'],
                ['page' => 'exec-cmd-page', 'text' => 'コマンド実行'],
            ]
        ],

        // ツールセクション
        ['type' => 'divider'],
        [
            'title' => 'ツール',
            'type' => 'submenu',
            'items' => [
                ['page' => 'file-edit', 'text' => 'ファイル編集'],
                ['page' => 'hint-generator', 'text' => '内線ヒント生成'],
                ['page' => 'trunk-generator', 'text' => 'トランク設定'],
                ['page' => 'prov-generator', 'text' => '電話機設定ファイル'],
                ['page' => 'intra-config-page', 'text' => '拠点間接続設定'],
                ['page' => 'remote-exten-page', 'text' => 'リモート内線設定'],
                ['page' => 'backup-restore-page', 'text' => 'バックアップ/リストア'],
            ]
        ],
    ],

    // --- 2. メニュー項目にはないが、アクセスを許可するページ ---
    'allowed_non_menu_pages' => [
        'holiday-config-page',
        'call-log-disp',
        'block-log-disp',
        'view-hostinfo-page',
        'view-exten-page',
        'view-peer-page',
        'prov-gs-master',
        'prov-gs-peer',
        'prov-gs-fap-peer',
        'prov-gs-keys',
        'gs-xml-phonebook',
        'prov-pana-master',
        'prov-pana-peer-generator',
        'prov-pana-keys',
        'access_denied',
    ],

    // --- 3. メニュー項目にはないが、アクセスを許可するヘルプファイル ---
    'allowed_non_menu_helps' => [
        'incoming-1-1.html',
        'outgoing-1-1.html',
        'outgoing-2-1.html',
        'outgoing-3-1.html',
        'outgoing-4-1.html',
    ],

    // --- 4. 特定のDB設定によってアクセスが制限されるページ ---
    // キー: ページ名, 値: チェックするAstDBの Family/Key
    'restricted_pages' => [
        'file-edit' => 'ABS/PANEL/PGRESTRICTED',
    ],
];

// 後方互換性のために、古い変数名でもアクセスできるようにしておく
$menuStructure = $menuConfig['structure'];
