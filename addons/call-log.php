<h2>着信管理</h2>

<?php

    if($_SERVER['REQUEST_METHOD'] === 'POST'){

        if($_POST['function'] == 'bladd'){
          $p_cid = trim($_POST['blcid']);
          $p_yesno = trim($_POST['blchecked']);
          if($p_yesno == 'YES'){
            AbspFunctions\put_db_item('ABS/blacklist', $p_cid, '1');
          } else {
            AbspFunctions\del_db_item('ABS/blacklist', $p_cid);
          } 
        }

        if($_POST['function'] == 'del_file'){
          $p_filedel = trim($_POST['filedel']);
          $p_filename = trim($_POST['file']);
          if($p_filedel == 'on'){
            if($p_filename != ""){
              $p_filename = LOGDIR . '/' . $p_filename;
              unlink($p_filename);
            }
          }
        }

    } //POST

//着信ログ
$incoming_file = 'call_incoming.log';
echo <<<EOT
<table border=0 class="pure-table">
  <tr>
    <thead>
      <th>ダウンロード</th>
      <th>削除</th>
    </thead>
  </tr>
  <tr>
    <td>
      <form action="php/addon/cl-download.php" method="get">
      <input type="hidden" name="file" value="$incoming_file">
      <input type="submit" class={$_(ABSPBUTTON)} value="着信履歴ダウンロード">
      </form>
    </td>
    <td>
      <form action="" method="post">
      <input type="hidden" name="file" value="$incoming_file">
      <input type="hidden" name="function" value="del_file">
      <input type="submit" class={$_(ABSPBUTTON)} value="着信履歴削除">
      <input type="checkbox" name="filedel">
      </form>
    </td>
  </tr>
</table>

<h3>着信履歴</h3>
<table border=0 class="pure-table">
  <tr>
    <thead>
      <th>日時</th>
      <th>番号</th>
      <th>名称</th>
      <th>着拒登録</th>
      <th>発番登録</th>
    </thead>
  </tr>
EOT;

  $i = 0;
  $f_log = fopen('/var/log/asterisk/call_incoming.log', 'r');

  if($f_log !== FALSE){
    while(!feof($f_log)){

      $line = fgets($f_log);
      if($line == '') continue;
      list($dtime, $tmp1) = explode(' : ', $line, 2);
      list($dummy, $cidnum) = explode(' - ', $tmp1, 2);

      $cidname = AbspFunctions\get_db_item('cidname', $cidnum);

      $ret = AbspFunctions\get_db_item('ABS/blacklist', $cidnum);
      if($ret == 1) $ischecked = 'checked';
      else $ischecked = '';

      if($i % 2 == 0){
        $tr_odd_class = '';
      } else {
        $tr_odd_class = 'class="pure-table-odd"';
      }
      $i++;

echo <<<EOT
  <tr $tr_odd_class>
    <td>
     $dtime
    </td>
    <td>
     $cidnum
    </td>
    <td>
     $cidname
    </td>
    <td>
     <form action="" method="post">
       <input type="hidden" name="function" value="bladd">
       <input type="checkbox" name="blchecked" value="YES" $ischecked>
       <input type="hidden" name="blcid" value="$cidnum">
       <input type="submit" class={$_(ABSPBUTTON)} value="登録"> 
     </form>
    </td>
    <td>
     <form action="index.php?page=cid-config-page.php" method="post">
       <input type="hidden" name="post_pnum" value="$cidnum">
       <input type="submit" class={$_(ABSPBUTTON)} value="登録"> 
     </form>
    </td>
  </tr>
EOT;

    }
  }

echo <<<EOT
</table>
<br>
<hr>
EOT;

//拒否ログ
$reject_file = 'call_reject.log';
echo <<<EOT
<table border=0 class="pure-table">
  <tr>
    <thead>
      <th>ダウンロード</th>
      <th>削除</th>
    </thead>
  </tr>
  <tr>
    <td>
      <form action="php/addon/cl-download.php" method="get">
      <input type="hidden" name="file" value="$reject_file">
      <input type="submit" class={$_(ABSPBUTTON)} value="拒否履歴ダウンロード">
      </form>
    </td>
    <td>
      <form action="" method="post">
      <input type="hidden" name="function" value="del_file">
      <input type="hidden" name="file" value="$reject_file">
      <input type="submit" class={$_(ABSPBUTTON)} value="拒否履歴削除">
      <input type="checkbox" name="filedel">
      </form>
    </td>
  </tr>
</table>

<h3>着信拒否履歴</h3>
<table border=0 class="pure-table">
  <tr>
    <thead>
      <th>日時</th>
      <th>番号</th>
      <th>着拒登録</th>
    </thead>
  </tr>
EOT;

  $i = 0;
  $f_log = fopen('/var/log/asterisk/call_reject.log', 'r');

  if($f_log !== FALSE){
    while(!feof($f_log)){

      $line = fgets($f_log);
      if($line == '') continue;
      list($dtime, $tmp1) = explode(' : ', $line, 2);
      list($dummy, $cidnum) = explode(' - ', $tmp1, 2);

      $ret = AbspFunctions\get_db_item('ABS/blacklist', $cidnum);
      if($ret == 1) $ischecked = 'checked';
      else $ischecked = '';

      if($i % 2 == 0){
        $tr_odd_class = '';
      } else {
        $tr_odd_class = 'class="pure-table-odd"';
      }
      $i++;

echo <<<EOT
  <tr $tr_odd_class>
    <td>
     $dtime
    </td>
    <td>
     $cidnum
    </td>
    <td>
     <form action="" method="post">
       <input type="hidden" name="function" value="bladd">
       <input type="checkbox" name="blchecked" value="YES" $ischecked>
       <input type="hidden" name="blcid" value="$cidnum">
       <input type="submit" class={$_(ABSPBUTTON)} value="登録"> 
     </form>
    </td>
  </tr>
EOT;

    }
  }

echo <<<EOT
</table>
EOT;

?>
