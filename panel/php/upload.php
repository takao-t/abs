<?php
include 'config.php';

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

    $target_filename = $_FILES['upload_file']['name'];
    $upload_path = BACKUPDIR . '/' . $_FILES['upload_file']['name']; 

    if(preg_match('/astconf-/', $target_filename)){
        move_uploaded_file($_FILES['upload_file']['tmp_name'], $upload_path);
    }
    if(preg_match('/astdb-/', $target_filename)){
        move_uploaded_file($_FILES['upload_file']['tmp_name'], $upload_path);
    }
    if(preg_match('/extconf/', $target_filename)){
        move_uploaded_file($_FILES['upload_file']['tmp_name'], $upload_path);
    }


    $backto = $_SERVER['HTTP_REFERER'];
    http_response_code(30 );

    header("Location: $backto");
    exit; 
?>
