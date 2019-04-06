<h2>支援ツール</h2>

<?php
include 'toolsdef.php';

echo <<<EOT
<table border="0" class="pure-table">
  <tr>
    <thead>
      <th width="16em"></th>
      <th>概要</th>
    </thead>
  </tr>
EOT;

$menu_counts = count(ToolsMenu::NAME);

for($i=0;$i<$menu_counts;$i++){
    $name = ToolsMenu::NAME[$i];
    $desc = ToolsMenu::DESC[$i];
    $file = ToolsMenu::FILE[$i];
    $file = $file . '.php';

    if(file_exists("php/$file")){
echo <<<EOT
  <tr>
    <td>
      <a href="index.php?page=$file" style="width:100%;" class ="pure-button pure-button-active">
       $name
      </a>
    </td>
    <td>
      $desc
    </td>
  </tr>
EOT;
    }
}

echo '</table>';

?>
