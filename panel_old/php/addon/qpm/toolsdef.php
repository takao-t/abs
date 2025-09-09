<?php

class ToolsMenu
{
  const NAME = [
    'ファイル編集',
    '着信履歴管理',
    'トランク設定',
    '拠点間接続設定',
    'リモート内線',
    '内線ヒント生成',
    '電話機設定ファイル',
    'Slack連携',
    'QPM管理',
    'バックアップ',
    'リストア' ];

  const DESC = [
    '設定ファイル類の編集を行います',
    '着信履歴の参照と拒否番号への登録を行います',
    '必要な項目を入力するだけでトランク設定ファイルを生成します',
    '拠点間接続を使用する場合の情報を設定します',
    '接続先拠点の内線をローカルな内線として設定します',
    'BLFで内線状態を確認するためのヒントを生成します',
    '電話機の設定ファイルを生成します(機種限定)',
    'Slack連携機能の設定を行います',
    'QPMの管理を行います',
    'ABSの情報と設定ファイルのバックアップを行います',
    'バックアップファイルからの復元を行います' ];

  const FILE = [
    'file-edit',
    'addon/call-log',
    'addon/trunk-generator',
    'addon/intra-config',
    'addon/remote-exten-page',
    'hint-generator',
    'addon/prov-generator',
    'addon/slack-page',
    'addon/qpm-manage',
    'addon/backup-page',
    'addon/restore-page' ];  
}

?>
