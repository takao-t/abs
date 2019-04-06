<?php

class PanaPhone
{

  const VENDOR = 'パナソニック';

  const TITLE = [
    'マスターファイル',
    '端末(ピア)情報',
    'KX-UT136',
    'KX-HDV230/330/430' ];

  const DESC = [
    '各機種共通マスターファイルの生成',
    '各電話機のピア情報うと内線情報ファイルの生成',
    'KX-UT136用キー情報生成',
    'KX-HDV230/330/430用キー情報生成' ];

  const FILE = [
    'addon/prov-pana-master',
    'addon/prov-pana-peer',
    'addon/prov-kx-ut136',
    'addon/prov-kx-hdv330' ];  
}

?>
