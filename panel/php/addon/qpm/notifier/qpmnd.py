# coding: utf-8
#
# QPM着信通知用WebSocketサーバ
# 単なるメッセージディスパッチャ
# WebsocketServer必要
# git clone https://github.com/Pithikos/python-websocket-server.git
# pyyhon3 setup.py install
#

import signal, os, sys, re
import datetime
from qpmnd_config import *

from websocket_server import WebsocketServer

# 履歴バッファ
history_size = MAXHISTORY
history_now = 0
history_buf = [""] * history_size

tag_eliminate = re.compile(r"<[^>]*?>")

# 履歴に追加
def history_append(msg):

    global history_now
    global history_buf

    history_now = history_now + 1
    if history_now >= history_size:
        history_now = 0
    history_buf[history_now] = msg

# 履歴の送信
def history_send(client):

    global history_now
    global history_buf

    send_buf = []

    history_tosend = history_now

    # 履歴を送信用配列へ
    for i in range(history_size):
        if history_buf[history_tosend] != "":
            send_buf.append(history_buf[history_tosend]) 
        history_tosend = history_tosend - 1
        if history_tosend < 0:
            history_tosend = history_size - 1

    tmp_pos = len(send_buf) - 1
    if(tmp_pos == 0):
        return

    # 最新が一番上にくるので古いものから送信
    for i in range(len(send_buf)):
        server.send_message(client, send_buf[tmp_pos])
        tmp_pos = tmp_pos - 1


# 新規クライアント接続
def new_client(client, server):
    print("新規クライアント ID= %d" % client['id'])


# クライアント切断時処理
def client_left(client, server):
    print("クライアント切断 ID= %d" % client['id'])


# クライアントからのメッセージ受信処理
def message_received(client, server, message):
    try:
        (condition,p_token,content1,content2) = message.split(':',3)
    except:
        print("Message format error!\n");
        return

    #print(condition)
    #print(p_token)
    #print(content1)
    #print(content2)

    # 受信タイムスタンプ
    dt_now = datetime.datetime.now()
    timestamp = dt_now.strftime('%Y/%m/%d(%a) %H-%M-%S')

    if condition == 'INCOMING': #メッセージタイプがINCOMINGなら着信通知
        if p_token == TOKEN:    #適当なものを投げられると面倒なのでtokenで判断
            #安全のため数字または非通知でなければ無視
            if content1.isdigit() or content1 == 'anonymous':
                content1 = tag_eliminate.sub("", content1)
                # 全クライアント(ブラウザ)にメッセージを送信
                content2send = 'INCOMING:' +  content1 + ':' + content2 + ':' + timestamp
                server.send_message_to_all(content2send)
                # ヒストリに追加
                history_append(content2send)
    if condition == 'INSTANTMSG': #メッセージタイプがIM
        # HTMLタグを除去
        content1 = tag_eliminate.sub("", content1)
        # IMの場合はTOKENの個所がユーザ名
        content2send = 'INSTANTMSG:' + p_token + ":" + content1 + ":" + content2 + ':' + timestamp
        #print("IM : " + content2send)
        server.send_message_to_all(content2send)
        # ヒストリに追加
        history_append(content2send)
    if condition == 'REQHISTORY': #メッセージタイプが履歴取得
        if p_token == "YES":      #履歴の場合はTOKENにYESを設定 contentは空
            history_send(client)


# メイン処理
server = WebsocketServer(PORT, host=HOST)
server.set_fn_new_client(new_client)
server.set_fn_client_left(client_left)
server.set_fn_message_received(message_received)
server.run_forever()
