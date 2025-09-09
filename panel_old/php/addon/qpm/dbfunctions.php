<?php
namespace DBFUNC;

include 'config.php';

//エントリの更新,存在しなければ追加
function update_db($num, $cname, $cat, $pname='' ,$zip='' ,$addr='' ,$pn1='' ,$pn2='' ,$pn3='' ,$pn4='' ,$memo1='' ,$memo2='', $fpfx='',  $attend='', $last=''){

    if($num == "") return FALSE;
    if($cname == "") return FALSE;

    $db = new \SQLite3(QPMDB);
    // Check exsiting entry
    $query = "SELECT * FROM qpm WHERE num='". $num  . "'";
    $res = $db->query($query);

    if($res->fetchArray() == FALSE){
        $update_cmd = "INSERT INTO qpm values("
            .  "'" . $num
            . "','"  . $cname
            . "','"  . $cat
            . "','" . $pname
            . "','" . $zip
            . "','" . $addr
            . "','" . $pn1
            . "','"  . $pn2
            . "','" . $pn3
            . "','" . $pn4
            . "','" . $memo1
            . "','" . $memo2
            . "','" . $fpfx
            . "','" . $attend
            . "','" . $last
            . "')"; 
        //echo $update_cmd;
        //echo "\n";
    } else {
        $update_cmd = "UPDATE qpm SET"
            .  " cname='" . $cname
            . "' ,cat='" . $cat
            . "' ,pname='" . $pname
            . "' ,zip='" . $zip
            . "' ,addr='" . $addr
            . "' ,pn1='" . $pn1
            . "' ,pn2='"  . $pn2
            . "' ,pn3='" . $pn3
            . "' ,pn4='" . $pn4
            . "' ,memo1='" . $memo1
            . "' ,memo2='" . $memo2
            . "' ,fpfx='" . $fpfx
            . "' ,attend='" .$attend 
            . "' ,last='" . $last
            . "'"
            . " WHERE num='" . $num . "'"; 
        //echo $update_cmd;
        //echo "\n";
    }

    $res = $db->query($update_cmd);

    return TRUE;

} //update_db


//エントリ削除
function delete_entry($num){

    if($num == "") return FALSE;

    $db = new \SQLite3(QPMDB);

    $query = "DELETE FROM qpm WHERE num='". $num  . "'";
    $res = $db->querySingle($query);

    return TRUE;
}


//番号完全一致検索
function num_exact_search($num){

    if($num == "") return FALSE;

    $db = new \SQLite3(QPMDB);

    $query = "SELECT * FROM qpm WHERE num='". $num  . "'";
    $res = $db->querySingle($query, TRUE);

    return $res;

} //num_exact_search


//詳細検索
function entry_detail_search($num='',$cname='',$cat='',$pname='',$zip='',$addr='',$pn1='',$pn2='',$pn3='',$pn4=''){

    $q_entry = [];
    $q_string = '';

    if($num != '') array_push($q_entry, sprintf("num like '%%%s%%'", $num));
    if($cname != '') array_push($q_entry, sprintf("cname like '%%%s%%'", $cname));
    if($cat != '') array_push($q_entry, sprintf("cat like '%%%s%%'", $cat));
    if($pname != '') array_push($q_entry, sprintf("pname like '%%%s%%'", $pname));
    if($zip != '') array_push($q_entry, sprintf("zip like '%%%s%%'", $zip));
    if($addr != '') array_push($q_entry, sprintf("addr like '%%%s%%'", $addr));
    if($pn1 != '') array_push($q_entry, sprintf("pn1 like '%%%s%%'", $pn1));
    if($pn2 != '') array_push($q_entry, sprintf("pn2 like '%%%s%%'", $pn2));
    if($pn3 != '') array_push($q_entry, sprintf("pn3 like '%%%s%%'", $pn3));
    if($pn4 != '') array_push($q_entry, sprintf("pn4 like '%%%s%%'", $pn4));

    if(count($q_entry) < 1) return FALSE;

    foreach($q_entry as $ent){
        $q_string .=  $ent . " and ";
    }
    // Remove trailing 'and'
    $q_string = rtrim($q_string, " and");

    $db = new \SQLite3(QPMDB);
    // Check exsiting entry
    $query = "SELECT * FROM qpm WHERE " . $q_string;
    //print($query);

    $res = $db->query($query);
    if($res === FALSE) return(FALSE);

    $ret_array = [];
    while($res_a = $res->fetchArray(SQLITE3_ASSOC)){ 
        //var_dump($res_a);
        //print('<BR>');
        array_push($ret_array, $res_a);
    }
    $res->finalize();

    if(count($ret_array) == 0) return FALSE;

    return $ret_array;

}

//パスワード(token)取得
function get_db_password($user){

    if($user == "") return FALSE;

    $db = new \SQLite3(QPMDB);

    $query = "SELECT password  FROM qpm_users WHERE login='" . $user  . "'";
    $res = $db->querySingle($query);

    return $res;
}

//ログイン名から名前へ変換
function u_login_name($login){

    if($login == "") return "";

    $db = new \SQLite3(QPMDB);

    $query = "SELECT dname  FROM qpm_users WHERE login='" . $login  . "'";
    $res = $db->querySingle($query);

    if($res == "" | $res == FALSE) return $login;
    else return $res;

}

//ログイン名から内線を取得
function u_login_ext($login){

    if($login == "") return "";

    $db = new \SQLite3(QPMDB);

    $query = "SELECT ext  FROM qpm_users WHERE login='" . $login  . "'";
    $res = $db->querySingle($query);

    if($res == "" | $res == FALSE) return $login;
    else return $res;
}

//カテゴリ一覧取得
function get_ccat(){

    $db = new \SQLite3(QPMDB);

    $query = "SELECT cat FROM qpm_cats";

    $res = $db->query($query);
    if($res === FALSE) return(FALSE);

    $ret_array = [];
    while($res_a = $res->fetchArray(SQLITE3_ASSOC)){
        //var_dump($res_a);
        //print('<BR>');
        array_push($ret_array, $res_a);
    }
    $res->finalize();

    if(count($ret_array) == 0) return FALSE;

    return $ret_array;

}

//カテゴリ更新(なければ追加)
function update_ccat($cat){

    if($cat == "") return "";

    $db = new \SQLite3(QPMDB);

    $query = "SELECT cat  FROM qpm_cats WHERE cat ='" . $cat  . "'";
    $res = $db->querySingle($query);

    if($res == ""){
        $query = "INSERT INTO qpm_cats VALUES('" . $cat  . "')";
        $res = $db->querySingle($query);
    }

    return "";

}


// AstDB操作系
// 以下はastman.php必要

// AstDBからの値取得
function get_astdb_item($item_name, $param){

    $astman = new AstMan();
    $astman->Login('localhost', AMIUSERNAME, AMIPASSWORD);
    $retval = $astman->GetDB($item_name, $param);
    $astman->Logout();

    return $retval;
}

// AstDBのCIDnameを設定する(cidname/number)
function put_astdb_cidname($num, $cidname){

    $astman = new AstMan();
    $astman->Login('localhost', AMIUSERNAME, AMIPASSWORD);
    $retval = $astman->PutDB('cidname', $num, $cidname);
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

?>
