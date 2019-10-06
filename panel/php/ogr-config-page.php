<h2>発信設定</h2>
<h3 id="ogp">プレフィクス発信</h3>
<?php

$msg = "";
$ogp_selected = array('NKS'=>'', 'KEY'=>'');

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    if($_POST['function'] == 'ogpupdate'){ //OGP設定
        for($i=1;$i<=2;$i++){
            $p_ogp_num = $_POST["ogp_num_$i"];
            $p_ogp_route = $_POST["ogp_route_$i"];
            $p_ogp_route_num = $_POST["ogp_route_num_$i"];
            $p_ogp_ogcid = $_POST["ogp_ogcid_$i"];
            $p_aec_codes = $_POST['aec_codes'];

            if($p_ogp_num == ''){ //プレフィクスが無ければデータ削除
                AbspFunctions\del_db_item('ABS', "OGP$i");
                AbspFunctions\del_db_item("ABS/OGP$i", 'KEY');
                AbspFunctions\del_db_item("ABS/OGP$i", 'NKS');
                AbspFunctions\del_db_item("ABS/OGP$i", 'OGCID');
            } else { //プレフィクスがあればデータ更新
                if(ctype_digit($p_ogp_num)){
                    AbspFunctions\put_db_item('ABS', "OGP$i", "$p_ogp_num");
                    AbspFunctions\del_db_item("ABS/OGP$i", 'KEY');
                    AbspFunctions\del_db_item("ABS/OGP$i", 'NKS');
                    if($p_ogp_route == 'NKS'){
                        AbspFunctions\put_db_item("ABS/OGP$i", "$p_ogp_route", "1");
                    }
                    if($p_ogp_route == 'KEY'){
                        AbspFunctions\put_db_item("ABS/OGP$i", "$p_ogp_route", "$p_ogp_route_num");
                    }
                }
                if($p_ogp_ogcid == ''){
                    AbspFunctions\del_db_item("ABS/OGP$i", 'OGCID');
                } else if(ctype_digit($p_ogp_ogcid)){
                    AbspFunctions\put_db_item("ABS/OGP$i", 'OGCID', "$p_ogp_ogcid");
                }
            }
        }

        if($p_aec_codes == ''){ //エリアコードがなければ削除
            AbspFunctions\del_db_item("ABS", "AEC");
        } else {
            AbspFunctions\put_db_item("ABS", "AEC", "$p_aec_codes");
        }
    }

    if($_POST['function'] == 'nksupdate'){ //NKS設定
        for($i=1;$i<=2;$i++){
            $p_nks_trunk = trim($_POST["nks_trunk_$i"]);
            $p_nks_trunk_di = trim($_POST["nks_trunk_di_$i"]);
            if($p_nks_trunk_di != '') $p_nks_trunk = $p_nks_trunk_di;
            $p_nks_tech = $_POST["nks_tech_$i"];
            $p_nks_type = $_POST["nks_type_$i"];
            if($p_nks_trunk == ''){ //トランク情報がなければ削除
                AbspFunctions\del_db_item("ABS/NKS$i", "TRUNK");
                AbspFunctions\del_db_item("ABS/NKS$i", "TECH");
                AbspFunctions\del_db_item("ABS/NKS$i", "TYP");
            } else {
                AbspFunctions\put_db_item("ABS/NKS$i", "TRUNK", $p_nks_trunk);
                AbspFunctions\put_db_item("ABS/NKS$i", "TECH", $p_nks_tech);
                if($p_nks_type == 'none'){
                    AbspFunctions\del_db_item("ABS/NKS$i", "TYP");
                } else {
                    AbspFunctions\put_db_item("ABS/NKS$i", "TYP", $p_nks_type);
                }
            }
        }
    }

    if($_POST['function'] == 'd56update'){ //D56,D67系設定

        $p_d56opt = $_POST['d56opt']; //D56オプション
        if($p_d56opt == 'on'){
            AbspFunctions\put_db_item('ABS', 'D56', '1');
        } else {
            AbspFunctions\del_db_item('ABS', 'D56');
        }

        for($i=1;$i<=4;$i++){ //D57キー設定
            $p_d57key = $_POST["d57key_$i"];
            if($p_d57key == ''){
                AbspFunctions\del_db_item('ABS/D57KEY', "$i");
            } else {
                AbspFunctions\put_db_item('ABS/D57KEY', "$i", "$p_d57key");
            }
        }
    }

    if($_POST['function'] == 'tsswadd'){ //トランクスイッチャ追加
        $p_tssw_cid = trim($_POST['tssw_cid']);
        $p_tssw_trunks = trim($_POST['tssw_trunks']);
        $p_tssw_trunkd = trim($_POST['tssw_trunkd']);
        $p_tssw_tech = $_POST['tssw_tech'];
        $p_tssw_type = $_POST['tssw_type'];

        if($p_tssw_trunkd != '') $p_tssw_trunk = $p_tssw_trunkd;
          else  $p_tssw_trunk = $p_tssw_trunks;
        if($p_tssw_cid != ''){
            AbspFunctions\put_db_item('ABS/TSSW', $p_tssw_cid, $p_tssw_trunk);
            AbspFunctions\put_db_item("ABS/TSSW/$p_tssw_cid", 'TECH', $p_tssw_tech);
            AbspFunctions\put_db_item("ABS/TSSW/$p_tssw_cid", 'TYP', $p_tssw_type);
        }
    }

    if($_POST['function'] == 'tsswdel'){ //トランクスイッチャ削除
        $p_delcid = trim($_POST['delcid']);
        if($p_delcid != ''){
            AbspFunctions\del_db_item("ABS/TSSW/$p_delcid", 'TECH');
            AbspFunctions\del_db_item("ABS/TSSW/$p_delcid", 'TYP');
            AbspFunctions\del_db_item("ABS/TSSW" , $p_delcid);
        }
    }

    if($_POST['function'] == 'tpfxadd'){ //トランクプレフィクス追加
        $p_pfx = trim($_POST['pfx']);
        $p_pfxtrunk = trim($_POST['pfxtrunk']);
        AbspFunctions\put_db_item('ABS/TRUNK/PFX', $p_pfxtrunk, $p_pfx);
    }

    if($_POST['function'] == 'tpfxdel'){ //トランクプレフィクス削除
        $p_pfxtrunk = trim($_POST['pfxtrunk']);
        AbspFunctions\del_db_item('ABS/TRUNK/PFX', $p_pfxtrunk);
    }

} // end of POST

echo <<<EOT
  <table border=0 class="pure-table">
    <form class="pure-form" action="" method="post">
    <tr>
      <thead>
        <th>番号</th>
        <th><font color="red">*</font>プレフィクス</th>
        <th>経路</th>
        <th>経路番号</th>
        <th>発信CID</th>
      </thead>
    </tr>
EOT;

for($i=1;$i<=2;$i++){

    // デフォルト値
    $ogp_num = '';
    $ogp_route = '';
    $ogp_route_num = '';
    $ogp_ogcid= '';

    $ogp_num = AbspFunctions\get_ogp_num($i);
    // プレフィクスが設定されていないものは意味がないので処理しない
    if($ogp_num != ''){

        $ogp_route = AbspFunctions\get_ogp_route($i);
        $ogp_selected = array('NKS'=>'', 'KEY'=>'');
        $ogp_selected["$ogp_route"] = "selected";

        $ogp_route_num = AbspFunctions\get_ogp_routenum($i);

        $ogp_ogcid = AbspFunctions\get_ogp_ogcid($i);

    }

    $ogp_aec_codes = AbspFunctions\get_aec_codes();

echo <<<EOT
    <tr>
      <td align="right">
        OGP$i 
      </td>
      <td nowrap>
        <input type=\"txt\" size="2" name="ogp_num_$i" value="$ogp_num">
      </td>
      <td>
        <select name="ogp_route_$i">
          <option value="NKS" {$ogp_selected['NKS']}>NKS</option>
          <option value="KEY" {$ogp_selected['KEY']}>KEY</option>
        </select>
      </td>
      <td>
        <input type=\"txt\" size="4" name="ogp_route_num_$i" value="$ogp_route_num">
      </td>
      <td>
        <input type=\"txt\" size="8" name="ogp_ogcid_$i" value="$ogp_ogcid">
      </td>
EOT;

} /* end of for */

echo <<<EOT
    </tr>
  </table>
注意:NKS指定時には経路番号は'1'を指定。発信経路はプレフィクスで切り分けられる。
<br>
<br>
市内局番(先頭,カンマ区切り):
  <input type="txt" size="8" name="aec_codes" value="$ogp_aec_codes">
  <br>
  <input type="hidden" name="function" value="ogpupdate">
  <input type="submit" class={$_(ABSPBUTTON)} value="設定変更">
</form>
<br>
<br>
EOT;

echo <<<EOT
<h3 id="nks">ノーキーシステム設定(発信経路)</h3>
<table border=0 class="pure-table">
  <tr>
    <thead>
      <th>番号</th>
      <th>TECH</th>
      <th>トランク(選択または直接入力)</th>
      <th>種別</th>
    </thead>
  </tr>
EOT;

for($i=1;$i<=2;$i++){

    // デフォルト値
    $nks_tech = 'SIP';
    $nks_type = '';
    $tech_selected = array('SIP'=>'', 'PJSIP'=>'');
    $tech_selected["SIP"] = "selected";
    $type_selected = array('none'=>'', 'NTTE'=>'', 'NTTW'=>'', 'BASIX'=>'', 'UAREA'=>'');
    $type_selected["none"] = "selected";

    $nks_trunk = AbspFunctions\get_nks_trunk($i);
    // トランク情報が設定されていないものは意味がないので処理しない
    if($nks_trunk != ''){

        $nks_tech = AbspFunctions\get_nks_tech($i);
        $tech_selected["$nks_tech"] = "selected";

        $nks_type = AbspFunctions\get_nks_type($i);
        $type_selected["$nks_type"] = "selected";


    }

    $trunk_list = AbspFunctions\create_trunk_list('', $nks_trunk);
    if($nks_trunk == ''){
        $trunk_list = "<select name=\"nks_trunk_$i\">" . $trunk_list . "</select>";
    } else if(strpos($trunk_list, $nks_trunk) === false){
        $trunk_list = "<input type=\"text\" size=\"8\" name=\"nks_trunk_$i\" value=\"$nks_trunk\">";
    } else {
        $trunk_list = "<select name=\"nks_trunk_$i\">" . $trunk_list . "</select>";
    }

echo <<<EOT
  <form action="" method="post">
  <input type="hidden" name="function" value="nksupdate">
  <tr>
    <td align="right">
      NKS$i (OGP$i に対応) 
    </td>
    <td>
      <select name="nks_tech_$i">
        <option value="SIP" {$tech_selected['SIP']}>SIP</option>
        <option value="PJSIP" {$tech_selected['PJSIP']}>PJSIP</option>
      </select>
    </td>
    <td nowrap>
      $trunk_list
      <input type="txt" size="8" name="nks_trunk_di_$i">
    </td>
    <td>
      <select name="nks_type_$i">
        <option value="none" {$type_selected['none']}>なし</option>
        <option value="NTTE" {$type_selected['NTTE']}>NTT東</option>
        <option value="NTTW" {$type_selected['NTTW']}>NTT西</option>
        <option value="BASIX" {$type_selected['BASIX']}>BASIX</option>
        <option value="UAREA"  {$type_selected['UAREA']}>ユーザ定義</option>
      </select>
    </td>
  </tr>
EOT;

} /* end of for */


echo <<<EOT
</table>
注意:OGPでKEYが指定されている場合にはノーキーシステムは使用されない。
<br>
<input type="submit" class={$_(ABSPBUTTON)} value="設定変更">
</form>
<br>
<br>
EOT;

    $d56opt = AbspFunctions\get_db_item('ABS', "D56");
    if($d56opt == "1"){
      $d56ckd = "checked";
    } else {
      $d56ckd = "";
    }

echo <<<EOT
<h3 id="d56option">キー捕捉特番設定</h3>
<form action="#d56option" method="post">
  D56(*56[キー番号])特番発信
  &nbsp;
  <input type="hidden" name="function" value="d56update">
  <input type="checkbox" name="d56opt" value="on" $d56ckd>
  <br>
  D57(*57[番号])特番発信
  <table border=0 class="pure-table">
    <tr>
      <thead>
        <th>番号</th>
        <th>キー範囲(ハイフン区切)</th>
      </thead>
    </tr>
EOT;


for($i=1;$i<=4;$i++){

    $d57key = AbspFunctions\get_db_item('ABS/D57KEY', "$i");

echo <<<EOT
    <tr>
      <td align="right">
        $i
      </td>
      <td>
        <input type="txt" size="4" name="d57key_$i" value="$d57key">
      </td>
    </tr>
EOT;

} /* end of for */

echo <<<EOT
  </table>
  <input type="submit" class={$_(ABSPBUTTON)} value="設定変更">
</form>
EOT;

//トランクスイッチャ
echo <<<EOT
<br>

<hr>
<h2>トランク特殊設定</h2>
<h3 id="tssw">トランクスイッチャ</h3>
<table border=0 class="pure-table">
  <tr>
    <thead>
      <th>発信CID</th>
      <th>TECH</th>
      <th>トランク</th>
      <th>種別</th>
      <th></th>
    </thead>
  </tr>
EOT;

$tssw_list = AbspFunctions\create_tssw_list();

if(@count($tssw_list) != 0){
    foreach($tssw_list as $line){
        $tssw_cid = $line;
        $tssw_trunk = AbspFunctions\get_db_item("ABS/TSSW", $tssw_cid);
        $tssw_tech = AbspFunctions\get_db_item("ABS/TSSW/$tssw_cid", 'TECH');
        $tssw_type = AbspFunctions\get_db_item("ABS/TSSW/$tssw_cid", 'TYP');
echo <<<EOT
  <tr>
    <td>
      $tssw_cid
    </td>
    <td>
      $tssw_tech
    </td>
    <td>
      $tssw_trunk
    </td>
    <td>
      $tssw_type
    </td>
    <td>
      <form action="" method="post">
        <input type="hidden" name="delcid" value=$tssw_cid>
        <input type="hidden" name="function" value="tsswdel">
        <input type="submit" class={$_(ABSPBUTTON)} value="削除">
      </form>
  </tr>
EOT;
    }
}

echo <<<EOT
</table>
EOT;

    $trunk_list = AbspFunctions\create_trunk_list();

echo <<<EOT
<h3>トランクスイッチャ追加</h3>
<form action="" method="post">
<table border=0 class="pure-table">
  <tr>
    <thead>
      <th>発信CID</th>
      <th>TECH</th>
      <th>トランク(選択または直接入力)</th>
      <th>種別</th>
      <th></th>
    </thead>
  <tr>
  <form action="" method="post">
  <input type="hidden" name="function" value="tsswadd">
  <tr>
    <td>
      <input type="text" name="tssw_cid" size="8">
    </td>
    <td>
      <select name="tssw_tech">
        <option value="SIP">SIP</option>
        <option value="PJSIP">PJSIP</option>
      </select>
    </td>
    <td nowrap>
      <select name="tssw_trunks">
        $trunk_list
      </select>
      <input type="txt" size="8" name="tssw_trunkd">
    </td>
    <td>
      <select name="tssw_type">
        <option value="none">なし</option>
        <option value="NTTE">NTT東</option>
        <option value="NTTW">NTT西</option>
        <option value="BASIX">BASIX</option>
        <option value="UAREA">ユーザ定義</option>
      </select>
    </td>
    <td>
      <input type="submit" class={$_(ABSPBUTTON)} value="追加">
    </td>
  </tr>
</table>
</form>
EOT;


//トランクプレフィクス
echo <<<EOT
<br>
<h3 id="tssw">トランクプレフィクス</h3>
<table border=0 class="pure-table">
  <tr>
    <thead>
      <th>トランク名</th>
      <th>プレフィクス</th>
      <th></th>
    </thead>
  </tr>
EOT;

$tpfxs = AbspFunctions\get_db_family('ABS/TRUNK/PFX');

if($tpfxs != ''){
  foreach($tpfxs as $line){
    list($trunk, $prefix) = explode(':', $line, 2);
    $trunk = trim($trunk);
    $prefix = trim($prefix);

echo <<<EOT
  <tr>
    <form action="" method="post">
      <td>$trunk</td>
      <td>$prefix</td>
      <td><input type="submit" class={$_(ABSPBUTTON)} value="削除"></td>
      <input type="hidden" name="function" value="tpfxdel">
      <input type="hidden" name="pfxtrunk" value=$trunk>
    </form>
  </tr>
EOT;

  } //end foreach
}

echo "</table>";

echo <<<EOT
<h3>トランクプレフィクス追加</h3>
<form action="" method="post">
<table border=0 class="pure-table">
  <tr>
    <thead>
      <th>トランク名</th>
      <th>プレフィクス</th>
      <th></th>
    </thead>
  </tr>
  <tr>
    <td>
      <input type="text" size="10" name="pfxtrunk">
    </td>
    <td>
      <input type="text" size="2" name="pfx">
    </td>
    <td>
      <input type="hidden" name="function" value="tpfxadd">
      <input type="submit" class={$_(ABSPBUTTON)} value="設定">
    </td>
  </tr>
</table>
</form>
EOT;

?>

