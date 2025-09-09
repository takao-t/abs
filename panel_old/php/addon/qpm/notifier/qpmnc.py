#-*- coding:utf-8 -*-
# QPM用着信通知クライアント
# WebSocketでディスパッチャに投げるだけ
# websocket-clientが必要
# pip3 install websocket-client
#
# python3 qpmnc.py 番号
#

import sys

from qpmnd_config import *

from websocket import create_connection

TARGET = 'ws://' + HOST + ':' + str(PORT)

try:
    sys.argv[2]
    cidname = sys.argv[2]
except:
    cidname = ""

try:
    sys.argv[1]

    s_message = sys.argv[1]

    ws = create_connection(TARGET)

    send_str = 'INCOMING:' + TOKEN + ':' + s_message + ':' + cidname
    #print(send_str)

    ws.send(send_str)
    ws.close()

except:
    print("python3 qpmnc.py number [name]")
