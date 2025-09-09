<h2>内線ヒント生成</h2>
<h3>PJSIPのみ対応</h3>

<?php
if(!isset($msg)) $msg = '';


if($_SERVER['REQUEST_METHOD'] === 'POST'){

    if($_POST['function'] == 'savetofile'){
        if(isset($_POST['savechecked'])){
            if($_POST['savechecked'] == 'yes'){
                $p_filename = trim($_POST['filename']);
                if($p_filename != ''){
                    $p_filename = ASTDIR . '/' . $p_filename;
                    $content = $_POST['content'];
                    $content = str_replace("\r", '', $content);
                    file_put_contents($p_filename, $content);
                    $msg = '<font color="red">'. $p_filename . 'に保存しました' . '</font>';
                }
            }
        }
    } //savetofile

} // End POST

$content = "[ext-hints]\n";

$localtech = AbspFunctions\get_db_item('ABS', 'EXTTECH');

for($i=1;$i<=32;$i++){
    $exten = AbspFunctions\get_db_item('ABS/ERV', "phone$i");
    if($exten != ''){
        $content .= "exten => $exten,hint,$localtech/phone$i\n";
    }
}

echo <<<EOT
<h3>生成結果</h3>
$msg<br>
<textarea name="content" rows="34" cols="80">
$content
</textarea>
<br>
<form action="" method="post">
内容を確認しました
<input type="checkbox" name="savechecked" value="yes">
{$_(ASTDIR)}/extensions_exthints.conf として
<input type="submit" value="保存する">
<input type="hidden" name="function" value="savetofile">
<input type="hidden" name="content" value="$content">
<input type="hidden" name="filename" value="extensions_exthints.conf">
<form>
<br>
<font color="red" size="-1">注意:現在のファイルが上書きされます</font>
<br>
EOT;
?>
