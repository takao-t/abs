#!/bin/sh

CMD="asterisk -rx"
DBCMD="database put"


while read i 
do
    CHK=`echo $i | grep '^#'`
    if [ "$CHK" != "" ]
    then
         continue;
    fi
    CHK=`echo $i | grep '^;'`
    if [ "$CHK" != "" ]
    then
         continue;
    fi


    FAMILY=`echo $i | cut -f1,1 -d' '`
    KEY=`echo $i | cut -f2,2 -d' '`
    VALUE=`echo $i | cut -f3,3 -d' '`

    echo $FAMILY $KEY $VALUE
    $CMD "$DBCMD $FAMILY $KEY $VALUE"
    sleep 1
done
