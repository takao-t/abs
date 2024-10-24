<h2>電話機設定ファイル生成</h2>
<h3>端末用プロビジョニングファイル生成</h3>
注：シングルアカウントのみのファイルしか生成できません。同一電話機に複数のアカウントを設定したい場合には生成された個別の電話機用設定ファイル(端末情報)を手動で編集してください。<br>

<?php

// Grandstream
include 'gsphone.php';
$vdef = 'GsPhone';

$vendor = $vdef::VENDOR;

echo <<<EOT
<h3>$vendor</h3>
<table border=0 class="pure-table">
EOT;

$menu_counts = count(GsPhone::TITLE);

for($i=0;$i<$menu_counts;$i++){

    $title = $vdef::TITLE[$i];
    $desc  = $vdef::DESC[$i];
    $file  = $vdef::FILE[$i];

    if($i % 2 != 0){
        $tr_odd_class = '';
    } else {
        $tr_odd_class = 'class="pure-table-odd"';
    }

echo <<<EOT
  <tr $tr_odd_class>
    <td>
      <a href="index.php?page={$file}.php">
      $title
      </a>
    </td>
    <td>
      $desc
    </td>
  </tr>
EOT;
}
echo '</table>';
echo '<br>';

// Panasonic
include 'panadef.php';
$vdef = 'PanaPhone';

$vendor = $vdef::VENDOR;

echo <<<EOT
<h3>$vendor</h3>
<table border=0 class="pure-table">
EOT;

$menu_counts = count(PanaPhone::TITLE);

for($i=0;$i<$menu_counts;$i++){

    $title = $vdef::TITLE[$i];
    $desc  = $vdef::DESC[$i];
    $file  = $vdef::FILE[$i];

    if($i % 2 != 0){
        $tr_odd_class = '';
    } else {
        $tr_odd_class = 'class="pure-table-odd"';
    }

echo <<<EOT
  <tr $tr_odd_class>
    <td>
      <a href="index.php?page={$file}.php">
      $title
      </a>
    </td>
    <td>
      $desc
    </td>
  </tr>
EOT;
}

echo '</table>';
echo '<br>';
echo '端末(ピア)情報を生成する際に当該ベンダーの一覧に現れない場合には mac_vendor.php を編集してください。';
?>

