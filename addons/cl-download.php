<?php
include '../config.php';

@session_start();
if(isset($_SESSION['absp_session'])){
    if($_SESSION['absp_session'] == 'logged_in'){
        if(isset($_GET['file'])){
            $target_file = $_GET['file'];
        }
    } else {
        exit;
    }
} else {
    exit;
}


$f_path = LOGDIR . '/' . $target_file;
header('Content-Type: application/octet-stream');
header('Content-Length: '.filesize($f_path));
header('Content-disposition: attachment; filename="'.$target_file.'"');
readfile($f_path);

?>
