#!/bin/sh

SALT=`dd if=/dev/random bs=512 count=1|md5sum`
SECBASE="somewhere:$SALT:someone:$1"
ACCCODE=`echo $SECBASE | md5sum | cut -f1,1 -d' '`

echo ";各電話機個別設定"
for i in 33 34 35 36 37 38 39 40 41 42 43 44 45 46 47 48 49 50 51 52 53 54 55 56 57 58 59 60 61 62 63 64
do
    echo "[phone$i](phone-defaults)"
    echo "inbound_auth/username = phone$i"
    SALT=`date +%N`
    SECBASE="phone:$SALT:$i"
    SECRET=`echo $SECBASE | md5sum | cut -f1,1 -d' '`
    echo "inbound_auth/password = $SECRET"
    echo ""
done
#
#echo ";ビデオドアホン用(H.264 GS向け)"
#for i in 1 2
#do
#    echo "[doorphone$i](phone)"
#    echo "username=phone$i"
#    SALT=`date +%N`
#    SECBASE="phone:$SALT:$i"
#    SECRET=`echo $SECBASE | md5sum | cut -f1,1 -d' '`
#    echo "secret=$SECRET"
#    echo "context=from-door"
#    echo "callerid=\"ドアホン$i\" <500$i>"
#    echo "allow=h264"
#    echo ""
#done
