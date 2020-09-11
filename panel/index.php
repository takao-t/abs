<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="A layout example with a side menu that hides on mobile, just like the Pure website.">
    <title>ABS Panel</title>
        <link rel="stylesheet" href="css/pure-min.css">
        <!--[if lte IE 8]>
            <link rel="stylesheet" href="css/layouts/side-menu-old-ie.css">
        <![endif]-->
        <!--[if gt IE 8]><!-->
            <link rel="stylesheet" href="css/layouts/side-menu.css">
        <!--<![endif]-->
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

    </style>
</div>




<div id="layout">
    <!-- Menu toggle -->
    <a href="#menu" id="menuLink" class="menu-link">
        <!-- Hamburger icon -->
        <span></span>
    </a>

    <div id="menu">
        <div class="pure-menu">
            <a class="pure-menu-heading" href="#">ABS Panel</a>

            <ul class="pure-menu-list">
                <li class="pure-menu-item"><a href="index.php?page=ext-config-page.php" class="pure-menu-link">内線設定</a></li>
                <li class="pure-menu-item"><a href="index.php?page=group-config-page.php" class="pure-menu-link">内線グループ設定</a></li>
                <li class="pure-menu-item"><a href="index.php?page=keysys-config-page.php" class="pure-menu-link">キーシステム設定</a></li>
                <hr>
                <li class="pure-menu-item"><a href="index.php?page=outgoing-page.php" class="pure-menu-link">発信経路</a></li>
                <li class="pure-menu-item"><a href="index.php?page=ogr-config-page.php" class="pure-menu-link">発信設定</a></li>
                <hr>
                <li class="pure-menu-item"><a href="index.php?page=incoming-page.php" class="pure-menu-link">着信経路</a></li>
                <li class="pure-menu-item"><a href="index.php?page=did-config-page.php" class="pure-menu-link">ダイヤルイン設定</a></li>
                <li class="pure-menu-item"><a href="index.php?page=keyin-config-page.php" class="pure-menu-link">キー着信設定</a></li>
                <hr>
                <li class="pure-menu-item"><a href="index.php?page=tcs-config-page.php" class="pure-menu-link">時間外制御設定</a></li>
                <li class="pure-menu-item"><a href="index.php?page=vm-config-page.php" class="pure-menu-link">留守録設定</a></li>
                <li class="pure-menu-item"><a href="index.php?page=cid-config-page.php" class="pure-menu-link">発信者名管理</a></li>
                <li class="pure-menu-item"><a href="index.php?page=bl-config-page.php" class="pure-menu-link">着信拒否番号管理</a></li>
                <li class="pure-menu-item"><a href="index.php?page=system-config-page.php" class="pure-menu-link">システム設定</a></li>
                <li class="pure-menu-item"><a href="index.php?page=exec-cmd-page.php" class="pure-menu-link">コマンド実行</a></li>
                <li class="pure-menu-item"><a href="index.php?page=tools-page.php" class="pure-menu-link">ツール</a></li>
                <li class="pure-menu-item"><a href="logout.php" class="pure-menu-link">ログアウト</a></li>
            </ul>
        </div>
    </div>

    <div id="main">
        <div class="content">

<?php
include 'php/config.php';
include 'php/astman.php';
include 'php/functions.php';

@session_start();
if(isset($_SESSION['absp_session'])){
    if($_SESSION['absp_session'] == 'logged_in'){
        if(isset($_GET['page'])){
            $lickey = AbspFunctions\get_db_item('ABS', 'LIC');
            if($lickey == "" | $lickey = NULL){
                echo '<span style="background-color:#ff0000"><font color="white">ライセンス未設定</font></span>';
            }
            $target_page = $_GET['page'];
            include 'php/' . $target_page;
        } else {
            include 'php/blank_page.php';
        }
        if(isset($_GET['help'])){
            $target_page = $_GET['help'];
            include 'help/' . $target_page;
        }
    }
} else {
    echo "<br>";
    echo "<a href=\"login.php\" class=\"pure-button pure-button-active\">ログインしてください</a>";
}

?>
        </div>
    </div>
</div>

<script src="js/ui.js"></script>

</body>
</html>
