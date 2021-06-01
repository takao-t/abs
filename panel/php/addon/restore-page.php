<h2>リストア</h2>

<?php
$msg1 = '';
$msg2 = '';

    if($_SERVER['REQUEST_METHOD'] === 'POST'){

        $current =  date("Ymd-His");


        if($_POST['function'] == 'delete'){

            if(isset($_POST['filename'])){
                if(isset($_POST['confirm'])){
                    if($_POST['confirm'] == 'yes'){
                        $p_filename = $_POST['filename'];
                        $delpath = BACKUPDIR . '/' . $p_filename;
                        if(is_file($delpath)){
                            unlink($delpath);
                        }
                    }
                }
            }
        }

        if($_POST['function'] == 'restore'){

          if(isset($_POST['filename'])){
            if(isset($_POST['confirm'])){
              if($_POST['confirm'] == 'yes'){
                $p_filename = $_POST['filename'];
                //Asterisk 設定ファイル(tar.z)
                if(preg_match('/astconf-/', $p_filename)){ //Asterisk conf file
                  $respath = BACKUPDIR . '/' . $p_filename;
                  if(isset($_POST['dryrun'])){
                    if($_POST['dryrun'] == 'yes'){
                      $cmd_line = 'tar tvfz ' . BACKUPDIR . '/' . $p_filename ;
                    }
                  } else {
                      //Debug Use
                      //$cmd_line = 'tar xvfz ' . BACKUPDIR . '/' . $p_filename . ' --overwrite -C /etc/asterisk2';
                      $cmd_line = 'tar xvfz ' . BACKUPDIR . '/' . $p_filename . ' --overwrite -C '. ASTDIR;
                      $cmd_line2 = 'chmod g+rw'. ASTDIR;
                  }
                  $dummy = exec($cmd_line, $cmd_out);
                  $dummy = exec($cmd_line2);
                }
                //AstDB
                if(preg_match('/astdb-/', $p_filename)){ //AstDB
                  $respath = BACKUPDIR . '/' . $p_filename;
                  $content = file_get_contents($respath);
                  if(isset($_POST['dryrun'])){
                    if($_POST['dryrun'] == 'yes'){
                      $cmd_out = explode("\n", $content);
                    }
                  } else {
                      $db_content = explode("\n", $content);
                      $cmd_out = array();
                      $cmd_idx = 0; 
                      foreach($db_content as $db_ent){
                        list($db_mixed, $db_val) = explode(' ', $db_ent);
                        $db_tmp = explode('/', $db_mixed);
                        $c_tmp = count($db_tmp);
                        $db_family = '';
                        for($i=0;$i<($c_tmp - 1);$i++){
                          if($i == 0) $db_family .= $db_tmp[$i];
                          else $db_family .= '/' . $db_tmp[$i];
                        }
                        $db_key =  $db_tmp[$c_tmp -1];
                        AbspFunctions\put_db_item($db_family, $db_key, $db_val);
                        $cmd_out[$cmd_idx] = $db_family . ' ' . $db_key . ' ' . $db_val . "\n";
                        $cmd_idx++;
                      }
                    }
                }
              }
            }
          }
        }

    } //POST
echo <<<EOT
<h3>ファイルアップロード</h3>
ローカルに保存しているバックアップファイルから復元するには一旦アップロードしてください。<br>
同じ名前のファイルがあると上書きされます。<br>
<form method="post" action="php/upload.php" enctype="multipart/form-data">
  <table border=0 class="pure-table">
    <tr>
      <td>
        ファイル : <input type="file" name="upload_file">
      </td>
      <td>
        <input type="submit" class={$_(ABSPBUTTON)} value="upload">
      </td>
  </table>
</form>
EOT;


    $dir = opendir(BACKUPDIR);
    if($dir !== FALSE){
        while (false !== ($file_list[] = readdir($dir)));
    } else {
        $file_list = '';
    }
    closedir($dir);
    sort($file_list);

echo <<<EOT
<br>
<h3>バックアップファイル一覧</h3>
astconf- : Asteriskの設定情報(トランク情報等を復元する場合はこちら)<br>
astdb- : ABSの設定情報(内線設定情報などPBX機能を復元する場合はこちら)<br>
ドライ: ドライラン。実際の復元は行わず復元されるファイルやデータを表示します。<br>
<table border=0 class="pure-table">
  <tr>
    <thead>
      <th>ファイル</th>
      <th>削除</th>
      <th>ドライ</th>
      <th>確認</th>
      <th>実行</th>
    </thead>
  </tr>
EOT;

    $i = 0;

    foreach($file_list as $key=>$file){
       if($file == '.htaccess') continue;
       $target = BACKUPDIR . '/' . $file;
       if(is_file($target)) {

       if($i % 2 == 0){
           $tr_odd_class = '';
       } else {
           $tr_odd_class = 'class="pure-table-odd"';
       }
       $i++;

echo <<<EOT
  <tr $tr_odd_class>
    <td>
      $file
    </td>
    <td>
      <form action="" method="post">
      <input type="hidden" name="filename" value="$file">
      <input type="hidden" name="function" value="delete">
      <input type="checkbox" name="confirm" value="yes">
      <input type="submit" class={$_(ABSPBUTTON)} value="削除">
      </form>
    </td>
    <td>
      <form action="" method="post">
      <input type="hidden" name="filename" value="$file">
      <input type="hidden" name="function" value="restore">
      <input type="checkbox" name="dryrun" value="yes" checked>
    </td>
    <td>
      <input type="checkbox" name="confirm" value="yes">
    </td>
    <td>
      <input type="submit" class={$_(ABSPBUTTON)} value="このファイルから復元">
      </form>
    </td>
  </tr>
EOT;
       }
    }

echo <<<EOT
</table>
<font color="red">注意:安全のためリストアは現在の内容を上書きします。完全にリストアしたい場合にはディレクトリの内容を空にし、AstDBの内容を空にしてから行ってください。</font>
<br>
<hr>
実行結果は下に表示されます。
<hr>
EOT;

if(isset($cmd_out)){
    foreach($cmd_out as $line){
        echo $line;
        echo '<BR>';
    }
}
?>
