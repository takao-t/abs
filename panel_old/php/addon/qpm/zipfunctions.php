<?php

namespace ZIPFUNC;

function zipconv($zip){

    if(extension_loaded('curl') === FALSE) return FALSE;

    $zip = trim($zip);
    if(ctype_digit($zip) === FALSE){
        return FALSE;
    }

    $CURL_TARGET = "https://www.post.japanpost.jp/kt/zip/e2.cgi?xr=1";
    $CURL_TARGET = $CURL_TARGET . '&z=' . $zip;

    $ch = curl_init($CURL_TARGET);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $res = curl_exec($ch);
    curl_close($ch);

    $res2 = mb_convert_encoding($res, "UTF-8", "SJIS");
    $res3 = explode("<BR>", $res2);


    if (strrpos($res3[0], "■検索結果") > 0){
        if(strpos($res3[2], "見つかりません") == FALSE){
            $ret = str_replace(' ', '', $res3[4]);
            return $ret;
        }
        return FALSE;
    }

    return FALSE;
}



?>
