#!/bin/sh
#
# 休日取得(内閣府が公開しているCSVを取得)
#  http://www8.cao.go.jp/chosei/shukujitsu/syukujitsu_kyujitsu.csv
# 変換用にnkfとdos2unixを使用しているので他のものを使う場合には
# コマンドを書き換えること

NKF="nkf -u"
D2U="dos2unix"
WGET="wget"
FILE="syukujitsu_kyujitsu.csv"
URL="http://www8.cao.go.jp/chosei/shukujitsu/syukujitsu_kyujitsu.csv"
TARGET="holidays.txt"

# Asterisk DB用
ASTDBFAM="HOLIDAYS/JAPAN"

# あったら削除しておく
rm -f $FILE

# 取得
$WGET $URL > /dev/null 2>&1

# UNIX形式に変換して漢字コードをUTFに
$D2U < $FILE | $NKF > $TARGET.tmp

# 見出し行削除
LNS=`wc -l $TARGET.tmp | cut -f1,1 -d' '`
LNS=`expr $LNS - 1`
tail -$LNS $TARGET.tmp > $TARGET

# 中間ファイル削除
rm -f $TARGET.tmp

# 既存のDBを削除
asterisk -rx "database deltree $ASTDBFAM"
sleep 1

cat $TARGET | while read i
do
    KEY=`echo $i | cut -f1,1 -d','`
    VAL=`echo $i | cut -f2,2 -d','`
    asterisk -rx "database put $ASTDBFAM $KEY $VAL"
    sleep 1
done
