#!/bin/sh
# 自己署名証明書作成スクリプト

OPENSSL="openssl"
ABSKEY="abs.key"
ABSCSR="abs.csr"
ABSCRT="abs.crt"
SUBJECT="san.txt"

echo "情報を入力してください(英字)"

echo -n "国(例:JA) : "
read COUNTRY
echo -n "都道府県(例:TOKYO) : "
read PERF
echo -n "市区郡(例:CHIYODA) : "
read CITY
echo -n "サーバのIPアドレス(例:192.168.1.1) : "
read IPADD
echo

SUBOPT="/C=$COUNTRY/PN=$PERF/ST=$CITY/O=ABS/OU=PBX/CN=$IPADD"

echo "subjectAltName = DNS:$IPADD, IP:$IPADD"
echo $SUBOPT

echo "subjectAltName = DNS:$IPADD, IP:$IPADD" > $SUBJECT
echo $SUBOPT

$OPENSSL genrsa 2048 > $ABSKEY
$OPENSSL req -new -key abs.key -subj $SUBOPT > $ABSCSR
$OPENSSL x509 -days 3650 -req -extfile $SUBJECT -signkey $ABSKEY < $ABSCSR > $ABSCRT

echo
echo "-----"
echo

$OPENSSL x509 -in $ABSCRT -text -noout
