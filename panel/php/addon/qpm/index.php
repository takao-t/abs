<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="A layout example with a side menu that hides on mobile, just like the Pure website.">
    <title>QPM-Quick Phone Memo</title>
        <link rel="stylesheet" href="css/pure-min.css">
        <!--[if lte IE 8]>
            <link rel="stylesheet" href="css/layouts/side-menu-old-ie.css">
        <![endif]-->
        <!--[if gt IE 8]><!-->
            <link rel="stylesheet" href="css/layouts/side-menu.css">
        <!--<![endif]-->

<style type="text/css">
<!--
p.main {
  background-color: #fefefe; border-style: solid; border-color: #f0f0f0;
  padding: 20px;
}
-->
</style>

</head>
<body>


<div>
    <style scoped>

        .absp-button1 {
            color: white;
            border-radius: 6px;
            font-size: 85%;
            text-shadow: 0 1px 1px rgba(0, 0, 0, 0.2);
            background: rgb(28, 184, 65);
        }

        .absp-button2 {
            color: white;
            border-radius: 6px;
            font-size: 85%;
            text-shadow: 0 1px 1px rgba(0, 0, 0, 0.2);
            background: rgb(28, 184, 180);
        }

        .absp-button3 {
            color: white;
            border-radius: 6px;
            font-size: 85%;
            text-shadow: 0 1px 1px rgba(0, 0, 0, 0.2);
            background: rgb(180, 96, 96);
        }

        .absp-button4 {
            color: white;
            border-radius: 6px;
            font-size: 85%;
            text-shadow: 0 1px 1px rgba(0, 0, 0, 0.2);
            background: rgb(240, 96, 96);
        }

    </style>
</div>

<?php
$msg = "";
$search_msg = "";
$search_msg2 = "";
$number_msg = "";
$zip_msg = '';
$r_msg = '';
$p_num = "";
$p_zip = "";
$p_cname = "";
$p_pname = "";
$p_ccat = "";
$p_ccatsel = "";
$p_addr = "";
$p_pn1 = "";
$p_pn1k = "";
$p_pn2 = "";
$p_pn2k = "";
$p_pn3 = "";
$p_pn3k = "";
$p_pn4 = "";
$p_pn4k = "";
$p_memo1 = "";
$p_memo2 = "";
$p_fpfx = "";
$p_last = "";
$p_attend = "";
$detail_sheet = '';
$p_upd_user = '';

$c2c_num = "";
$c2c_pn1 = "";
$c2c_pn2 = "";
$c2c_pn3 = "";
$c2c_pn4 = "";

include 'astman.php';
include 'zipfunctions.php';
include 'dbfunctions.php';

//ログインとセッション
ini_set('session.gc_maxlifetime', 28800);
ini_set('session.save_path', SESSIONPATH);
@session_start();
if(!isset($_SESSION['qpm_session']) | !isset($_SESSION['qpm_user'])){
    echo "<br><center>";
    echo "<a href=\"login.php\" class=\"pure-button pure-button-active\">ログインしてください</a>";
    echo "</center><br>";
    exit;
} else {
    $qpm_user = $_SESSION['qpm_user'];
}


// 番号種別のセレクタ生成
function get_s_kind_num($kind){

    if($kind == "") return(0);

    $s_kind = ['none','land','mobile','fax','ip'];
    $ret = 0;
    $cnt = 0;

    foreach($s_kind as $line){
        if($kind == $line) break;
        $cnt = $cnt +1;
    }

    if($cnt >= 5){
        $ret = 0;
    } else {
        $ret = $cnt;
    }

    return($ret);

} //get_s_kind_num


// 全角数字を半角にしハイフン系除去
function num_convert($num){

    $tmp_num = mb_convert_kana($num, "n");
    $tmp_num = str_replace('-', '', $tmp_num);
    $tmp_num = str_replace('ー', '',  $tmp_num);
    $tmp_num = str_replace('―', '',  $tmp_num);
    $tmp_num = str_replace('－', '',  $tmp_num);

    return $tmp_num;

} // num_convert


// シートの内容維持
function keep_items(){

    global $p_num, $p_cname, $p_ccat, $p_pname, $p_zip, $p_addr;
    global $p_pn1, $p_pn2, $p_pn3, $p_pn4;
    global $p_pn1k, $p_pn2k, $p_pn3k, $p_pn4k;
    global $p_memo1, $p_memo2;
    global $p_fpfx, $p_ccatsel;

    if(isset($_POST['num'])){
        $p_num = trim($_POST['num']);
        $p_num = num_convert($p_num);
    }

    if(isset($_POST['cname'])){
        $p_cname = trim($_POST['cname']);
    }

    if(isset($_POST['ccat'])){
        $p_ccat = trim($_POST['ccat']);
    }

    if(isset($_POST['ccatsel'])){
        $p_ccatsel = trim($_POST['ccatsel']);
    }

    if(isset($_POST['pname'])){
        $p_pname = trim($_POST['pname']);
    }

    if(isset($_POST['zip'])){
        $p_zip = trim($_POST['zip']);
        $p_zip = num_convert($p_zip);
    }

    if(isset($_POST['addr'])){
        $p_addr = trim($_POST['addr']);
    }

    if(isset($_POST['pn1'])){
        $p_pn1 = trim($_POST['pn1']);
        $p_pn1 = num_convert($p_pn1);
    }

    if(isset($_POST['pn1k'])){
        $p_pn1k = trim($_POST['pn1k']);
    }

    if(isset($_POST['pn2'])){
        $p_pn2 = trim($_POST['pn2']);
        $p_pn2 = num_convert($p_pn2);
    }
    if(isset($_POST['pn2k'])){
        $p_pn2k = trim($_POST['pn2k']);
    }

    if(isset($_POST['pn3'])){
        $p_pn3 = trim($_POST['pn3']);
        $p_pn3 = num_convert($p_pn3);
    }
    if(isset($_POST['pn3k'])){
        $p_pn3k = trim($_POST['pn3k']);
    }

    if(isset($_POST['pn4'])){
        $p_pn4 = trim($_POST['pn4']);
        $p_pn4 = num_convert($p_pn4);
    }
    if(isset($_POST['pn4k'])){
        $p_pn4k = trim($_POST['pn4k']);
    }

    if(isset($_POST['memo1'])){
        $p_memo1 = trim($_POST['memo1']);
    }

    if(isset($_POST['memo2'])){
        $p_memo2 = trim($_POST['memo2']);
    }

    if(isset($_POST['fpfx'])){
        $p_fpfx = trim($_POST['fpfx']);
        //$p_fpfx = num_convert($p_fpfx);
        $p_fpfx = mb_convert_kana($p_fpfx,'a');
    }

} //keep_items


// 番号から検索
function number_search($num){

    global $p_num, $p_cname, $p_ccat, $p_pname, $p_zip, $p_addr;
    global $p_pn1, $p_pn2, $p_pn3, $p_pn4;
    global $p_pn1k, $p_pn2k, $p_pn3k, $p_pn4k;
    global $p_memo1, $p_memo2;
    global $p_fpfx;
    global $p_attend, $p_last, $search_msg, $search_msg2;

    $search_msg = '';
    $search_msg2 = '';

    $ret = DBFUNC\num_exact_search($num);
    if($ret === FALSE || count($ret) == 0){
        $as_res = DBFUNC\get_astdb_item('cidname', $num);
        if($as_res != ""){
            $p_cname = $as_res;
            $p_ccat = '';
            $search_msg = '';
            $search_msg2 = ' (CIDNAME)';
            $p_zip = "";
            $p_pname = "";
            $p_addr = "";
            $p_pn1 = "";
            $p_pn1k = "";
            $p_pn2 = "";
            $p_pn2k = "";
            $p_pn3 = "";
            $p_pn3k = "";
            $p_pn4 = "";
            $p_pn4k = "";
            $p_memo1 = "";
            $p_memo2 = "";
            $p_fpfx = "";
            $p_last = "";
            $p_attend = "";
            $detail_sheet = '';
        } else {
            $search_msg = '該当なし';
            $p_zip = "";
            $p_cname = "";
            $p_ccat = "";
            $p_pname = "";
            $p_addr = "";
            $p_pn1 = "";
            $p_pn1k = "";
            $p_pn2 = "";
            $p_pn2k = "";
            $p_pn3 = "";
            $p_pn3k = "";
            $p_pn4 = "";
            $p_pn4k = "";
            $p_memo1 = "";
            $p_memo2 = "";
            $p_fpfx = "";
            $p_last = "";
            $p_attend = "";
            $detail_sheet = '';
        }
    } else {
        if(isset($ret['num'])) $p_num =  $ret['num'];
        if(isset($ret['cname'])) $p_cname =  $ret['cname'];
        if(isset($ret['cat'])) $p_ccat =  $ret['cat'];
        if(isset($ret['pname'])) $p_pname =  $ret['pname'];
        if(isset($ret['zip'])) $p_zip =  $ret['zip'];
        if(isset($ret['addr'])) $p_addr =  $ret['addr'];
        if(isset($ret['memo1'])) $p_memo1 =  $ret['memo1'];
        if(isset($ret['memo2'])) $p_memo2 =  $ret['memo2'];
        if(isset($ret['fpfx'])) $p_fpfx =  $ret['fpfx'];
        if(isset($ret['attend'])) $p_attend =  $ret['attend'];
        if(isset($ret['last'])) $p_last =  $ret['last'];
        if(isset($ret['pn1'])){
            $tmp_pn =  $ret['pn1'];
            list($p_pn1k, $p_pn1) = explode(":", $tmp_pn);
        }
        if(isset($ret['pn2'])){
            $tmp_pn =  $ret['pn2'];
            list($p_pn2k, $p_pn2) = explode(":", $tmp_pn);
        }
        if(isset($ret['pn3'])){
            $tmp_pn =  $ret['pn3'];
            list($p_pn3k, $p_pn3) = explode(":", $tmp_pn);
        }
        if(isset($ret['pn4'])){
             $tmp_pn =  $ret['pn4'];
             list($p_pn4k, $p_pn4) = explode(":", $tmp_pn);
        }
    }

}// number_search


// 詳細検索
function detail_search(){

    global $p_num, $p_cname, $p_ccat, $p_pname, $p_zip, $p_addr;
    global $p_pn1, $p_pn2, $p_pn3, $p_pn4;
    global $p_pn1k, $p_pn2k, $p_pn3k, $p_pn4k;
    global $p_memo1, $p_memo2;
    global $p_fpfx, $p_ccatsel;
    global $p_last, $p_attend, $search_msg, $search_msg2;

    $search_msg = '';
    $search_msg2 = '';

    //カテゴリはセレクタ優先
    if($p_ccatsel != "none"){
        $p_ccat = $p_ccatsel;
    }

    $ret_ar = DBFUNC\entry_detail_search($p_num,$p_cname,$p_ccat,$p_pname,$p_zip,$p_addr,$p_pn1,$p_pn2,$p_pn4,$p_pn4);

    return $ret_ar;

}// detail_search


// GETメソッドで番号から検索
// URL直での検索用
if($_SERVER['REQUEST_METHOD'] === 'GET'){
    if(isset($_GET['method'])){
        if($_GET['method'] == "q"){ //単純な番号着信時処理
            if(isset($_GET['num'])){
                $p_num = $_GET['num'];
                number_search($p_num);
             }
        } else if($_GET['method'] == "abs"){ //ABSのプレフィクス除去処理
            $p_num = $_GET['num'];
            $add_prefix = DBFUNC\get_astdb_item('ABS', 'APF');
            if($add_prefix == "1"){ //プレフィクスが付加されているはず
                if(strpos($p_num, "*56") === 0){ //先頭が*56で始まるのはキー捕捉
                    $p_num = substr($p_num, 4);  //頭4桁がプレフィクス 
                } else { //そうでなければ単純プレフィクス1桁
                    $p_num = substr($p_num, 1);
                }
            }
            number_search($p_num);
        }
    }
} //END GET


function build_detail_sheet($arr){

    global $detail_sheet;

    $tr_odd_class = '';
    $tr_count = 0;

    $detail_sheet = '<TABLE class="pure-table">';
    $detail_sheet .= '<TR>';
    $detail_sheet .= '<THEAD>';
    $detail_sheet .= '<TH nowrap>番号</TH><TH nowrap>社名/氏名</TH><TH nowrap>担当者</TH><TH nowrap>郵便番号</TH><TH nowrap>住所</TH><TH nowrap>他番号1</TH><TH nowrap>他番号2</TH><TH nowrap>他番号3</TH><TH nowrap>他番号4</TH>';
    $detail_sheet .= '</THEAD>';
    $detail_sheet .= '</TR>';

    foreach($arr as $ent){
        if($tr_count % 2 == 1) $tr_odd_class = 'class=pure-table-odd';
        else $tr_odd_class ='';
        $tr_count += 1;

        $detail_sheet .= "<TR $tr_odd_class>";
        $detail_sheet .= '<TD nowrap>';
        $detail_sheet .= '<A HREF="';
        $detail_sheet .= MYSELF;
        $detail_sheet .= '?method=q&num=';
        $detail_sheet .= $ent['num'];
        $detail_sheet .= '">';
        $detail_sheet .= $ent['num'];
        $detail_sheet .= '</A>';
        $detail_sheet .= '</TD>';
        $detail_sheet .= '<TD nowrap>';
        $detail_sheet .= $ent['cname'];
        $detail_sheet .= '</TD>';
        $detail_sheet .= '<TD nowrap>';
        $detail_sheet .= $ent['pname'];
        $detail_sheet .= '</TD>';
        $detail_sheet .= '<TD nowrap>';
        $detail_sheet .= $ent['zip'];
        $detail_sheet .= '</TD>';
        $detail_sheet .= '<TD nowrap>';
        $detail_sheet .= $ent['addr'];
        $detail_sheet .= '</TD>';
        $detail_sheet .= '<TD nowrap>';
        $detail_sheet .= explode(':', $ent['pn1'])[1];
        $detail_sheet .= '</TD>';
        $detail_sheet .= '<TD nowrap>';
        $detail_sheet .= explode(':', $ent['pn2'])[1];
        $detail_sheet .= '</TD>';
        $detail_sheet .= '<TD nowrap>';
        $detail_sheet .= explode(':', $ent['pn3'])[1];
        $detail_sheet .= '</TD>';
        $detail_sheet .= '<TD nowrap>';
        $detail_sheet .= explode(':', $ent['pn4'])[1];
        $detail_sheet .= '</TD>';
        $detail_sheet .= '</TR>';
        $detail_sheet .= "\n";
    }

    $detail_sheet .= '</TABLE>';
    $detail_sheet .= '<BR>';
}

//クリックコール処理
function click2call($target='',$force_pfx=''){

    if($target == "") return FALSE;
    if(isset($_POST['myext'])){
        $c2_myext = trim($_POST['myext']);
    }
    if($c2_myext == "") return FALSE;

    if($force_pfx != "") $c2_pfx = $force_pfx;
    else $c2_pfx = DBFUNC\get_astdb_item('ABS', 'QPMC2CPFX');

    $target = $c2_pfx . $target;
    $ast_c2_command = 'channel originate Local/' . $c2_myext . '@c2c-inside extension ' . $target . '@c2c-outside'; 
    DBFUNC\exec_cli_command($ast_c2_command);
}


// シート上からのPOSTでの処理
if($_SERVER['REQUEST_METHOD'] === 'POST'){

    if(isset($_POST['zipsearch'])){
        keep_items();
        if(isset($_POST['zip'])){
            $p_zip = trim($_POST['zip']);
            $p_zip = num_convert($p_zip);
            $p_ret = ZIPFUNC\zipconv($p_zip);
            if($p_ret != FALSE){
                $zip_msg = '';
                $p_addr = $p_ret;
            } else {
                $zip_msg = '該当なしまたは入力誤り';
            }
        }
    }

    //登録処理
    if(isset($_POST['register'])){
        keep_items();
        $tmp_pn1 = $p_pn1k . ":" . $p_pn1;
        $tmp_pn2 = $p_pn2k . ":" . $p_pn2;
        $tmp_pn3 = $p_pn3k . ":" . $p_pn3;
        $tmp_pn4 = $p_pn4k . ":" . $p_pn4;
        $last_date = date("Y/m/d(D) H:i");

        if($p_num == "" | $p_cname == ""){
            $r_msg = "番号と社名/氏名は最低限必要です";
            $number_msg = "！";
            $search_msg2 = "<font color=red>！</font>";
        } else {
            if(isset($_POST['toastdb'])){
                if($_POST['toastdb'] == "on"){
                    DBFUNC\put_astdb_cidname($p_num, $p_cname);
                }
            }

            //カテゴリ処理:セレクタ側優先
            if($p_ccatsel != "none"){
                $p_tcat = $p_ccatsel;
            } else if($p_ccat != ""){
                $p_tcat = $p_ccat;
                //手入力カテゴリがあれば更新処理
                if($p_ccat != "") DBFUNC\update_ccat($p_ccat);
            } else {
                $p_tcat = "";
            }
            //シート表示のボックスのため
            $p_ccat = $p_tcat;

            // 追加または更新実行
            DBFUNC\update_db($p_num, $p_cname, $p_tcat, $p_pname, $p_zip, $p_addr,
                $tmp_pn1, $tmp_pn2, $tmp_pn3, $tmp_pn4, $p_memo1, $p_memo2, $p_fpfx, $qpm_user, $last_date );
            $p_last = 'いま';
        }

    }

    //検索処理(シート上の指定パターン)
    if(isset($_POST['search'])){
        if(isset($_POST['searchmode'])){
            $search_msg = '';
            keep_items();
            $p_search_m = trim($_POST['searchmode']);
            if($p_search_m == "quick"){
                number_search($p_num);
            } else if($p_search_m == "detail"){
                $ret = detail_search();
                if(count($ret) > 0){ //結果がゼロなら何もしない
                    if(count($ret) == 1){ //結果数が1なら番号検索で表示させるだけ
                        number_search($ret[0]['num']);
                     
                    } else if(count($ret) > 100){
                        $search_msg = '検索結果が多すぎます';
                    } else {
                        build_detail_sheet($ret);
                    }
                } else {
                    $search_msg = '該当なし';
                }
            }
        }
    }

    //クイックサーチ
    if(isset($_POST['quicksearch'])){
        $search_msg = '';
        keep_items();
        number_search($p_num);
    }

    //詳細サーチ
    if(isset($_POST['detailsearch'])){
        $search_msg = '';
        keep_items();
        $ret = detail_search();
        if(count($ret) > 0){ //結果がゼロなら何もしない
            if(count($ret) == 1){ //結果数が1なら番号検索で表示させるだけ
                number_search($ret[0]['num']);
            } else if(count($ret) > 100){
                $search_msg = '検索結果が多すぎます';
            } else {
                build_detail_sheet($ret);
            }
        } else {
           $search_msg = '該当なし';
        }
     }


    //削除処理
    if(isset($_POST['delete'])){
        keep_items();
        if(isset($_POST['delcheck'])){
            if($_POST['delcheck'] == "on"){
                if($p_num == ""){
                    $number_msg = "削除には番号を指定してください";
                } else {
                    $ret = DBFUNC\num_exact_search($p_num);
                    if(ret === FALSE || count($ret) == 0){
                        $number_msg = "削除対象なし"; 
                    } else {
                        DBFUNC\delete_entry($p_num);
                        $number_msg = "削除済"; 
                    }
                }
            }
        }
    }

    //クリックコール処理
    if(isset($_POST['c2cnum'])){
        keep_items();
        if(isset($_POST['num'])){
            $tmp_c2cnum = trim($_POST['num']);
        }
        click2call($tmp_c2cnum,$p_fpfx);
    }
    if(isset($_POST['c2cpn1'])){
        keep_items();
        if(isset($_POST['pn1'])){
            $tmp_c2cnum = trim($_POST['pn1']);
        }
        click2call($tmp_c2cnum,$p_fpfx);
    }
    if(isset($_POST['c2cpn2'])){
        keep_items();
        if(isset($_POST['pn2'])){
            $tmp_c2cnum = trim($_POST['pn2']);
        }
        click2call($tmp_c2cnum,$p_fpfx);
    }
    if(isset($_POST['c2cpn3'])){
        keep_items();
        if(isset($_POST['pn3'])){
            $tmp_c2cnum = trim($_POST['pn3']);
        }
        click2call($tmp_c2cnum,$p_fpfx);
    }
    if(isset($_POST['c2cpn4'])){
        keep_items();
        if(isset($_POST['pn4'])){
            $tmp_c2cnum = trim($_POST['pn4']);
        }
        click2call($tmp_c2cnum,$p_fpfx);
    }
    //クリックコール処理ここまで
    

    //シートクリア処理
    if(isset($_POST['allclear'])){
    }


} //END POST

?>


<!--シートメイン-->

<?php

$tmp_pnk = get_s_kind_num($p_pn1k);
$p_pn1k_s = ['','','','','',''];
$p_pn1k_s[$tmp_pnk] = "selected"; 

$tmp_pnk = get_s_kind_num($p_pn2k);
$p_pn2k_s = ['','','','','',''];
$p_pn2k_s[$tmp_pnk] = "selected"; 

$tmp_pnk = get_s_kind_num($p_pn3k);
$p_pn3k_s = ['','','','','',''];
$p_pn3k_s[$tmp_pnk] = "selected"; 

$tmp_pnk = get_s_kind_num($p_pn4k);
$p_pn4k_s = ['','','','','',''];
$p_pn4k_s[$tmp_pnk] = "selected"; 

//ユーザを表示名に
$qpm_user_name = DBFUNC\u_login_name($qpm_user);
//内線番号を取得
$qpm_u_ext = DBFUNC\u_login_ext($qpm_user);

//
if(isset($p_attend)){
    $p_upd_user = DBFUNC\u_login_name($p_attend);
}

//デフォルトプレフィクス取得
$default_prefix = DBFUNC\get_astdb_item('ABS', 'QPMC2CPFX');
if($default_prefix == "") $default_prefix = 'なし';

//クリックコール情報生成
$qpmc2c = DBFUNC\get_astdb_item('ABS', 'QPMC2C');
if($qpmc2c == '1' & $qpm_u_ext != ""){


    if($p_num != "") $c2c_num = ' <input type="submit" name="c2cnum" class="absp-button4" value="発信">';
    else $c2c_num = '';

    if($p_pn1 != "" & $p_pn1k != "fax") $c2c_pn1 = ' <input type="submit" name="c2cpn1" class="absp-button4" value="発信">';
    else $c2c_pn1 = '';

    if($p_pn2 != "" & $p_pn2k != "fax") $c2c_pn2 =  ' <input type="submit" name="c2cpn2" class="absp-button4" value="発信">'; 
    else $c2c_pn2 = '';

    if($p_pn3 != "" & $p_pn3k != "fax") $c2c_pn3 =  ' <input type="submit" name="c2cpn3" class="absp-button4" value="発信">';
    else $c2c_pn3 = '';

    if($p_pn4 != "" & $p_pn4k != "fax") $c2c_pn4 =  ' <input type="submit" name="c2cpn4" class="absp-button4" value="発信">';
    else $c2c_pn4 = '';
}

//QPMNのURL生成
$qpmn_url = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
if(strpos($qpmn_url,'index.php') !== false){
    list($qpmn_url,$dummy) = explode('index.php',$qpmn_url,2);
}
$qpmn_url = $qpmn_url . 'qpmn.php';

//カテゴリプルダウン生成
$cat_pulldown = "";
$cat_arr = DBFUNC\get_ccat();
if($cat_arr != NULL){
  foreach($cat_arr as $ccat){
    $cat_pulldown .= '<option value="' . $ccat['cat'] . '">' . $ccat['cat'] . "</option>";
  }
}

echo <<<EOM
<p class="main">
<!-- detailed sheet will here -->
$detail_sheet

<table border=1 class="pure-table">
<form action="" method="POST">

<tr class="pure-table-odd">
  <td></td>
  <td align="right">
    <font color="red">
      $r_msg
    </font>
    <font color="red">
      $search_msg
    </font>
    最終更新 : $p_last [$p_upd_user]
  </td>
  <td>
    <input type="submit" name="allclear" class="absp-button2" value="シートクリア">
  </td>
</tr>

<tr>
  <td>
    番号<font color="red">*</font>
  </td>
  <td>
    <input type="text" size="12" name="num" value=$p_num>$c2c_num
    <font color="red">
      $number_msg
    </font>
  </td>
  <td>
    <input type="submit" name="quicksearch" class="absp-button2" value="番号検索">
  </td>
</tr>

<tr>
  <td>
    社名/氏名<font color="red">*</font>
  </td>
  <td>
    <input type="text" size="16" name="cname" value=$p_cname>
    $search_msg2
  </td>
  <td>
    <input type="submit" name="detailsearch" class="absp-button2" value="詳細検索">
  </td>
</tr>

<tr>
  <td>
    業種
  </td>
  <td>
    <input type="text" size="8" name="ccat" value=$p_ccat>
    <select name="ccatsel">
      <option value="none"> </option>
      $cat_pulldown
    </select>
  </td>
  <td></td>
</tr>

<tr>
  <td>
    担当者
  </td>
  <td>
    <input type="text" size="16" name="pname" value=$p_pname>
  </td>
  <td>
  </td>
</tr>

<tr>
  <td>
    〒
  </td>
  <td>
    <input type="text" size="8" name="zip" value=$p_zip>
    <input type="submit" class="absp-button2" name="zipsearch" value="住所検索">
    <font color="red">
      $zip_msg
    </font>
  </td>
  <td>
  </td>
</tr>

<tr>
  <td>
    住所
  </td>
  <td>
    <input type="text" size="48" name="addr" value=$p_addr>
  </td>
  <td>
  </td>
</tr>

<tr>
  <td>
    他番号1
  </td>
  <td>
    <select name="pn1k">
      <option value="none" $p_pn1k_s[0]></option>
      <option value="land" $p_pn1k_s[1]>固定</option>
      <option value="mobile"  $p_pn1k_s[2]>携帯</option>
      <option value="fax" $p_pn1k_s[3]>FAX</option>
      <option value="ip" $p_pn1k_s[4]>IP</option>
    </select>
    <input type="text" size="12" name="pn1" value=$p_pn1>$c2c_pn1
  </td>
  <td>
  </td>
</tr>

<tr>
  <td>
    他番号2
  </td>
  <td>
    <select name="pn2k">
      <option value="none" $p_pn2k_s[0]></option>
      <option value="land" $p_pn2k_s[1]>固定</option>
      <option value="mobile" $p_pn2k_s[2]>携帯</option>
      <option value="fax" $p_pn2k_s[3]>FAX</option>
      <option value="ip" $p_pn2k_s[4]>IP</option>
    </select>
    <input type="text" size="12" name="pn2" value=$p_pn2>$c2c_pn2
  </td>
  <td>
  </td>
</tr>

<tr>
  <td>
    他番号3
  </td>
  <td>
    <select name="pn3k">
      <option value="none" $p_pn3k_s[0]></option>
      <option value="land" $p_pn3k_s[1]>固定</option>
      <option value="mobile" $p_pn3k_s[2]>携帯</option>
      <option value="fax" $p_pn3k_s[3]>FAX</option>
      <option value="ip" $p_pn3k_s[4]>IP</option>
    </select>
    <input type="text" size="12" name="pn3" value=$p_pn3>$c2c_pn3
  </td>
  <td>
  </td>
</tr>

<tr>
  <td>
    他番号4
  </td>
  <td>
    <select name="pn4k">
      <option value="none" $p_pn4k_s[0]></option>
      <option value="land" $p_pn4k_s[1]>固定</option>
      <option value="mobile" $p_pn4k_s[2]>携帯</option>
      <option value="fax" $p_pn4k_s[3]>FAX</option>
      <option value="ip" $p_pn4k_s[4]>IP</option>
    </select>
    <input type="text" size="12" name="pn4" value=$p_pn4>$c2c_pn4
  </td>
  <td>
  </td>
</tr>

<tr>
  <td valign="top">
    メモ1
  </td>
  <td>
    <textarea name="memo1" cols="48" rows="4">$p_memo1</textarea>
  </td>
  <td>
  </td>
</tr>

<tr>
  <td valign="top">
    メモ2
  </td>
  <td>
    <textarea name="memo2" cols="48" rows="4">$p_memo2</textarea>
  </td>
  <td>
  </td>
</tr>

<tr>
  <td>
    プレフィクス
  </td>
  <td>
    <input type="text" size="4" name="fpfx" value=$p_fpfx>&nbsp;
    (未指定時：$default_prefix) 
  </td>
  <td>
    発信時に使用
  </td> 
</tr>


<tr class="pure-table-odd">
  <td>$qpm_user_name($qpm_u_ext)</td>
    <input type="hidden" name="myext" value="$qpm_u_ext">
  </td>
  <td>
    <input type="submit" name="register" class="absp-button1" value="登録/更新">
    <input type="checkbox" name="toastdb">
    ABSのCIDNAMEへも反映(番号,社名/氏名のみ)
  </td>
  <td>
    削除確認
    <input type="checkbox" name="delcheck">
    <input type="submit" name="delete" class="absp-button3" value="削除実行">
  </td>
</tr>

</form>
</table>

<p style="text-align: left">
<FONT SIZE="-1">
<A HREF="$qpmn_url" target="_blank">通知ページ</A>
</FONT>
</p>
<p style="text-align: right">
<FONT SIZE="-1">
<A HREF="./logout.php">ログアウト</A>
</FONT>
</p>

</body>
</html>
EOM;

?>
