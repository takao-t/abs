<html>
<body>
<h3>拠点間接続設定</h3>
複数台のABSを相互接続して拠点間内線接続を行います。ただし使用できるのは固定IPアドレスで対向する相手のみです。<br>
拠点間内線は *0 + 拠点番号 + 相手内線番号でダイヤルします。<br>
<br>
設定完了後は以下の例のようにpjsip.confの再下業に_intra_をもつトランク情報をincludeしてください。intra_meは自局の情報です。<br>
;拠点間接続用<br>
#include "pjsip_trunk_intra_me.conf"<br>
#include "pjsip_trunk_intra_itabashi.conf"<br>
<?php
?>
</body>
</html>
