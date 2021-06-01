<html>
<body>
<h3>内線情報設定</h3>
このページでは各端末に割り当てる内線番号を設定します。<br>
設定は各内線ごとの右側の[設定]ボタンをクリックしてください。ページ全体を一気に設定する機能はありませんので、各内線毎の設定を行ってください。
<h4>ピア名</h4>
チャネルで定義されるピア(エンドポイント)名です。詳細は[システム設定]->[端末情報]で確認できます。
<h4>内線番号</h4>
このピアに設定する内線番号を入力します。内線番号は2桁以上で設定してください。
<h4>規制値</h4>
規制値はピアに紐付けられます。内線番号を変更しても規制値は変更されません。<br>
0:着信のみ発信不可<br>
1:内線発信のみ可<br>
2:外線発信可<br>
3:管理機能(番号による留守設定等)使用可<br>
<h4>発信CID</h4>
発信時に指定するCIDを指定します。ただし、先(トランク等の経路)で指定されているCIDがある場合には、そちらが優先されます。
<h4>PickUp</h4>
この端末がデフォルトで対象とするピックアップグループを設定します。グループ番号は1～8の1桁で指定してください。グループに所属する内線番号は[内線グループ設定]で行います。
<h4>MACアドレス</h4>
端末のMACアドレスを入力しますが、この情報は端末設定ファイル生成に使用されるだけですので端末設定ファイルをABSで生成しない場合には入力の必要はありません。
<h4>操作</h4>
必要な項目を入力した後、[設定]ボタンをクリックしてください。
<?php
?>
</body>
</html>
