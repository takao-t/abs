#!/bin/sh

SALT=`dd if=/dev/random bs=512 count=1|md5sum`
SECBASE="somewhere:$SALT:someone:$1"
ACCCODE=`echo $SECBASE | md5sum | cut -f1,1 -d' '`

#テンプレート(共通部分)
echo ";電話機用テンプレート(共通設定)"
echo "[phone](!)"
echo "type=friend"
echo "canreinvite=no"
echo "host=dynamic"
echo "dtmfmode=rfc2833"
echo "busylevel=1"
echo "callgroup=1"
echo "pickupgroup=1"
echo "videosupport=yes"
echo "disallow=all"
echo "allow=ulaw"
echo ";ACL"
echo "deny=0.0.0.0/0"
echo "permit=192.168.0.0/255.255.0.0"
echo ""
echo "accountcode=$ACCCODE"
echo ""
echo ""

echo ";各電話機個別設定"
for i in 1 2 3 4 5 6 7 8 9 10 11 12 13 14 15 16 17 18 19 20 21 22 23 24 25 26 27 28 29 30 31 32
do
    echo "[phone$i](phone)"
    echo "username=phone$i"
    SALT=`date +%N`
    SECBASE="phone:$SALT:$i"
    SECRET=`echo $SECBASE | md5sum | cut -f1,1 -d' '`
    echo "secret=$SECRET"
    echo ""
done

echo ";ビデオドアホン用(H.264 GS向け)"
for i in 1 2
do
    echo "[doorphone$i](phone)"
    echo "username=phone$i"
    SALT=`date +%N`
    SECBASE="phone:$SALT:$i"
    SECRET=`echo $SECBASE | md5sum | cut -f1,1 -d' '`
    echo "secret=$SECRET"
    echo "context=from-door"
    echo "callerid=\"ドアホン$i\" <500$i>"
    echo "allow=h264"
    echo ""
done
