<?php
include 'config.php';
include 'astman.php';

//セッション管理
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
    $qpm_user_name = $_SESSION['qpm_user_name'];
}

?>

<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>QPMN-Quick Phone Memo notifier</title>
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

body {
  margin:20px;
}
</style>

</head>

<body>

<script>

//行カウント用
var lcount = 0;

// WebSocketクライアント
var ws;
var myself = location.href;

function init(target) {

  // QPM用WebSocketサーバに接続
  ws = new WebSocket(target);

  // 各イベント時に状態表示させる
  ws.onopen = function() {
    msg_status("<font color=\"green\">接続中</font>");
    //初回接続時に履歴要求
    send_msg = "REQHISTORY:YES:DUMMY:DUMMY"
    ws.send(send_msg);
  };

  ws.onmessage = function(e) {
    msg_number(e.data);
  };

  ws.onclose = function() {
    msg_status("<font color=\"red\">切断</font>");
  };

  ws.onerror = function(e) {
    msg_status("<font color=\"red\">接続エラー</font>");
  };

}

//ウインドウ閉じた時にWSも閉じる
window.onclose = function() {
  ws.close();
}


//状態メッセージ表示
function msg_status(str) {
  var status = document.getElementById("status");
  status.innerHTML = str ;
}

//数値の先行するゼロ追加
function add_zero_to_digit(num){
  if(num < 10){
    num = "0" + num;
  }
  return num;
}


//番号表示とQPM検索へのリンク生成
function msg_number(str) {

  c_num = "";
  c_name = "";
  c_intime = "";
  c_log = "";


  tmp_str = str.replace(/<("[^"]*"|'[^']*'|[^'">])*>/g,'');
  incoming_message = tmp_str.split(':');
  a_count = incoming_message.length;

  // フィールド数が足りない場合は処理しない
  if(a_count < 4) return;

  // 着信時(番号)処理
  if(incoming_message[0] == 'INCOMING'){

    incoming_num = incoming_message[1];

    if(incoming_message[2] == null) incoming_name = "";
    else incoming_name = incoming_message[2];

    timestamp = incoming_message[3].replace(/-/g,':');

    var tmp_url_arr = myself.split('/');
    tmp_url_arr.pop();
    link_url =  tmp_url_arr.join('/');

    if(isNaN(incoming_num)){
      if(incoming_num == 'anonymous'){
        c_num = '<a href="' + link_url + '/index.php" target="_blank">' + '非通知' + '</a>';
      } else {
        c_num = incoming_num;
      }
    } else {
      c_num = '<a href="' + link_url + '/index.php\?method=q\&num=' + incoming_num + '" target="_blank">' + incoming_num + '</a>';
    }
    c_name = incoming_name;
    c_intime = timestamp;
    c_log = '';
  }

  // IM処理
  if(incoming_message[0] == 'INSTANTMSG'){
    incoming_user = incoming_message[1];
    incoming_msg = incoming_message[2];
    timestamp = incoming_message[4].replace(/-/g,':');
    //console.log(incoming_msg);
    //console.log(incoming_user);
    c_num = 'IM';
    c_name = incoming_user;
    if(c_name.match(/;/)){
        c_name_arr = c_name.split(';');
        //c_name = c_name_arr[0];
        c_extnum = c_name_arr[1];
        if(c_extnum != qpm_user_ext){
            c_name = '<form action="" method="post">'
                   + '<input type="submit" class="imext-button" name="c2cext" value="' +  c_name_arr[0] + '">'
                   + '<input type="hidden" name="extnum" value="' + c_name_arr[1] + '">' 
                   + '</form>';
        } else {
            c_name = c_name_arr[0];
        }
    }
    c_intime = timestamp;
    c_log = incoming_msg;
  }

  lcount += 1;
  if((lcount % 2) != 0) tb_bg ='#FFFFFF';
  else tb_bg = '#F4F4F4';

  //新規エントリを表に追加
  table = document.getElementById('histtable');
  n_row = table.insertRow(2);
  n_row.bgColor = tb_bg;
  cell1 = n_row.insertCell(0);
  cell2 = n_row.insertCell(1);
  cell3 = n_row.insertCell(2);
  cell4 = n_row.insertCell(3);

  cell1.innerHTML = c_num;
  cell2.innerHTML = c_name;
  cell3.innerHTML = c_intime;
  cell4.innerHTML = c_log;


}

</script>

<?php

// Asterisk コマンド実行
function exec_cli_command($param){

    if($param != ''){
        $astman = new DBFUNC\AstMan();
        $astman->Login('localhost', AMIUSERNAME, AMIPASSWORD);
        $retval = $astman->ExecCMD($param);
        $astman->Logout();
        return $retval;
    }

    return '';
}

//内線番号取得
$db = new \SQLite3(QPMDB);
$query = "SELECT ext  FROM qpm_users WHERE login='" . $qpm_user  . "'";
$res = $db->querySingle($query);
if($res != null  & $res != ""){
    $qpm_user_ext = $res;
} else {
    $qpm_user_ext = "";
}

//C2C内線発信(POST)
if(isset($_POST['c2cext'])){
    //echo $_POST['extnum'];
    $target_ext = trim($_POST['extnum']);
    //echo $qpm_user_ext;
    $ast_cmd = 'channel originate Local/' . $qpm_user_ext .'@c2c-inside extension ' . $target_ext . '@c2c-inhouse';
    //echo $ast_cmd;
    exec_cli_command($ast_cmd);
}

?>

<script>
  // PHP変数の取り込み(セッション関連)
  var qpm_user = <?php echo json_encode($qpm_user);?>;
  var qpm_user_name = <?php echo json_encode($qpm_user_name);?>;
  var qpm_user_ext = <?php echo json_encode($qpm_user_ext);?>;
  var qpmd_host = <?php echo json_encode(QPMDHOST);?>;
  var qpmd_port = <?php echo json_encode(QPMDPORT);?>;
  var qpmd_target = 'ws://' + qpmd_host + ':' + qpmd_port;

  //console.log(qpmd_target);
  init(qpmd_target);

  //表示テーブル部表中身
  var hist_tb_data = '<tr><thead><th> 番号 </th><th>CIDname</th><th>着信</th><th></th></thead></tr><tr> <td></td><td></td><td></td><td></td></tr>';

</script>

<!--HTML本体-->
<div>
    <style scoped>

	.imsend-button {
	  position: relative;
	  display: inline-block;
	  font-weight: bold;
	  padding: 0.25em 0.5em;
	  text-decoration: none;
	  color: #00BCD4;
	  background: #ECECEC;
	  transition: .4s;
	}

	.imsend-button:hover {
	  background: #00bcd4;
	  color: white;
	}

        .imext-button {
          position: relative;
          display: inline-block;
          font-weight: bold;
          text-decoration: none;
          color: #808080;
          background: #E0E0E0;
          height:30px;
          line-height:30px;
          text-align:center;
          display:inline-block;
          border-radius:5px;
          transition: .4s;
        }

        .imext-button:hover {
          background: #080808;
          color: white;
        }

    </style>
</div>

<br>
<!-- IM入力ボックス -->
<input type="text" size="32" name="imtext" id="imtext" onkeypress="enter_detect();">
<span id="imsendu">
<a href="#" class="imsend-button">
送信
</a>
<p id="text"></p>
</span>

<script>
//IM送信処理
function im_send_main(){
  send_text = document.getElementById("imtext").value;
  //console.log(qpm_user_name);
  if(qpm_user_name == null) im_user = qpm_user;
  if(qpm_user_name != "") im_user = qpm_user_name;
  else im_user = qpm_user;

  if(send_text != ""){
    if(qpm_user_ext != ""){
        send_msg = "INSTANTMSG:" + im_user + ";" + qpm_user_ext + ":" + send_text.replace(/:/, "") + ":" + qpm_user_ext;
    } else {
        send_msg = "INSTANTMSG:" + im_user + ":" + send_text.replace(/:/, "") + ":" + qpm_user_ext;
    }
    ws.send(send_msg);
    document.getElementById("imtext").value = '';
  }
}

//Enter入力で送信
function enter_detect(){
  if(window.event.keyCode == 13){
    im_send_main();
  }
}

//ボタンクリックで送信
document.getElementById("imsendu").onclick = function() {
  im_send_main();  
};


</script>

<br>
<table class="pure-table" border="1" id="histtable">
<script>
  //テーブルの中身は可変
  document.write(hist_tb_data);
</script>
</table>

<br>

<table class="pure-table" border="1" id="stattable">
  <tr>
    <td>
      <b><div id=status></div></b>
    </td>
    <td>
      <?php
        echo $qpm_user_name . '(' . $qpm_user_ext . ')'
      ?>
    </td>
    <td>
      <span id="pgreload">
        <a href="#" class="imsend-button">
          再接続
        </a>
      </span>
    </td>
  </tr>
</table>
<font size="-2">上から新しい着信順です</font><br>

<script>
//再接続
document.getElementById("pgreload").onclick = function() {
  ws.close();
  init(qpmd_target);
  //テーブル組みなおし
  ntable = document.getElementById('histtable');
  ntable.innerHTML = hist_tb_data;
};
</script>

</body>
</html>
