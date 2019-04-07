#!/bin/sh

CMD="asterisk -rx "
PHN="phone"
FAMIL="ABS/ERV"
DEFAULTTECH="PJSIP"
LFAMIL="ABS/LOCALTECH"

echo "[ext-hints]"
echo ";各電話機用ヒント生成"
for i in 1 2 3 4 5 6 7 8 9 10 11 12 13 14 15 16 17 18 19 20 21 22 23 24 25 26 27 28 29 30 31 32
do
    ENT=`$CMD "database get $FAMIL $PHN$i" | cut -f2,2 -d':'` 2> /dev/null
    LTECH=`$CMD "database get $LFAMIL $PHN$i" | cut -f2,2 -d':' | sed 's/ //g'` 2> /dev/null
    if [ "$ENT" != "Database entry not found." ]
    then
        if [ "$LTECH" = "PJSIP" ]
        then
	    TECH="PJSIP"
	else
	    TECH=$DEFAULTTECH
        fi
        echo "exten =>$ENT,hint,$TECH/phone$i"
    fi
done
