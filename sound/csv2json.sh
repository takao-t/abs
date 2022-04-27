#!/bin/sh
#
# Convert CSV to each .json file.
#

BASEFILE="./abs-sounds-src.csv"
TEMPLATE="./template.json"

mkdir -p ja
mkdir -p ja/digits
mkdir -p ja/letters

while read i
do
    TLINE=`echo $i | nkf | sed  's/"//g'`
    FNAME=`echo $TLINE | cut -f2,2 -d','`
    TTEXT=`echo $TLINE | cut -f3,3 -d',' | sed 's/\r//g'`
    cat $TEMPLATE  | sed s/###TEXTHERE###/$TTEXT/ > ja/$FNAME.json
    echo $FNAME

done < $BASEFILE
