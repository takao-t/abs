<?php

class ToolsMenu
{
  const NAME = [
    'ファイル編集',
    'トランク設定',
    '拠点間接続設定',
    'リモート内線',
    '内線ヒント生成',
    '電話機設定ファイル',
    'QPM管理',
    'バックアップ',
    'リストア',
    '内線一括管理' ];

  const DESC = [
    '設定ファイル類の編集を行います',
    '必要な項目を入力するだけでトランク設定ファイルを生成します',
    '拠点間接続を使用する場合の情報を設定します',
    '接続先拠点の内線をローカルな内線として設定します',
    'BLFで内線状態を確認するためのヒントを生成します',
    '電話機の設定ファイルを生成します(機種限定)',
    'QPMの管理を行います',
    'ABSの情報と設定ファイルのバックアップを行います',
    'バックアップファイルからの復元を行います',
    '内線の登録/バックアップを一括で行います' ];

  const FILE = [
    'file-edit',
    'addon/trunk-generator',
    'addon/intra-config',
    'addon/remote-exten-page',
    'hint-generator',
    'addon/prov-generator',
    'addon/qpm-manage',
    'addon/backup-page',
    'addon/restore-page',
    'addon/ext-inout' ];  
}

?>
