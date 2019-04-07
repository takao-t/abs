#!/bin/sh
#
#
# モーニングコール実行用スクリプト
# /var/lib/asterisk/scriptsの下に置くこと
#
# crontabへの登録が必要(毎分実行)
# 0-59 * * * * /var/lib/asterisk/scripts/wakeup_do.sh > /dev/null 2>&1
#


PATH1="/var/spool/asterisk"
PATH2="wakeup"
OUT=$PATH1/outgoing

TIME=`date +%H%M`

cd $PATH1/$PATH2

LIST=`ls $TIME.*  2> /dev/null`

if [ "$LIST" != "" ]
then
    for i in $LIST
    do
        mv $i $OUT
    done
fi
