#!/bin/sh
#
# Convert like:
# sox abs-tcrmessage.wav -r 8000 -c 1 ja/abs-tcrmessage.wav gain -6

# 音声ファイルの一覧CSV
SRCF="./abs-sounds.csv"
# VPで生成した場合の名前(000-abs=ja-voice.wavの形になる)
SRCN="abs-ja-voice"
# 変換後の音声ファイルの置き場所
DIRS="ja ja/digits"

# 出力先のサブ・ディレクトリ作成
for i in $DIRS
do
    mkdir -p $i
done


# リスト生成
LIST=`cat $SRCF | cut -f1-2 -d','`

# 各エントリの取り出し
for ENT in $LIST
do
    NUM=`echo $ENT | cut -f1,1 -d','`
    LEN=${#NUM}
    if [ $LEN -eq 1 ];
    then
        NUM=00$NUM
    elif [ $LEN -eq 2 ];
    then
        NUM=0$NUM
    fi 
    FILE=`echo $ENT | cut -f2,2 -d','`
    FILE=$FILE.wav
    SRCV="$NUM-$SRCN.wav"
    echo $SRCV "->" $FILE
    sox $SRCV -r 8000 -c 1 $FILE gain -6
done
