#!/bin/sh
cd /var/lib/asterisk/qpmnd

while :
do
    MYIPADDR=`ip add show eth0 |grep 'inet '|cut -f6,6 -d' '|cut -f1,1 -d'/'`
    if [ $MYIPADDR != "" ]
    then
        break
    fi
    sleep 60
done

python3 ./qpmnd.py
