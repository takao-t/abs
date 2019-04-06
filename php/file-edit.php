<h3>ファイル編集</h3>

<?php
$msg ='';
$msgn ='';
$ofile ='';
$file = '';

if($use_file_editor != 'YES') exit;

$content = '';

if(isset($_POST['readfile'])){
    $file = $_POST['readfile'];
    $ofile = $file;
    if ($_POST['open'] && $file) {
       $target = ASTDIR . '/' . $file;
       $content = file_get_contents($target);
    }
    if(isset($_POST['do_backup'])){
        if($_POST['do_backup'] == 'yes'){
            $backup_file = ASTDIR . '/' . $file . '.bak';
            if(file_put_contents($backup_file, $content) !== false){
                $msg='バックアップ作成済 ';
            } else {
                $msg='バックアップを作成できませんでした ';
            }
        }
    }
}


if(isset($_POST['do_save'])){
    if($_POST['do_save'] == 'yes'){
        $savefile = $_POST['savefile'];
        $savefile = ASTDIR . '/' .  $savefile;
        $content = $_POST['contents'];
        $content = str_replace("\r", '', $content);
        if(file_put_contents($savefile, $content) !== false){
            $msg='保存完了 ';
            $content = '';
        } else {
            $msg='保存できませんでした ';
        }
    }
}

if(isset($_POST['create_file'])){
    if($_POST['newfile'] != ''){
        $file = ASTDIR . '/' . $_POST['newfile'];
        if(fopen($file, 'r') === false){
            $content = '';
            if(file_put_contents($file, $content) !== false){
                $msgn='新規作成完了';
            } else {
                $msgn='作成できませんでした ';
            }
        } else {
            fclose($file);
            $msgn='そのファイルはすでに存在します';
        }
    }
}

echo <<<EOT
<table border="0" class="pure-table">
<tr>
<td align="right">
<b>
$ofile
</b>
<form action="" method="post">
<textarea name="contents" cols="80" rows="34">
$content
</textarea>
<br>
$msg
<input type="hidden" name="do_save" value="yes">
<input type="hidden" name="savefile" value="$file">
<input class={$_(ABSPBUTTON)} type="submit" name="save" value="保存">
</form>
</td>
EOT;
?>

<td valign="top" align="right">
<form action="" method="post">
<select name="readfile" size="30">
<?php
$dir = opendir(ASTDIR);
while (false !== ($file_list[] = readdir($dir)));
closedir($dir);
sort($file_list);
foreach($file_list as $key=>$file){
  $target = ASTDIR . '/' . $file;
  if(is_file($target)) {
    print "<option>$file</option>\n";
  }
}

echo <<<EOT
</select>
<input class={$_(ABSPBUTTON)} type="submit" name="open" value="開く">
<input type="checkbox" name="do_backup" value="yes">バックアップ作成
</form>
</td>
</tr>
</table>

<br>

<table border=0 class"pure-table">
<tr>
<td>
ファイル新規作成：
</td>
<td>
</td>
</tr>
<tr>
<td align="right">
ファイル名
</td>
<td>
<form action="" method="post">
<input type="hidden" name="create_file" value="yes">
<input type="text" size="16" name="newfile">
</td>
<td>
<input type="submit" class={$_(ABSPBUTTON)} name="nfile" value="新規作成">
</form>
$msgn
</td>
</tr>
</table>

EOT;
?>
