<h2>バックアップ</h2>

<?php
$msg1 = '';
$msg2 = '';

    if($_SERVER['REQUEST_METHOD'] === 'POST'){

        $current =  date("Ymd-His");

        if($_POST['function'] == 'absbackup'){

            $backup = BACKUPDIR . '/' . 'astdb-' . $current . '.db';

            $content = '';
            $abs_data = AbspFunctions\get_db_family('ABS');
            foreach($abs_data as $line){
                list($key, $val) = explode(' : ', $line);
                $key = trim($key);
                $val = trim($val);
                $content .=  'ABS/' . $key . ' ' . $val ."\n";
            }

            $abs_data = AbspFunctions\get_db_family('KEYTEL');
            foreach($abs_data as $line){
                list($key, $val) = explode(':', $line);
                $key = trim($key);
                $val = trim($val);
                $content .=  'KEYTEL/' . $key . ' ' . $val ."\n";
            }

            $abs_data = AbspFunctions\get_db_family('cidname');
            foreach($abs_data as $line){
                list($key, $val) = explode(':', $line);
                $key = trim($key);
                $val = trim($val);
                $content .=  'cidname/' . $key . ' ' . $val ."\n";
            }

            $abs_data = AbspFunctions\get_db_family('HOLIDAYS');
            foreach($abs_data as $line){
                list($key, $val) = explode(':', $line);
                $key = trim($key);
                $val = trim($val);
                $content .=  'HOLIDAYS/' . $key . ' ' . $val ."\n";
            }

            if(file_put_contents($backup, $content) !== false){
                $msg1 = 'バックアップ完了';
            } else {
                $msg1 = 'バックアップ失敗';
            }

        } //ABS db save

        if($_POST['function'] == 'confbackup'){

            $cmd_line = 'cd ' . ASTDIR . ';' . 'tar cfz ' . BACKUPDIR . '/astconf-' . $current . '.tar.z' . ' *';

            $cmd_out = exec($cmd_line, $out_lines, $retval);
            if($retval == 0){
                $msg2 = 'バックアップ完了';
            } else {
                $msg2 = 'バックアップ失敗';
            }

        }

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

    } //POST

echo <<<EOT
<h3>バックアップの作成</h3>
<table border=0 class="pure-table">
  <tr>
    <thead>
      <th></th>
      <th></th>
      <th></th>
    </thead>
  </tr>
  <tr>
    <td>
      ABS設定情報
    </td>
    <td>
      <form action="" method="post">
      <input type="hidden" name="function" value="absbackup">
      <input type="submit" class={$_(ABSPBUTTON)} value="バックアップ作成">
      </form>
    </td>
    <td>
      $msg1
    </td>
  </tr>
  <tr>
    <td>
      Asterisk設定ファイル
    </td>
    <td>
      <form action="" method="post">
      <input type="hidden" name="function" value="confbackup">
      <input type="submit" class={$_(ABSPBUTTON)} value="バックアップ作成">
      </form>
    </td>
    <td>
      $msg2
    </td>
  </tr>
</table>
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
<h3>バックアップファイル</h3>
<table border=0 class="pure-table">
  <tr>
    <thead>
      <th></th>
      <th></th>
      <th></th>
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

       $f_path = BACKUPDIR . '/' . $file;

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
     <form action="php/download.php" method="get">
     <input type="hidden" name="file" value="$file">
      <input type="submit" class={$_(ABSPBUTTON)} value="ダウンロード">
     </form>
    </td>
  </tr>
EOT;
       }
    }
?>
