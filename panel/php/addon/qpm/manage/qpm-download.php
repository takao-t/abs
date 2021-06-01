<?php
include '../config.php';
include 'qpm-config.php';

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

$bom = [];
$bom[0] = 0xef;
$bom[1] = 0xbb;
$bom[2] = 0xbf;


$f_path = CSVDIR . '/' . $target_file;
header('Content-Type: application/octet-stream');
header('Content-Length: '.filesize($f_path));
header('Content-disposition: attachment; filename="'.$target_file.'"');
echo pack('C', $bom[0]);
echo pack('C', $bom[1]);
echo pack('C', $bom[2]);
readfile($f_path);

?>
