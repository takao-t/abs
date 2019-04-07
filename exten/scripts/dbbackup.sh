#!/bin/sh

CMD="asterisk -rx"
DBCMD="database show"

#バックアップファイル名
BACKUPFILE="astdb.backup"

#中間ファイル
TMPFILE="astdbbackup.tmp"
MIDFILE="astdbbackup1.tmp"

#DBファイル名
FAMFILE="astdbbackup.fam"
KEYFILE="astdbbackup.key"
VALFILE="astdbbackup.val"

#取得するファミリの接頭辞
FAMILY="ABS KEYTEL cidname"

#除外する項目
#REJECT1=/KEYTEL/KEYSYS./ID
#REJECT2=/KEYTEL/KEYSYS./ORIGIN
#REJECT3=/KEYTEL/KEYSYS./PEER
REJECT1=/KEYTEL/KEYSYS[1-9]/ID
REJECT2=/KEYTEL/KEYSYS[1-9]/ORIGIN
REJECT3=/KEYTEL/KEYSYS[1-9]/PEER
REJECT11=/KEYTEL/KEYSYS1[0-9]/ID
REJECT12=/KEYTEL/KEYSYS1[0-9]/ORIGIN
REJECT13=/KEYTEL/KEYSYS1[0-9]/PEER
REJECT4=/ABS/TCSPEC

#asteriskからastdbの内容読出し
$CMD "$DBCMD" > $TMPFILE

#中間ファイルがあれば削除
rm -f $MIDFILE

#該当するファミリだけ抽出
for i in $FAMILY
do
    grep "^/$i" $TMPFILE | grep -v "$REJECT1" | grep -v "$REJECT2" | grep -v "$REJECT3" | grep -v "$REJECT4" | grep -v "$REJECT11" | grep -v "$REJECT12" | grep -v "REJETC13" >> $MIDFILE
done

#キー項目抽出
awk -F"/" '{print $NF}'  $MIDFILE | cut -f1,1 -d':' | sed 's/ //g' > $KEYFILE
#値抽出
awk -F"/" '{print $NF}'  $MIDFILE | cut -f2,2 -d':' | sed 's/^ //' > $VALFILE
#ファミリ抽出
awk -F"/" '{$NF="";print $0}'  $MIDFILE  | sed 's/ $//' | sed s'/^ //' | sed 's/ /\//g'> $FAMFILE

#バックアップファイルにヘッダ付加
DATE=`date`
echo "#" > $BACKUPFILE
echo "# ABS Database Backup : $DATE" >> $BACKUPFILE
echo "#" >> $BACKUPFILE

#要素を結合してバックアップファイル作成
paste -d' ' astdbbackup.fam astdbbackup.key astdbbackup.val >> $BACKUPFILE

#TCSPECだけ取得
TCS=`$CMD "database get ABS TCSPEC" | sed 's/Value\: //'`
echo "ABS TCSPEC $TCS" >> $BACKUPFILE
