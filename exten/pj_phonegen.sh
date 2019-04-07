#!/bin/sh

SALT=`dd if=/dev/random bs=512 count=1|md5sum`
SECBASE="somewhere:$SALT:someone:$1"
ACCCODE=`echo $SECBASE | md5sum | cut -f1,1 -d' '`

#テンプレート(共通部分)
echo ";電話機用テンプレート(共通設定)"
echo "[phone-defaults](!)"
echo "type=wizard"
echo "transport = transport-udp"
echo "accepts_registrations = yes"
echo "sends_registrations = no"
echo "accepts_auth = yes"
echo "sends_auth = no"
echo "endpoint/context = default"
echo "endpoint/dtmf_mode = rfc4733"
echo "endpoint/call_group = 1"
echo "endpoint/pickup_group = 1"
echo "endpoint/language = ja"
echo "endpoint/disallow = all"
echo "endpoint/allow = ulaw"
echo "endpoint/rtp_symmetric = yes"
echo "endpoint/force_rport = yes"
echo "endpoint/direct_media = no"
echo "endpoint/send_pai = yes"
echo "endpoint/send_rpid = yes"
echo "endpoint/rewrite_contact = yes"
echo "endpoint/inband_progress = yes"
echo "endpoint/allow_subscribe = yes"
echo "aor/max_contacts = 2"
echo "aor/qualify_frequency = 30"
echo "aor/authenticate_qualify = no"
echo ""


echo ";各電話機個別設定"
for i in 1 2 3 4 5 6 7 8 9 10 11 12 13 14 15 16 17 18 19 20 21 22 23 24 25 26 27 28 29 30 31 32
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
