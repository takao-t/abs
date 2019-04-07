#!/bin/sh
#
# ARG1 channel
#
# モーニングコールキャンセル用スクリプト
# /var/lib/asterisk/scriptsの下に置くこと
#

PATH1="/var/spool/asterisk"
PATH2="wakeup"

CHAN=$1

cd $PATH1/$PATH2
#Remove before create new
rm -f *.$CHAN.mcall

