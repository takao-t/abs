<h2 id="sysconfig">時間外制御設定</h2>

<?php
$msg = "";

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    if($_POST['function'] == 'tccset'){ //時間外制御設定
        $p_tccval = $_POST['tccval'];
        if($p_tccval == '') $p_tccval = '0';
        AbspFunctions\put_db_item('ABS', 'TCC', $p_tccval);
    }

    if($_POST['function'] == 'tdisset'){ //ダイヤルイン時間外制御設定
        $p_tdiss = $_POST['tdiss'];
        if($p_tdiss == '') $p_tdiss = '1';
        AbspFunctions\put_db_item('ABS/DID', 'TCS', $p_tdiss);
    }

    if($_POST['function'] == 'thdisset'){ //ダイヤルイン時休日制御
        $p_thdiss = $_POST['thdiss'];
        if($p_thdiss == '') $p_thdiss = '1';
        AbspFunctions\put_db_item('ABS/DID', 'THS', $p_thdiss);
    }

    if($_POST['function'] == 'tcspecset'){ //時刻情報設定
        $p_twday = '';
        if(isset($_POST['sun'])) $p_twday = $_POST['sun'];
        if(isset($_POST['mon'])) $p_twday = $p_twday . '&' . $_POST['mon'];
        if(isset($_POST['tue'])) $p_twday = $p_twday . '&' . $_POST['tue'];
        if(isset($_POST['wed'])) $p_twday = $p_twday . '&' . $_POST['wed'];
        if(isset($_POST['thu'])) $p_twday = $p_twday . '&' . $_POST['thu'];
        if(isset($_POST['fri'])) $p_twday = $p_twday . '&' . $_POST['fri'];
        if(isset($_POST['sat'])) $p_twday = $p_twday . '&' . $_POST['sat'];
        $p_twday = ltrim($p_twday, '&');
 
        $p_tstart = $_POST['tstart'];
        $p_tend = $_POST['tend'];
        $p_tcspec = "$p_tstart-$p_tend,$p_twday,*,*";
        AbspFunctions\put_db_item('ABS', 'TCSPEC', $p_tcspec);
    }

    if($_POST['function'] == 'tchset'){ //祝日・休日制御
        $p_tchval = $_POST['tchval'];
        if($p_tchval == '') $p_tchval = '0';
        AbspFunctions\put_db_item('ABS', 'TCHC', $p_tchval);
    }

    if($_POST['function'] == 'tctset'){ //トグル切り替え設定
        $p_tctval = $_POST['tctval'];
        if($p_tctval == '') $p_tctval = '0';
        AbspFunctions\put_db_item('ABS', 'TCT', $p_tctval);
    }

    if($_POST['function'] == 'tcpinset'){ // PINセット
        $p_tcpin = $_POST['tcpin'];
        AbspFunctions\put_db_item('ABS', 'TCPIN', $p_tcpin);
    }

    if($_POST['function'] == 'wtiset'){ // 待機時間設定
        $p_wtival = $_POST['wtival'];
        AbspFunctions\put_db_item('ABS', 'WTI', $p_wtival);
    }

} // end of POST


//時間外制御

    $tcc_selected = array('0'=>'','1'=>'','2'=>'','3'=>'','4'=>'');
    $tcsval = AbspFunctions\get_db_item('ABS', 'TCC');
    $tcc_selected["$tcsval"] = "selected";

//時間外制御管理

echo <<<EOT
<h3 id="tcc">時間外制御</h3>
<table class="pure-table">
  <tr>
    <td>
      <form action="" method="POST">
        <input type="hidden" name="function" value="tccset">
        <select name="tccval">
          <option value="0" {$tcc_selected['0']}>時刻分岐しない</option>
          <option value="1" {$tcc_selected['1']}>時刻分岐あり。音声再生後切断。</option>
          <option value="2" {$tcc_selected['2']}>時刻分岐あり。要件録音あり。</option>
          <option value="3" {$tcc_selected['3']}>強制時間外設定。音声再生後切断。</option>
          <option value="4" {$tcc_selected['4']}>強制時間外設定。要件録音あり。</option>
        </select>
    </td>
    <td>
      <input type="submit" class={$_(ABSPBUTTON)} value="設定">
    </td>
  </tr>
      </form>
</table>
<br>
EOT;

//DID時スキップ

    $tdis_selected = array('0'=>'','1'=>'');
    $tdis = AbspFunctions\get_db_item('ABS/DID', 'TCS');
    $tdis_selected["$tdis"] = "selected";

echo <<<EOT
<h3 id="tdis">ダイヤルイン時制御</h3>
<table class="pure-table">
  <tr>
    <td>
      <form action="" method="POST">
      <input type="hidden" name="function" value="tdisset">
        <select name="tdiss">
          <option value="0" {$tdis_selected['0']}>ダイヤルイン時も時間外制御する</option>
          <option value="1" {$tdis_selected['1']}>ダイヤルイン時は時間外制御しない</option>
        </select>
    </td>
    <td>
      <input type="submit" class={$_(ABSPBUTTON)} value="設定">
    </td>
  </tr>
      </form>
</table>
<br>
EOT;

//時刻情報

    $swd_selected = array('sun'=>'','mon'=>'','tue'=>'','wed'=>'','thu'=>'','fri'=>'','sat'=>'');
    $ewd_selected = array('sun'=>'','mon'=>'','tue'=>'','wed'=>'','thu'=>'','fri'=>'','sat'=>'');
    $tcspec = AbspFunctions\get_db_item('ABS', 'TCSPEC');
    if($tcspec != ""){
      list($ttime, $twday, $dummy) = explode(',', $tcspec, 3);
      list($stime, $etime) = explode('-', $ttime, 2);
    } else {
      $etime = '';
      $stime = '';
      $twday = '';
    }

    $sunchecked=""; 
    $monchecked=""; 
    $tuechecked=""; 
    $wedchecked=""; 
    $thuchecked=""; 
    $frichecked=""; 
    $satchecked=""; 
    if(strpos($twday, 'sun') !== false) $sunchecked="checked"; 
    if(strpos($twday, 'mon') !== false) $monchecked="checked"; 
    if(strpos($twday, 'tue') !== false) $tuechecked="checked"; 
    if(strpos($twday, 'wed') !== false) $wedchecked="checked"; 
    if(strpos($twday, 'thu') !== false) $thuchecked="checked"; 
    if(strpos($twday, 'fri') !== false) $frichecked="checked"; 
    if(strpos($twday, 'sat') !== false) $satchecked="checked"; 

//時刻情報
echo <<<EOT
<h3 id="tcspec">着信可能時間</h3>
<form action="" method="POST">
<input type="hidden" name="function" value="tcspecset">
<table class="pure-table">
  <tr>
    <thead>
      <th>日曜</th><th>月曜</th><th>火曜</th><th>水曜</th><th>木曜</th><th>金曜</th><th>土曜</th>
    </thead>
  </tr>
  <tr>
    <td>
      <input type="checkbox" name="sun" value="sun" $sunchecked>
    </td>
    <td>
      <input type="checkbox" name="mon" value="mon" $monchecked>
    </td>
    <td>
      <input type="checkbox" name="tue" value="tue" $tuechecked>
    </td>
    <td>
      <input type="checkbox" name="wed" value="wed" $wedchecked>
    </td>
    <td>
      <input type="checkbox" name="thu" value="thu" $thuchecked>
    </td>
    <td>
      <input type="checkbox" name="fri" value="fri" $frichecked>
    </td>
    <td>
      <input type="checkbox" name="sat" value="sat" $satchecked>
    </td>
  </tr>
</table>
<table class="pure-table">
  <tr>
    <thead>
      <th>開始</th>
      <th></th>
      <th>終了</th>
      <th></th>
      <th></th>
    </thead>
  </tr>

  <tr>
    <td>
      <input type="txt" name="tstart" size="6" value="$stime">
    </td>
    <td>
      -
    </td>
    <td>
      <input type="txt" name="tend" size="6" value="$etime">
    </td>
    <td>
      <input type="submit" class={$_(ABSPBUTTON)} value="設定">
    </td>
    <td width="20">
    </td>
  </tr>
</table>
</form>
<br>
EOT;

//休日チェック

    $tch_selected = array('0'=>'','1'=>'','2'=>'');
    $tchval = AbspFunctions\get_db_item('ABS', 'TCHC');
    $tch_selected["$tchval"] = "selected";

echo <<<EOT
<h3 id="tchctl">祝日・休日制御</h3>
<table class="pure-table">
  <tr>
    <td>
      <form action="" method="POST">
        <input type="hidden" name="function" value="tchset">
        <select name="tchval">
          <option value="0" {$tch_selected['0']}>行わない</option>
          <option value="1" {$tch_selected['1']}>行う(音声再生後切断)</option>
          <option value="2" {$tch_selected['2']}>行う(留守録あり)</option>
        </select>
    </td>
    <td>
      <input type="submit" class={$_(ABSPBUTTON)} value="設定">
    </td>
    <td>
      <a href="index.php?page=holiday-config-page.php" class ="pure-button pure-button-active">
        祝日・休日管理
      </a>
    </td>
  </tr>
      </form>
</table>
<br>
EOT;

//DID時休日スキップ

    $thdis_selected = array('0'=>'','1'=>'');
    $thdis = AbspFunctions\get_db_item('ABS/DID', 'THS');
    $thdis_selected["$thdis"] = "selected";

echo <<<EOT
<h3 id="thdis">ダイヤルイン時休日制御</h3>
<table class="pure-table">
  <tr>
    <td>
      <form action="" method="POST">
      <input type="hidden" name="function" value="thdisset">
        <select name="thdiss">
          <option value="0" {$thdis_selected['0']}>ダイヤルイン時も休日制御する</option>
          <option value="1" {$thdis_selected['1']}>ダイヤルイン時は休日制御しない</option>
        </select>
    </td>
    <td>
      <input type="submit" class={$_(ABSPBUTTON)} value="設定">
    </td>
  </tr>
      </form>
</table>
<br>
EOT;

//特番トグル

    $tct_selected = array('0'=>'','1'=>'','2'=>'','3'=>'','4'=>'');
    $tctval = AbspFunctions\get_db_item('ABS', 'TCT');
    $tct_selected["$tctval"] = "selected";

echo <<<EOT
<h3 id="tcspec">特番ダイヤルによるトグル切替</h3>
<table class="pure-table">
  <tr>
    <td>
      <form action="" method="POST">
      <input type="hidden" name="function" value="tctset">
      <select name="tctval">
        <option value="0" {$tct_selected['0']}>使用しない</option>
        <option value="1" {$tct_selected['1']}>時刻分岐あり。音声再生後切断。</option>
        <option value="2" {$tct_selected['2']}>時刻分岐あり。要件録音あり。</option>
        <option value="3" {$tct_selected['3']}>強制時間外設定。音声再生後切断。</option>
        <option value="4" {$tct_selected['4']}>強制時間外設定。要件録音あり。</option>
      </select>
    </td>
    <td>
      <input type="submit" class={$_(ABSPBUTTON)} value="設定">
    </td>
  </tr>
      </form>
</table>
<br>
EOT;

    $tcpin = AbspFunctions\get_db_item('ABS', 'TCPIN');

echo <<<EOT
<h3 id="tcspec">時間制御切替用PIN</h3>
<table class="pure-table">
  <tr>
    <td>
      <form action="" method="POST">
      <input type="hidden" name="function" value="tcpinset">
      <input type="text" size="4" name="tcpin" value=$tcpin>
    </td>
    <td>
      <input type="submit" class={$_(ABSPBUTTON)} value="設定">
    </td>
  </tr>
      </form>
</table>
EOT;

    $wtiv = AbspFunctions\get_db_item('ABS', 'WTI');
    if($wtiv == '') $wtiv = "10";

echo <<<EOT
<h3 id="tcspec">自動応答前待機時間</h3>
<table class="pure-table">
  <tr>
    <td>
      <form action="" method="POST">
      <input type="hidden" name="function" value="wtiset">
      <input type="text" size="4" name="wtival" value=$wtiv>
    </td>
    <td>
      <input type="submit" class={$_(ABSPBUTTON)} value="設定">
    </td>
  </tr>
      </form>
</table>
EOT;
?>
