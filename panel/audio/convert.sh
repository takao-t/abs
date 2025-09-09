#!/bin/sh
SOX="sox"
SRC="/var/spool/asterisk/recording"
FLIST=$*
TARGET="audio"

if [ "$FLIST" != "" ];
then
  for i in $FLIST
  do
    rm -f $i.mp3
  done

  for i in $FLIST
  do
    $SOX $SRC/$i.gsm $TARGET/$i.mp3
  done
fi
