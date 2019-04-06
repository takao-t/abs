#!/bin/sh
SOX="sox"
SRC="/var/spool/asterisk/recording"
FLIST="abs-tcmessage abs-tcrmessage"
TARGET="audio"

for i in $FLIST
do
  rm -f $i.mp3
done

for i in $FLIST
do
  $SOX $SRC/$i.wav $TARGET/$i.mp3
done
