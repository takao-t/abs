<h2 id="sysconfig">システム設定</h2>

<?php
$msg = "";
$p_msg = array();
$n_msg = "";
$lr_msg = "";

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    if($_POST['function'] == 'extconf'){ //内線テクノロジ
        $p_tech = $_POST['exttech'];
        if($p_tech == 'SIP'){
            AbspFunctions\put_db_item('ABS', 'EXTTECH', 'SIP');
        } else {
            AbspFunctions\put_db_item('ABS', 'EXTTECH', 'PJSIP');
        }
    }

    if($_POST['function'] == 'updatepass'){ //パスワード変更
        if(isset($_POST['username'])) $n_username = $_POST['username'];
        else $n_username = '';
        if(isset($_POST['opasswd'])) $n_opasswd = $_POST['opasswd'];
        else $n_opasswd = '';
        if(isset($_POST['npasswd1'])) $n_npasswd1 = $_POST['npasswd1'];
        else $n_npasswd1 = '';
        if(isset($_POST['npasswd2'])) $n_npasswd2 = $_POST['npasswd2'];
        else $n_npasswd2 = '';
        if(isset($_POST['nent'])) $n_nent = $_POST['nent'];
        else $n_nent = '';

        $p_msg = array();
        if($n_username != ''){
            if($n_npasswd1 != ''){
                if($n_npasswd1 == $n_npasswd2){
                    $unmkey = $n_username . ':abspanel:' . $n_npasswd1;
                    $unmkey = trim(md5($unmkey));
                    $oldkey = $n_username . ':abspanel:' . $n_opasswd;
                    $oldkey = trim(md5($oldkey));
                    $unent = "{\"name\":\"$n_username\",\"key\":\"$unmkey\",\"class\":\"2\"}\n";
                    $ufile = $uinfolocation . '/' . 'userinfo.dat';
                    $ulist = file_get_contents($ufile);
                    $ucontent = explode("\n", $ulist);
                    $ncontent = '';
                    $gonogo = '';
                    foreach($ucontent as $line){
                        if(strpos($line, $n_username) !== false){
                            if(strpos($line, $oldkey) !== false){
                                $newline = str_replace($oldkey, $unmkey, $line);
                                $ncontent .= $newline . "\n";
                                $gonogo = 'go';
                                $p_msg[$n_nent] = '変更完了';
                            } else {
                                $ncontent .= $line . "\n";
                                $p_msg[$n_nent] = 'パスワード不一致';
                            }
                        } else {
                            $ncontent .= $line . "\n";
                        }
                     } //end scan
                     if($gonogo == 'go'){
                         @file_put_contents($ufile, $ncontent, LOCK_EX);
                     }
                } else {
                    $p_msg[$n_nent] = 'パスワード不一致';
                }
            } else {
                $p_msg[$n_nent] = 'パスワード未指定';
            }
        } else {
            $p_msg[$n_nent] = 'ユーザ名未指定';
        }
    }

    if($_POST['function'] == 'useradd'){ //ユーザ追加
        if(isset($_POST['username'])) $n_username = $_POST['username'];
        else $n_username = '';
        if(isset($_POST['npasswd1'])) $n_npasswd1 = $_POST['npasswd1'];
        else $n_npasswd1 = '';
        if(isset($_POST['npasswd2'])) $n_npasswd2 = $_POST['npasswd2'];
        else $n_npasswd2 = '';

        if($n_username != ''){
            if($n_npasswd1 != ''){
                if($n_npasswd1 == $n_npasswd2){
                    $unmkey = $n_username . ':abspanel:' . $n_npasswd1;
                    $unmkey = trim(md5($unmkey));
                    $unent = "{\"name\":\"$n_username\",\"key\":\"$unmkey\",\"class\":\"2\"}\n";
                    $ufile = $uinfolocation . '/' . 'userinfo.dat';
                    file_put_contents($ufile, $unent, FILE_APPEND | LOCK_EX);
                } else {
                    $n_msg = 'パスワード不一致';
                }
            } else {
                $n_msg = 'パスワード未指定';
            }
        } else {
            $n_msg = 'ユーザ名未指定';
        }

    }

    if($_POST['function'] == 'areaconf'){ //エリア管理
        $p_a_ntte = $_POST['a_ntte'];
        $p_a_nttw = $_POST['a_nttw'];
        $p_a_basix = $_POST['a_basix'];
        $p_a_user = $_POST['a_user'];

        if($p_a_ntte == '') $p_a_ntte = 'ntt-east.ne.jp';
        if($p_a_nttw == '') $p_a_nttw = 'ntt-west.ne.jp';
        if($p_a_basix == '') $p_a_basix = 'asterisk.basix.ne.jp';

        AbspFunctions\put_db_item('ABS/NTTE', 'AREA', $p_a_ntte);
        AbspFunctions\put_db_item('ABS/NTTW', 'AREA', $p_a_nttw);
        AbspFunctions\put_db_item('ABS/BASIX', 'AREA', $p_a_basix);
        AbspFunctions\put_db_item('ABS/UAREA', 'AREA', $p_a_user);
    
    }

    if($_POST['function'] == 'keysysinit'){ //キーシステム初期化
        AbspFunctions\exec_cli_command('channel originate Local/s@keysinit application NoCDR');
        $msg = '初期化完了';
    }

    if($_POST['function'] == 'bllog'){
    }

    if($_POST['function'] == 'anonupdate'){
    }

    if($_POST['function'] == 'localring'){
        $p_lr_ext = $_POST['extnum'];
        $c_lr_ext = AbspFunctions\get_db_item('ABS/ERV', 'localring');
        AbspFunctions\put_db_item('ABS/LOCALTECH', 'localring', 'Local');
        if($p_lr_ext == ''){
            if($c_lr_ext != ''){
                AbspFunctions\del_db_item('ABS/EXT', $c_lr_ext);
                AbspFunctions\del_db_item('ABS/ERV', 'localring');
                $lr_msg = '削除';
            } 
        } else {
            $ck_peer = AbspFunctions\get_db_item('ABS/EXT', $p_lr_ext);
            if($ck_peer != ''){
                $lr_msg = '内線重複';
            } else {
                AbspFunctions\del_db_item('ABS/EXT', $c_lr_ext);
                AbspFunctions\put_db_item('ABS/EXT', $p_lr_ext, 'localring');
                AbspFunctions\put_db_item('ABS/ERV', 'localring', $p_lr_ext);
                $lr_msg = '設定完了';
            }
        }
    }

    if($_POST['function'] == 'licset'){ //ライセンスキー設定
        if(isset($_POST['lickey'])){
            $p_lickey = $_POST['lickey'];
            AbspFunctions\put_db_item('ABS', 'LIC', $p_lickey);
        }
    }

} // end of POST

//情報確認
echo <<<EOT
<h3>各種情報確認</h3>
<a href="index.php?page=view-hostinfo-page.php" class ="pure-button pure-button-active">
  ホスト情報確認
</a>
<a href="index.php?page=view-exten-page.php" class ="pure-button pure-button-active">
  内線情報確認
</a>
<a href="index.php?page=view-peer-page.php" class ="pure-button pure-button-active">
  端末情報確認
</a>
<hr>
EOT;

//ユーザ管理

    $userinfo = $uinfolocation . '/' . 'userinfo.dat';
    $user_temp = file_get_contents($userinfo);
    $user_list = explode("\n", $user_temp);

echo <<<EOT
<h3>ユーザ管理</h3>
<table border=0 class="pure-table">
  <tr>
    <thead>
      <th>ユーザ名</th>
      <th>現在のパスワード</th>
      <th>新しいパスワード</th>
      <th>新しいパスワード(確認)</th>
      <th>操作</th>
      <th></th>
    </thead>
  </tr>
EOT;

    $i=0;
    foreach($user_list as $user_ent){
      $udata = json_decode($user_ent);
      if($udata != ''){
        $username = $udata->name;

      if($i % 2 == 0){
        $tr_odd_class = '';
      } else {
        $tr_odd_class = 'class="pure-table-odd"';
      }

echo <<<EOT
  <tr $tr_odd_class>
    <form action="" method="post">
    <input type="hidden" name="function" value="updatepass">
    <input type="hidden" name="nent" value="$i">
    <td>
      $username
      <input type="hidden" name="username" value="$username">
    </td>
    <td>
      <input type="password" size="8" name="opasswd">
    </td>
    <td>
      <input type="password" size="8" name="npasswd1">
    </td>
    <td>
      <input type="password" size="8" name="npasswd2">
    </td>
    <td>
      <input type="submit" class={$_(ABSPBUTTON)} value="更新">
    </td>
    </form>
    <td>
EOT;
if(isset($p_msg[$i])) echo "{$p_msg[$i]}";
echo <<<EOT
    </td>
  </tr>
EOT;
      $i++;

       }
    }//end foreach

echo <<<EOT
</table>
<h4>ユーザ追加</h4>
<table border=0 class="pure-table">
  <tr>
    <thead>
      <th>ユーザ名</th>
      <th>パスワード</th>
      <th>パスワード(確認)</th>
      <th>操作</th>
      <th></th>
    </thead>
  </tr>
  <tr>
    <form action="" method="post">
    <input type="hidden" name="function" value="useradd">
    <td>
      <input type="text" size="10" name="username">
    </td>
    <td>
      <input type="password" size="8" name="npasswd1">
    </td>
    <td>
      <input type="password" size="8" name="npasswd2">
    </td>
    <td>
      <input type="submit" class={$_(ABSPBUTTON)} value="追加">
    </td>
    </form>
    <td>
      $n_msg
    </td>
  </tr>
</table>
<hr>
EOT;

//内線テクノロジ

    $tech_selected = array('SIP'=>'', 'PJSIP'=>'');
    $tech = AbspFunctions\get_db_item('ABS', 'EXTTECH');
    $tech_selected["$tech"] = "selected";


echo <<<EOT
<h3 id="exttech">内線テクノロジ</h3>
<form action="" method="POST">
  <input type="hidden" name="function" value="extconf">
    <select name="exttech">
      <option value="SIP"  {$tech_selected['SIP']}>SIP</option>
      <option value="PJSIP"  {$tech_selected['PJSIP']}>PJSIP</option>
    </select>
  <input type="submit" class={$_(ABSPBUTTON)} value="設定">
</form>
<br>
EOT;

    $lr_ext = AbspFunctions\get_db_item('ABS/ERV', 'localring');

echo <<<EOT
</table>
<h4>特殊内線(鳴動内線)設定</h4>
<table border=0 class="pure-table">
  <tr>
    <thead>
      <th>内線名</th>
      <th>内線番号</th>
      <th>操作</th>
      <th></th>
    </thead>
  </tr>
  <tr>
    <form action="" method="post">
    <input type="hidden" name="function" value="localring">
    <td>
      localring
    </td>
    <td>
      <input type="text" size="8" name="extnum" value=$lr_ext>
    </td>
    <td>
      <input type="submit" class={$_(ABSPBUTTON)} value="設定">
    </td>
    </form>
    <td>
      $lr_msg
    </td>
  </tr>
</table>
<hr>
EOT;

//エリア管理

    $a_ntte = AbspFunctions\get_db_item('ABS/NTTE', 'AREA');
    if($a_ntte == '') $a_ntte = 'ntt-east.ne.jp';
    $a_nttw = AbspFunctions\get_db_item('ABS/NTTW', 'AREA');
    if($a_nttw == '') $a_nttw = 'ntt-west.ne.jp';
    $a_basix = AbspFunctions\get_db_item('ABS/BASIX', 'AREA');
    if($a_basix == '') $a_basix = 'asterisk.basix.ne.jp';
    $a_user = AbspFunctions\get_db_item('ABS/UAREA', 'AREA');

echo <<<EOT
<h3 id="exttech">エリア管理</h3>
※この情報は発信者番号通知のPPIヘッダで使用されます。<br>
<table class="pure-table">
  <form action="" method="POST">
    <input type="hidden" name="function" value="areaconf">
  <tr class="pure-table-odd">
    <td>
      <input type="txt" size="10" name="ntte" value="NTT東" readonly>
    </td>
    <td>
      <input type="txt" size="16" name="a_ntte" value="$a_ntte"><br>
    </td>
  </tr>
  <tr>
    <td>
      <input type="txt" size="10" name="nttw" value="NTT西" readonly>
    </td>
    <td>
      <input type="txt" size="16" name="a_nttw" value="$a_nttw"><br>
    </td>
  </tr>
  <tr class="pure-table-odd">
    <td>
      <input type="txt" size="10" name="basix" value="BASIX" readonly>
    </td>
    <td>
      <input type="txt" size="16" name="a_basix" value="$a_basix"><br>
    </td>
  </tr>
  <tr>
    <td>
      <input type="txt" size="10" name="user" value="ユーザ定義" readonly>
    </td>
    <td>
      <input type="txt" size="16" name="a_user" value="$a_user"><br>
    </td>
  </tr>
  <tr class="pure-table-odd">
    <td>
    </td>
    <td align="right">
      <input type="submit" class={$_(ABSPBUTTON)} value="設定">
    </td>
  </tr>
</form>
</table>
<br>
EOT;

echo <<<EOT
<h3 id="exttech">キーシステム初期化</h3>
<form action="" method="POST">
<table border="0" class="pure-table">
  <tr>
    <td>
      <input type="hidden" name="function" value="keysysinit">
      <font color="red"><b>キーシステムの状態を初期化します<br>通話中には実行しないでください。</b></font>
    </td>
    <td>
      <input type="submit" class={$_(ABSPBUTTON)} value="初期化実行">
    </td>
    <td>
      $msg
    </td>
  </tr>
</table>
</form>
<br>
EOT;

    $lickey = AbspFunctions\get_db_item('ABS', 'LIC');

echo <<<EOT
<h3 id="exttech">ライセンスキー</h3>
<form action="" method="POST">
<input type="hidden" name="function" value="licset">
<input type="txt" size="40" name="lickey" value="$lickey">
<input type="submit" class={$_(ABSPBUTTON)} value="設定">
</form>
EOT;

?>
