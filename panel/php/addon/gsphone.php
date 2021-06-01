<?php

class GsPhone
{

  const VENDOR = 'Grandstream';

  const TITLE = [
    'マスターファイル',
    '端末(ピア)情報',
    'GXP2130',
    'GXP2135'];

  const DESC = [
    '各機種共通マスターファイルの生成',
    '各電話機のピア情報と内線情報ファイルの生成',
    'GXP2130用キー情報生成',
    'GXP2135用キー情報生成'];

  const FILE = [
    'addon/prov-gs-master',
    'addon/prov-gs-peer',
    'addon/prov-gs-gxp2130',
    'addon/prov-gs-gxp2135'];
}

?>
