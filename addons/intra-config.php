<h2>拠点間接続設定</h2>
<h3>PJSIPで固定IP相互接続拠点のみ対応</h3>

<?php
if(!isset($msg)) $msg = '';
$svfilename = '';


if($_SERVER['REQUEST_METHOD'] === 'POST'){


    //拠点番号桁数設定
    if($_POST['function'] == 'iopsetdigits'){
        if(isset($_POST['iop_digits'])){
            $iop_digits = trim($_POST['iop_digits']);
            AbspFunctions\put_db_item('ABS/IOP', 'DIGITS', $iop_digits);
        }
    }

    //自局情報設定
    if($_POST['function'] == 'setuphereinfo'){
        if(isset($_POST['iop_here'])){
            $p_iop_here = trim($_POST['iop_here']);
            AbspFunctions\put_db_item('ABS/IOP', 'HERE', $p_iop_here);
            if(isset($_POST['iop_here_name'])){
                $p_iop_here_name = trim($_POST['iop_here_name']);
                AbspFunctions\put_db_item("ABS/IOP/$p_iop_here", 'NAME' , $p_iop_here_name);
                if(isset($_POST['iop_here_node'])){
                    $p_iop_here_node = $_POST['iop_here_node'];
                    if(isset($_POST['iop_here_user'])){
                        $p_iop_here_user = trim($_POST['iop_here_user']);
                        if(isset($_POST['iop_here_pass'])){
                            $p_iop_here_pass = trim($_POST['iop_here_pass']);
                            $filename = ASTDIR . '/' . 'pjsip_trunk_intra_me.conf';
                            $fcontent = "[$p_iop_here_node]\n";
                            $fcontent .=  "type = auth\n";
                            $fcontent .=  "auth_type = userpass\n";
                            $fcontent .=  "username = $p_iop_here_user\n";
                            $fcontent .=  "password = $p_iop_here_pass\n";
                            file_put_contents($filename, $fcontent);
                        }
                    }
                }
            }
        }
    }

    //対向情報削除
    if($_POST['function'] == 'iopentdel'){
        if(isset($_POST['delent'])){
            $p_delent = $_POST['delent'];
            AbspFunctions\del_db_item("ABS/IOP/$p_delent", 'NAME');
            AbspFunctions\del_db_item("ABS/IOP/$p_delent", 'TECH');
            AbspFunctions\del_db_item("ABS/IOP/$p_delent", 'TRUNK');
            AbspFunctions\del_db_item("ABS/IOP", $p_delent);
        }
    }

    //対向情報追加
    if($_POST['function'] == 'ioppadd'){
        if(isset($_POST['iopp_num'])){
            $p_iopp_num = trim($_POST['iopp_num']);
            if(isset($_POST['iopp_name'])) $p_iopp_name = trim($_POST['iopp_name']);
              else $p_iopp_name = '';
            if(isset($_POST['iopp_ident'])) $p_iopp_ident = trim($_POST['iopp_ident']);
              else $p_iopp_ident = '';
            if(isset($_POST['iopp_addr'])) $p_iopp_addr = trim($_POST['iopp_addr']);
              else $p_iopp_addr = '';
            if(isset($_POST['iopp_user'])) $p_iopp_user = trim($_POST['iopp_user']);
              else $p_iopp_user = '';
            if(isset($_POST['iopp_pass'])) $p_iopp_pass = trim($_POST['iopp_pass']);
              else $p_iopp_pass = '';
            if(isset($_POST['iopp_here_node'])) $p_iopp_here_node = trim($_POST['iopp_here_node']);
              else $p_iopp_here_node = '';

            if(strpos($p_iopp_addr, ':') !== false){
                list($p_iopp_addr, $p_iopp_port) = explode(':', $p_iopp_addr);
                $p_iopp_port = ':' . $p_iopp_port;
            } else {
                $p_iopp_port = '';
            }

            $filename = './php/addon/templates/pjsip_trunk_intra' . '.tmpl';
            $content = file_get_contents($filename);

            $content = str_replace('##TRUNKNAME##', $p_iopp_ident, $content);
            $content = str_replace('##IPADDR##', $p_iopp_addr, $content);
            $content = str_replace('##PORT##', $p_iopp_port, $content);
            $content = str_replace('##USERNAME##', $p_iopp_user, $content);
            $content = str_replace('##PASSWORD##', $p_iopp_pass, $content);
            $content = str_replace('##HEREAUTH##', $p_iopp_here_node, $content);

            $svfilename = ASTDIR . '/' . 'pjsip_trunk_intra_' . $p_iopp_ident . '.conf';
            @file_put_contents($svfilename, $content);
            AbspFunctions\put_db_item("ABS/IOP/$p_iopp_num", 'NAME', $p_iopp_name);  
            AbspFunctions\put_db_item("ABS/IOP/$p_iopp_num", 'TECH', 'PJSIP');  
            AbspFunctions\put_db_item("ABS/IOP/$p_iopp_num", 'TRUNK', $p_iopp_ident);  
            $tfnam = 'pjsip_trunk_intra_' . $p_iopp_ident . '.conf';
            $msg = $tfnam . ' に接続情報を保存しました。pjsip.confの最下行に #include ' . $tfnam . ' を追加してください。';
            
        }
    }

} // End POST


// 自局情報

    $iop_digits = AbspFunctions\get_db_item('ABS/IOP', 'DIGITS');
    $iop_here = AbspFunctions\get_db_item('ABS/IOP', 'HERE');
    $iop_here_name = AbspFunctions\get_db_item("ABS/IOP/$iop_here", 'NAME');

    $filename = ASTDIR . '/' . 'pjsip_trunk_intra_me.conf';
    $me_config = file_get_contents($filename);
    $me_config = explode("\n", $me_config);
    foreach($me_config as $line){
        if(preg_match('/^\[.*\]$/', $line)){
            $iop_here_node = str_replace('[', '', $line);
            $iop_here_node = trim(str_replace(']', '', $iop_here_node));
        }
        if(preg_match('/^username/', $line)){
            list($dummy, $iop_here_user) = explode('=', $line, 2);
            $iop_here_user = trim($iop_here_user);
        }
        if(preg_match('/^password/', $line)){
            list($dummy, $iop_here_pass) = explode('=', $line, 2);
            $iop_here_pass = trim($iop_here_pass);
        }
    }


echo <<<EOT
<form action="" method="post">
拠点番号の桁数：<input type="text" size="2" name="iop_digits", value=$iop_digits>
<input type="hidden" name="function" value="iopsetdigits">
<input type="submit" class={$_(ABSPBUTTON)} value="設定">
</form>
<br>
<h3>自局(ここ)情報</h3>
<form action="" method="post">
<table border=0 class="pure-table">
  <tr>
    <thead>
      <th>
      </th>
      <th>
      </th>
      <th>
      </th>
    </thead>
  </tr>
  <tr>
    <td>
      拠点番号
    </td>
    <td>
      <input type="text" size="2" name="iop_here" value="$iop_here">
    </td>
    <td>
      ここの拠点番号を設定します。数字のみ。桁数に注意。(2なら02と設定)
    </td>
  </tr>
  <tr>
    <td>
      拠点名
    </td>
    <td>
      <input type="text" size="8" name="iop_here_name" value="$iop_here_name">
    </td>
    <td>
      ここの名称を設定します。日本語可。
    </td>
  </tr>
  <tr>
    <td>
      識別名 
    </td>
    <td>
      <input type="text" size="8" name="iop_here_node" value="$iop_here_node">
    </td>
    <td>
      ここの識別名を指定します。英字数字のみ。
    </td>
  </tr>
  <tr>
    <td>
      ユーザ名 
    </td>
    <td>
      <input type="text" size="8" name="iop_here_user" value="$iop_here_user">
    </td>
    <td>
      外部から接続を受け入れる際のユーザ名を設定します。英字数字のみ。
    </td>
  </tr>
  <tr>
    <td>
      パスワード
    </td>
    <td>
      <input type="text" size="8" name="iop_here_pass" value="$iop_here_pass">
    </td>
    <td>
      外部から接続を受け入れる際のパスワードを設定します。英字数字のみ。
    </td>
  </tr>
</table>
<input type="hidden" name="function" value="setuphereinfo">
<input type="submit" class={$_(ABSPBUTTON)} value="設定">
</form>
EOT;

//拠点一覧

    $iops = AbspFunctions\get_db_family('ABS/IOP');
    $iop_list = array();
    foreach($iops as $line){
        if(strpos($line, '/') !== false){
            list($inum, $itemp) = explode('/', $line);
            $inum = trim($inum);
            list($iitem, $ivalue) = explode(':', $itemp);
            $iitem = trim($iitem);
            $ivalue = trim($ivalue);
            $iop_list[$inum][$iitem] = $ivalue;
        }
    }

    $n_count = count($iop_list);


echo <<<EOT
<br>
<h3>対向拠点一覧</h3>
<table border=0 class="pure-table">
  <tr>
    <thead>
      <th>拠点番号</th>
      <th>拠点名</th>
      <th>テクノロジ</th>
      <th>トランク</th>
      <th>削除</th>
    </thead>
  </tr>
EOT;

    $i = 0;

    foreach(array_keys($iop_list) as $tkey){
        if($tkey == $iop_here) $ishere = '(ここ)';
            else $ishere = '';

        if($i % 2 == 0){
            $tr_odd_class = '';
        } else {
            $tr_odd_class = 'class="pure-table-odd"';
        }
        $i++;

echo <<<EOT
    <tr $tr_odd_class>
      <td>
        $tkey$ishere
      </td>
      <td>
        {$iop_list[$tkey]['NAME']}
      </td>
      <td>
        {$iop_list[$tkey]['TECH']}
      </td>
      <td>
        {$iop_list[$tkey]['TRUNK']}
      </td>
      <td>
        <form action="" method="post">
          <input type="hidden" name="function" value="iopentdel">
          <input type="hidden" name="delent" value="$tkey">
          <input type="submit" class={$_(ABSPBUTTON)} value="削除">
        </form>
      </td>
    </tr>
EOT;
    }
echo '</table>';
echo '<br>';

echo <<<EOT
<h3>対向拠点追加</h3>
対向を追加する前に自局を正しく設定してください。<br>
<form action="" method="post">
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
      自局識別名
    </td>
    <td>
      <input type="text" size="8" name="iopp_here_node" value="$iop_here_node" readonly>
    </td>
    <td>
      自局の識別情報(変更する場合には自局情報更新してください)
    </td>
  </tr>
  <tr>
    <td>
      拠点番号
    </td>
    <td>
      <input type="text" size="2" name="iopp_num">
    </td>
    <td>
      接続先の拠点番号を指定します。数字のみ。桁数に注意。
    </td>
  </tr>
  <tr>
    <td>
      拠点名
    </td>
    <td>
      <input type="text" size="8" name="iopp_name">
    </td>
    <td>
      接続先の名称を設定します。日本語可。
    </td>
  </tr>
  <tr>
    <td>
      IPアドレス
    </td>
    <td>
      <input type="text" size="16" name="iopp_addr">
    </td>
    <td>
      接続先のIPアドレスを指定します。ポートも指定してください。
    </td>
  </tr>
  <tr>
    <td>
      識別名
    </td>
    <td>
      <input type="text" size="16" name="iopp_ident">
    </td>
    <td>
      接続先の識別名を指定します。英数字のみ。(トランク名となります)
    </td>
  </tr>
  <tr>
    <td>
      ユーザ名
    <td>
      <input type="text" size="16" name="iopp_user">
    </td>
    <td>
      接続先の認証ユーザ名を指定します。英数字のみ。
    </td>
  </tr>
  <tr>
    <td>
      パスワード
    </td>
    <td>
      <input type="text" size="16" name="iopp_pass">
    </td>
    <td>
      接続先の認証パスワードを指定します。英数字のみ。
    </td>
  </tr>
    <td>
    </td>
    <td>
    </td>
    <td>
      <input type="hidden" name="function" value="ioppadd">
      <input type="submit" class={$_(ABSPBUTTON)} value="追加">
    </td>
  </tr>
</table>
</form>
<br>
$msg
EOT;
?>
