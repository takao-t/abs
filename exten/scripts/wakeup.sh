#!/bin/sh
#
# ARG1 time
# ARG2 channel
#
# モーニングコール設定用スクリプト
# /var/lib/asterisk/scriptsの下に置くこと
# 注意：使用する前に /var/spool/asterisk/wakeup ディレクトリを作成し
# 適切なパーミッションを設定すること
#

PATH1="/var/spool/asterisk"
PATH2="wakeup"

TIME=$1
CHAN=$2

MCFILE=$PATH1/$PATH2/$TIME.$CHAN.mcall

cd $PATH1/$PATH2
#Remove before create new
rm -f *.$CHAN.mcall

echo "Channel: SIP/$CHAN" > $MCFILE
echo "CallerID: WAKEUP<*77$TIME>" >> $MCFILE
echo "MaxRetries: 3" >> $MCFILE
echo "RetryTime: 300" >> $MCFILE
echo "WaitTime: 120" >> $MCFILE
echo "Context: default" >> $MCFILE
echo "Extension: *MCALL" >> $MCFILE
echo "Priority: 1" >> $MCFILE

