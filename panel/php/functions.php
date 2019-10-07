<?php
namespace AbspFunctions;

include 'amiauth.php';


// AstDBからの値取得
function get_db_item($item_name, $param){

    $astman = new AstMan();
    $astman->Login('localhost', AMIUSERNAME, AMIPASSWORD);
    $retval = $astman->GetDB($item_name, $param);
    $astman->Logout();

    return $retval;
}

// AstDBへの値設定
// 挙動注意: $paramが空の場合にはそのエントリを削除
function put_db_item($item_name, $key, $param){

    if(($item_name != '') && ($key != '')){
        $astman = new AstMan();
        $astman->Login('localhost',AMIUSERNAME, AMIPASSWORD);
        if($param != ''){
            $retval = $astman->PutDB($item_name, $key, $param);
        } else {
            $retval = $astman->DelDB($item_name, $key);
        }
        $astman->Logout();
        return $retval;
    }

    return false;
}

// AstDBからの値削除
function del_db_item($item_name, $param){

    if(($item_name != '') && ($param != '')){
        $astman = new AstMan();
        $astman->Login('localhost', AMIUSERNAME, AMIPASSWORD);
        $retval = $astman->DelDB($item_name, $param);
        $astman->Logout();
        return $retval;
    }

    return false;
}

// AstDBからのファミリ単位取得
function get_db_family($item_name){

    $astman = new AstMan();
    $astman->Login('localhost', AMIUSERNAME, AMIPASSWORD);
    $retval = $astman->GetFamilyDB($item_name);
    $astman->Logout();

    return $retval;
}

// Asterisk コマンド実行
function exec_cli_command($param){

    if($param != ''){
        $astman = new AstMan();
        $astman->Login('localhost', AMIUSERNAME, AMIPASSWORD);
        $retval = $astman->ExecCMD($param);
        $astman->Logout();
        return $retval;
    }

    return '';
}

// ピアとその内線情報取得
// $peername : ピア名(phone1,phone2,et...)
// 戻値: Array ( [peer] => phone1 [exten] => 201 [limit] => 2
//               [ogcid] => 0312345678 [pgrp] => 1 ) 
function get_peer_info($peername){

    $peer_info = array();

    $peer_info['peer'] = $peername;

    $astman = new AstMan();
    $astman->Login('localhost', AMIUSERNAME, AMIPASSWORD);

    $ext = $astman->GetDB('ABS/ERV', $peername);

    // 内線が割り当てられていないピアは処理しない
    if($ext == ''){
        $peer_info['exten'] = "";
        $peer_info['limit'] = "";
        $peer_info['ogcid'] = "";
        $peer_info['pgrp']  = "";
    } else {
        $peer_info['exten'] = $ext;
        $peer_info['limit'] = $astman->GetDB('ABS/LMT', $peername);
        $peer_info['ogcid'] = $astman->GetDB("ABS/EXT/$ext", 'OGCID');
        $peer_info['pgrp']  = $astman->GetDB("ABS/EXT/$ext", 'PGRP');
    }

    $astman->Logout();
    return $peer_info;
}

// ピアとその内線情報設定
// array($peer_info)
// 設定値: Array ( [peer] => phone1 [exten] => 201 [limit] => 2
//               [ogcid] => 0312345678 [pgrp] => 1 ) 
function set_peer_info($peer_info){

    $p_peer    = $peer_info['peer']; //処理対象のピア
    $p_exten   = $peer_info['exten']; //設定するexten
    $p_p_exten = $peer_info['p_exten']; // 以前のexten
    $p_limit   = $peer_info['limit'];
    $p_ogcid   = $peer_info['ogcid'];
    $p_pgrp    = $peer_info['pgrp'];

    $retval = '';

    $astman = new AstMan();
    $astman->Login('localhost', AMIUSERNAME, AMIPASSWORD);

    //内線番号が指定されていない場合には該当するピアの情報を削除
    if($p_exten == ''){
       // ERV削除
       $astman->DelDB('ABS/ERV', $p_peer);
       $astman->DelDB('ABS/LMT', $p_peer);
       // 以前のextenに結びついていた情報を削除
       $astman->DelDB("ABS/EXT/$p_p_exten", 'OGCID');
       $astman->DelDB("ABS/EXT/$p_p_exten", 'PGRP');
       $astman->DelDB('ABS/EXT', $p_p_exten);
       $retval = '削除完了';
    } else if(ctype_digit($p_exten)){ //内線番号が指定されている場合には情報更新

        //指定された内線番号を使用しているピアを取得
        $p_cpeer = $astman->GetDB('ABS/EXT', $p_exten);
        if($p_peer == $p_cpeer){ //自分のピアと同じなら更新

            if($p_limit == ''){
                $astman->DelDB('ABS/LMT', $p_peer);
            } else {
                $astman->PutDB('ABS/LMT', $p_peer, $p_limit);
            }

            if($p_ogcid == ''){
                $astman->DelDB("ABS/EXT/$p_exten", 'OGCID');
            } else {
                if(ctype_digit($p_ogcid)){
                    $astman->PutDB("ABS/EXT/$p_exten", 'OGCID', $p_ogcid);
                }
            }

            if($p_pgrp == ''){
                $astman->DelDB("ABS/EXT/$p_exten", 'PGRP');
            } else {
                if(ctype_digit($p_exten)){
                    $astman->PutDB("ABS/EXT/$p_exten", 'PGRP', $p_pgrp);
                }
            }
            $retval = '変更完了';

        } else if($p_cpeer == ''){ //使用しているピアがなければ新規内線番号
            $astman->PutDB('ABS/ERV', $p_peer, $p_exten);
            $astman->PutDB('ABS/EXT', $p_exten, $p_peer);

            if($p_limit != '') $astman->PutDB('ABS/LMT', $p_peer, $p_limit);

            if($p_ogcid != ''){
                if(ctype_digit($p_ogcid)){
                    $astman->PutDB("ABS/EXT/$p_exten", 'OGCID', $p_ogcid);
                }
            }

            if($p_pgrp != ''){
                if(ctype_digit($p_pgrp)){
                    $astman->PutDB("ABS/EXT/$p_exten", 'PGRP', $p_pgrp);
                }
            }
            $retval = '登録完了';

        } else {
            $retval = '番号重複';
        }
    }

    $astman->Logout();
    return $retval;
}

// グループ情報取得
// $grp : グループ番号(1,2,3...)
// 戻値: Array ( [group] => 1 [member] => 201,206,208 [exten] => 888 [mode] => RA 
//               [timeout] => 10 [ovr] => [bnl] => [bnt] => ) 
function get_group_info($grp){

    $group_info = array();

    $group_info['group'] = $grp;

    $astman = new AstMan();
    $astman->Login('localhost', AMIUSERNAME, AMIPASSWORD);

    $member = $astman->GetDB("ABS/GRP", "$grp");

    // メンバが割り当てられていないグループは処理しない
    if($member == ''){
        $group_info['member'] = "";
        $group_info['exten']  = "";
        $group_info['mode'] = "";
        $group_info['timeout'] = "";
        $group_info['ovr'] = "";
        $group_info['bnl'] = "";
        $group_info['bnt'] = "";
    } else {
        $group_info['member'] = $member;
        $group_info['exten']   = $astman->GetDB("ABS/GRP/$grp", "EXT");
        $group_info['mode']    = $astman->GetDB("ABS/GRP/$grp", "MET");
        $group_info['timeout'] = $astman->GetDB("ABS/GRP/$grp", "TMO");
        $group_info['ovr']     = $astman->GetDB("ABS/GRP/$grp", "OVR");
        $group_info['bnl']     = $astman->GetDB("ABS/GRP/$grp", "BNL");
        $group_info['bnt']     = $astman->GetDB("ABS/GRP/$grp", "BNT");
    }

    $astman->Logout();
    return $group_info;
}

// グループ情報設定
// 設定値: Array ( [group] => 1 [member] => 201,206,208 [exten] => 888 [mode] => RA 
//               [timeout] => 10 [ovr] => [bnl] => [bnt] => ) 
//
function set_group_info($group_info){

    $p_member = $group_info['member'];
    $p_grp    = $group_info['group'];
    $p_mode   = $group_info['mode'];
    $p_exten  = $group_info['exten'];
    $p_timeout= $group_info['timeout'];
    $p_ovr    = $group_info['ovr'];
    $p_bnl    = $group_info['bnl'];
    $p_bnt    = $group_info['bnt'];

    $astman = new AstMan();
    $astman->Login('localhost', AMIUSERNAME, AMIPASSWORD);

    if($p_member == ''){ //メンバがない場合には該当エントリ削除
        //内線が割り当てられているか取得
        $tmp_ext = $astman->GetDB("ABS/GRP/$p_grp", 'EXT');
        if($tmp_ext != ''){ //割り当て内線がある場合は自グループなら削除
            if($tmp_ext == "G$p_grp") $astman->DelDB('ABS/EXT', $tmp_ext);
        }

        $astman->DelDB('ABS/GRP', $p_grp);
        $astman->DelDB("ABS/GRP/$p_grp", 'EXT');
        $astman->DelDB("ABS/GRP/$p_grp", 'TMO');
        $astman->DelDB("ABS/GRP/$p_grp", 'MET');
        $astman->DelDB("ABS/GRP/$p_grp", 'OVR');
        $astman->DelDB("ABS/GRP/$p_grp", 'BNL');
        $astman->DelDB("ABS/GRP/$p_grp", 'BNT');
        $astman->DelDB("ABS/GRP/$p_grp", 'LAST');
        $retval = '削除完了';
    } else { //メンバがあれば登録更新
        //以前に割り当てられていた内線があれば削除
        $tmp_ext = $astman->GetDB("ABS/GRP/$p_grp", 'EXT');
        if($tmp_ext != ''){ //割り当て内線がある場合は自グループなら削除
            if($tmp_ext == "G$p_grp") $astman->DelDB('ABS/EXT', $tmp_ext);
        }
        //更新前に全削除
        $astman->DelDB('ABS/GRP', $p_grp);
        $astman->DelDB("ABS/GRP/$p_grp", 'EXT');
        $astman->DelDB("ABS/GRP/$p_grp", 'TMO');
        $astman->DelDB("ABS/GRP/$p_grp", 'MET');
        $astman->DelDB("ABS/GRP/$p_grp", 'OVR');
        $astman->DelDB("ABS/GRP/$p_grp", 'BNL');
        $astman->DelDB("ABS/GRP/$p_grp", 'BNT');
        $astman->DelDB("ABS/GRP/$p_grp", 'LAST');

        $astman->PutDB('ABS/GRP', $p_grp, $p_member);
        if(ctype_digit($p_timeout)){
            $astman->PutDB("ABS/GRP/$p_grp", 'TMO', $p_timeout);
        }
        if(ctype_digit($p_bnt)){
            $astman->PutDB("ABS/GRP/$p_grp", 'BNT', $p_bnt);
        }
        $astman->PutDB("ABS/GRP/$p_grp", 'OVR', $p_ovr);
        $astman->PutDB("ABS/GRP/$p_grp", 'MET', $p_mode);
        $astman->PutDB("ABS/GRP/$p_grp", 'BNL', $p_bnl);
        $retval = '登録完了';

        if(ctype_digit($p_exten)){
            // 内線重複チェック
            $tmp = $astman->GetDB('ABS/EXT', $p_exten);
            if($tmp == "G$p_grp") $tmp =''; //自グループの内線番号なら上書きする
            if($tmp == ''){ //重複なし
                $astman->PutDB("ABS/GRP/$p_grp", 'EXT', $p_exten);
                $astman->PutDB('ABS/EXT', $p_exten, "G$p_grp");
                $retval = '登録完了';
            } else {
                $retval = '内線重複';
            }
        }
    }

    $astman->Logout();
    return $retval;
}

// キー情報取得
// $key : キー番号
// 戻値: Array ( [key] => 1 [trunk] => hikari-hgw [label] => 外線 [tech] => PJSIP 
//       [type] => NTTE [ogcid] => 0312345678 [rgrp] => G1 [bpin] => 1234 [rgpt] => 1 [mmd] => S) 
function get_key_info($key){

    $key_info = array();

    $key_info['key'] = $key;

    $astman = new AstMan();
    $astman->Login('localhost', AMIUSERNAME, AMIPASSWORD);

    $trunk = $astman->GetDB("KEYTEL/KEYSYS$key", "TRUNK");
    $rgrp  = $astman->GetDB("KEYTEL/KEYSYS$key", "RING");

    $key_info['trunk'] = $trunk;
    $key_info['label'] = $astman->GetDB("KEYTEL/KEYSYS$key", "LABEL");
    $key_info['tech']  = $astman->GetDB("KEYTEL/KEYSYS$key", "TECH");
    $key_info['type']  = $astman->GetDB("KEYTEL/KEYSYS$key", "TYP");
    $key_info['ogcid'] = $astman->GetDB("KEYTEL/KEYSYS$key", "OGCID");
    $key_info['rgrp']  = $rgrp;
    $key_info['bpin']  = $astman->GetDB("KEYTEL/KEYSYS$key", "BPIN");
    $key_info['rgpt']  = $astman->GetDB("KEYTEL/KEYSYS$key", "RGPT");
    $key_info['mmd']  = $astman->GetDB("KEYTEL/KEYSYS$key", "MMD");

    $astman->Logout();
    return $key_info;
}

// キー情報設定
// 設定値: Array ( [key] => 1 [trunk] => hikari-hgw [label] => 外線 [tech] => PJSIP
//       [type] => NTTE [ogcid] => 0312345678 [rgrp] => G1 [bpin] => 1234 [rgpt] => 1 [mmd]] => S)
function set_key_info($key_info){

    $key = $key_info['key'];

    $astman = new AstMan();
    $astman->Login('localhost', AMIUSERNAME, AMIPASSWORD);

    $trunk = $key_info['trunk'];
    $label = $key_info['label'];
    $rgrp  = $key_info['rgrp'];
    $tech = $key_info['tech'];
    $type = $key_info['type'];
    $ogcid = $key_info['ogcid'];
    $rgrp = $key_info['rgrp'];
    $rgpt = $key_info['rgpt'];
    $bpin = $key_info['bpin'];
    $mmd = $key_info['mmd'];

    //登録前に情報削除
    $astman->DelDB("KEYTEL/KEYSYS$key", 'LABEL');
    $astman->DelDB("KEYTEL/KEYSYS$key", 'TECH');
    $astman->DelDB("KEYTEL/KEYSYS$key", 'TRUNK');
    $astman->DelDB("KEYTEL/KEYSYS$key", 'TYP');
    $astman->DelDB("KEYTEL/KEYSYS$key", 'OGCID');
    $astman->DelDB("KEYTEL/KEYSYS$key", 'RGRP');
    $astman->DelDB("KEYTEL/KEYSYS$key", 'RGPT');
    $astman->DelDB("KEYTEL/KEYSYS$key", 'BPIN');
    $astman->DelDB("KEYTEL/KEYSYS$key", 'MMD');

    $astman->PutDB("KEYTEL/KEYSYS$key", "LABEL", $label);
    $astman->PutDB("KEYTEL/KEYSYS$key", "TECH", $tech);
    $astman->PutDB("KEYTEL/KEYSYS$key", "TRUNK", $trunk);
    $astman->PutDB("KEYTEL/KEYSYS$key", "TYP", $type);
    $astman->PutDB("KEYTEL/KEYSYS$key", "MMD", $mmd);
    if(ctype_digit($ogcid)){
        $astman->PutDB("KEYTEL/KEYSYS$key", "OGCID", $ogcid);
    }
    $astman->PutDB("KEYTEL/KEYSYS$key", "RING", $rgrp);
    $astman->PutDB("KEYTEL/KEYSYS$key", "RGPT", $rgpt);
    if(ctype_digit($bpin)){
         $astman->PutDB("KEYTEL/KEYSYS$key", "BPIN", $bpin);
    }
    $retval = '登録完了';

    $astman->Logout();
    return $retval;
}

// 着信先ターゲットリスト生成
function create_target_list($option = '' , $selected = ''){
    //TOPはempty
    $select_list = "<option value=\"\" ></option>\n";

    $astman = new AstMan();
    $astman->Login('localhost', AMIUSERNAME, AMIPASSWORD);

    //内線
    for($i=1;$i<=32;$i++){
        $ext = $astman->GetDB("ABS/ERV", "phone$i");
        if($ext != ''){
            if($ext == $selected)
                $select_list .= "<option value=\"$ext\" selected>$ext</option>\n";
            else  $select_list .= "<option value=\"$ext\">$ext</option>\n";
        }
    }
    //オプション指定があればグループも
    if($option == 'group'){
        for($i=1;$i<=16;$i++){
            $member = $astman->GetDB("ABS/GRP", "$i");
            //メンバのあるグループのみ処理
            if($member != ''){
                if("G$i" == $selected)
                    $select_list .= "<option value=\"G$i\" selected>G$i</option>\n";
                else  $select_list .= "<option value=\"G$i\">G$i</option>\n";
             }
         }
    }

    $astman->Logout();
    return $select_list;
}

// トランクスイッチャリスト生成
function create_tssw_list(){
    $astman = new AstMan();
    $astman->Login('localhost', AMIUSERNAME, AMIPASSWORD);
    $retval = $astman->GetFamilyDB('ABS/TSSW');
    $astman->Logout();

    $r_list = array();
    $i = 0;
    if(count($retval) > 2){
        foreach($retval as $line){
            if(strpos($line, "/") === false){
                list($t_cid, $dummy) = explode(":", $line, 2);
                $r_list[$i] = trim($t_cid);
                $i++;
            }
        }
    }
    return $r_list;
}

// トランクリスト生成
function create_trunk_list($option = '', $selected = ''){
    //TOPはempty
    $select_list = "<option value=\"\" ></option>\n";
    $dbg ='';

    $astman = new AstMan();
    $astman->Login('localhost', AMIUSERNAME, AMIPASSWORD);

    $tret = $astman->ExecCMD('pjsip show registrations');
    $tret = str_replace('Output: ', '', $tret);
    $tlist = explode("\n", $tret);
    $i = 0;
    if(count($tlist) !== 0){
        foreach($tlist as $line){
            if(strpos($line, "/") !== false){
                if($line != ''){
                    list($trlist[$i], $dummy) = explode('/', $line, 2);
                    $trlist[$i] = trim($trlist[$i]);
                    $i++;
                }
            }
        }
        for($j=1;$j<$i;$j++){
            if($trlist[$j] == $selected)
                $select_list .= "<option value=\"$trlist[$j]\" selected>$trlist[$j]</option>\n";
            else  $select_list .= "<option value=\"$trlist[$j]\">$trlist[$j]</option>\n";
        }
    }
    $astman->Logout();
    return $select_list;
}

// ピックアップグループメンバ取得
function get_pgrp_member($grp){
    $retval = get_db_item("ABS/PGRP", "$grp");
    return $retval;
}

// ピックアップグループ更新
function set_pgrp_member($pgrp, $member){

    $astman = new AstMan();
    $astman->Login('localhost', AMIUSERNAME, AMIPASSWORD);

    if($member == ''){ //メンバがない場合には該当エントリ削除
        $astman->DelDB('ABS/PGRP', $pgrp);
        $retval = '削除完了';
    } else { //メンバがあれば登録更新
        $astman->PutDB('ABS/PGRP', $pgrp, $member);
        $retval = '登録完了';
    }

    $astman->Logout();
    return $retval;
}

function get_nks_tech($num){
    $retval = get_db_item("ABS/NKS$num", "TECH");
    return $retval;
}

function get_nks_trunk($num){
    $retval = get_db_item("ABS/NKS$num", "TRUNK");
    return $retval;
}

function get_nks_type($num){
    $retval = get_db_item("ABS/NKS$num", "TYP");
    return $retval;
}

function get_ogp_num($num){
    $retval = get_db_item("ABS", "OGP$num");
    return $retval;
}

function get_ogp_route($num){
    $retval = get_db_item("ABS/OGP$num", "NKS");
    if($retval != '') return "NKS";
    $retval = get_db_item("ABS/OGP$num", "KEY");
    if($retval != '') return "KEY";
    return "";
}

function get_ogp_routenum($num){
    $retval = get_db_item("ABS/OGP$num", "NKS");
    if($retval != ''){
          $retval = get_db_item("ABS/OGP$num", "NKS");
          return $retval;
    }
    $retval = get_db_item("ABS/OGP$num", "KEY");
    if($retval != ''){
          $retval = get_db_item("ABS/OGP$num", "KEY");
          return $retval;
    }
    return $retval;
}

function get_ogp_ogcid($num){
    $retval = get_db_item("ABS/OGP$num", "OGCID");
    return $retval;
}

function get_aec_codes(){
    $retval = get_db_item("ABS", "AEC");
    return $retval;
}

?>
