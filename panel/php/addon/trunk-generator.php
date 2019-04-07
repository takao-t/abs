<h2>トランク設定ファイル生成</h2>
<h3>PJSIPのみ対応</h3>

<?php
if(!isset($msg)) $msg = '';


if($_SERVER['REQUEST_METHOD'] === 'POST'){

     //共通処理
    if(isset($_POST['template'])) $template = trim($_POST['template']);
     else $template = '';
    if(isset($_POST['num'])) $num = trim($_POST['num']);
     else $num = '';
    if(isset($_POST['ipaddr'])) $ipaddr = trim($_POST['ipaddr']);
     else $ipaddr = '';
    if(isset($_POST['username'])) $username = trim($_POST['username']);
     else $username = '';
    if(isset($_POST['password'])) $password = trim($_POST['password']);
     else $password = '';
    if(isset($_POST['proxy'])) $proxy = trim($_POST['proxy']);
     else $proxy = '';
    if(isset($_POST['exten'])) $exten = trim($_POST['exten']);
     else $exten = '';
    //トランク名定義
    $trunkdef = array('hgw'=>'hikari-hgw', 'ogw'=>'hikari-ogw', 'smart'=>'fsmart',
        'opengate'=>'opengate', 'basix'=>'basix');
    $trunkname = $trunkdef[$template] . $num;

    if($_POST['function'] == 'generate'){

        $filename = './php/addon/templates/pjsip_trunk_' . $template . '.tmpl';
        $content = file_get_contents($filename);

        $content = str_replace('##NUM##', $num, $content);
        $content = str_replace('##IPADDR##', $ipaddr, $content);
        $content = str_replace('##USERNAME##', $username, $content);
        $content = str_replace('##PASSWORD##', $password, $content);
        $content = str_replace('##PROXY##', $proxy, $content);
        $content = str_replace('##EXTEN##', $exten, $content);
        $content = str_replace('##TRUNKNAME##', $trunkname, $content);

        //ACL情報
        if($template == 'hgw'){
            $acl = 'permit=' . $ipaddr . '/32';
        }
        if($template == 'ogw'){
            $acl = 'permit=' . $ipaddr . '/32';
        }
        if($template == 'basix'){
            $filename = './templates/' . $template . '.acl';
            $acl = file_get_contents($filename);
        }
        if($template == 'smart'){
            $filename = './templates/' . $template . '.acl';
            $acl = file_get_contents($filename);
        }
        if($template == 'opengate'){
            $filename = './templates/' . $template . '.acl';
            $acl = file_get_contents($filename);
        }
    } //generate

    if($_POST['function'] == 'savetofile'){
        if(isset($_POST['savechecked'])){
            if($_POST['savechecked'] == 'yes'){
                $p_filename = trim($_POST['filename']);
                if($p_filename != ''){
                    $p_filename = ASTDIR . '/' . $p_filename;
                    $content = $_POST['conf_content'];
                    $content = str_replace("\r", '', $content);
                    file_put_contents($p_filename, $content);
                    $msg = '<font color="red">'. $p_filename . 'に保存しました' . '</font>';
                }
            }
        }
    } //savetofile

} // End POST

if(!isset($template)) $template = '';
if(!isset($content)) $content = '';
if(!isset($num)) $num = '';
if(!isset($ipaddr)) $ipaddr = '';
if(!isset($username)) $username = '';
if(!isset($password)) $password = '';
if(!isset($proxy)) $proxy = '';
if(!isset($exten)) $exten = '';
if(!isset($acl)) $acl = '';
if(!isset($trunkname)) $trunkname = '';

    $templ_selected = array('hgw'=>'','ogw'=>'','smart'=>'','opengate'=>'','basix'=>'');
    $templ_selected[$template] = "selected";
    $target_filename = 'pjsip_trunk_' . $template . $num . '.conf';

echo <<<EOT
$msg
<table border=0 class="pure-table">
  <form action="" method="post">
  <input type="hidden" name="function" value="generate">
  <tr>
    <thead>
      <th>項目名</th>
      <th>設定値</th>
      <th>説明</th>
    </thead>
  </tr>
  <tr>
    <td>種別</td>
    <td>
      <select name="template">
      <option value="hgw" {$templ_selected['hgw']}>ひかり電話(ホームゲートウェイ)</option>
      <option value="ogw" {$templ_selected['ogw']}>ひかり電話(オフィスゲートウェイ)</option>
      <option value="smart" {$templ_selected['smart']}>FUSION(SMART)</option>
      <option value="opengate" {$templ_selected['opengate']}>FUSION(Open Gate)</option>
      <option value="basix" {$templ_selected['basix']}>Brastel(BASIX)</option>
    </td>
    <td>
      使用するテンプレートを選択します
    </td>
  </tr>
  <tr class="pure-table-odd">
    <td>トランク番号</td>
    <td>
      <input type="text" name="num" size="2" value="$num">
    </td>
    <td>
      同一種類のトランクを複数使用する場合に数字を入力します
    </td>
  </tr>
  <tr>
    <td>ドメイン<br>またはIPアドレス</td>
    <td>
      <input type="text" name="ipaddr" size="20" value="$ipaddr">
    </td>
    <td>
      対象のIPアドレスまたはドメイン名を指定します
    </td>
  </tr>
  <tr class="pure-table-odd">
    <td>プロキシ</td>
    <td>
      <input type="text" name="proxy" size="20" value="$proxy">
    </td>
    <td>
      指定がある場合にはプロキシを指定します
    </td>
  </tr>
  <tr>
    <td>ユーザ名</td>
    <td>
      <input type="text" name="username" size="20" value="$username">
    </td>
    <td>
      ユーザ名を指定します
    </td>
  </tr>
  <tr class="pure-table-odd">
    <td>パスワード</td>
    <td>
      <input type="text" name="password" size="20" value="$password">
    </td>
    <td>
      パスワードを指定します
    </td>
  </tr>
  <tr>
    <td>番号</td>
    <td>
      <input type="text" name="exten" size="20" value="$exten">
    </td>
    <td>
      指定のある場合契約番号を指定します
    </td>
  </tr>
  <tr class="pure-table-odd">
    <td>
      <input type="submit" value="実行">
    </td>
    <td>
    </td>
    <td>
      項目を入力して実行を押してください
    </td>
  </tr>
  </form>
</table>
<br>
トランク名 : <input type="text" name="trunkname" size="20" value="$trunkname" readonly><br>
ABS上で使用するトランク名は上記の名称を使用します。<br>
<br>
<h3>生成結果</h3>
<textarea name="content" rows="34" cols="80">
$content
</textarea>
<br>
(1) 上記の内容を $target_filename などの名前で保存します。<br>
<form action="" method="post">
内容を確認しました
<input type="checkbox" name="savechecked" value="yes">
{$_(ASTDIR)}/{$target_filename} として
<input type="submit" value="保存する">
<input type="hidden" name="function" value="savetofile">
<input type="hidden" name="filename" value="$target_filename">
<input type="hidden" name="conf_content" value="$content">
<input type="hidden" name="template" value="$template">
<input type="hidden" name="num" value="$num">
<input type="hidden" name="ipaddr" value="$ipaddr">
<input type="hidden" name="proxy" value="$proxy">
<input type="hidden" name="username" value="$username">
<input type="hidden" name="password" value="$password">
<input type="hidden" name="exten" value="$exten">
<form>
<br>
<font color="red" size="-1">注意:同じ名前のファイルが存在すると上書きされます</font>
<br>
(2) pjsip.confの最下行に以下を追加します。<br>
<input type="text" size="40" value="#include pjsip_trunk_{$template}{$num}.conf" readonly><br>
(3) pjsip.confの[acl]セクションに以下を追加します。<br>
<textarea readonly cols="40">
$acl
</textarea>
<br>
このアドレスは参考値です。運用上支障が出た場合は変更してください。<br>
EOT;
?>
