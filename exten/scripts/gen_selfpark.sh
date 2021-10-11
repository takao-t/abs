#!/bin/sh
PARKBASE=7000
PHONEPFX=phone
MAXPHONE=32

CNT=1

while :
do
    PEXT=`expr $CNT \* 2 + $PARKBASE`
    PPOS=`expr $PEXT + 1`

    echo "[$PHONEPFX$CNT]"
    echo "parkext => $PEXT"
    echo "parkpos => $PPOS-$PPOS"
    echo "context = selfpark"
    echo "parkinghints = yes"
    echo "comebacktoorigin = no"
    echo "comebackcontext = selfptimedout"
    echo ""

    CNT=`expr $CNT + 1`
    if [ $CNT -gt $MAXPHONE ]
    then
        break
    fi
done 
