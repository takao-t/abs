#-*- coding:utf-8 -*-

import sys
import signal
import time
import socket

# ホスト情報設定ファイル
import blfl_config as blfl_config

try:
    blfl_config.AMI_FROM_HOST
    blfl_config.AMI_FROM_PORT
    blfl_config.AMI_FROM_USER
    blfl_config.AMI_FROM_PASS
    blfl_config.AMI_TO_HOST
    blfl_config.AMI_TO_PORT
    blfl_config.AMI_TO_USER
    blfl_config.AMI_TO_PASS
except:
    print("please setup config file.")
    sys.exit()

try:
    blfl_config.DEVICE_LIST
    device_list = blfl_config.DEVICE_LIST
except:
    device_list = 'device_list.txt'

devlist = {}

# デバイス対応リストを読み込み辞書型で登録
with open(device_list, mode='rt', encoding='utf-8') as f:
    print('Building Device list')
    for line in f:
        line = line.replace('\r','')
        line = line.replace('\n','')
        if line.startswith('#'):
            continue
        else:
            dev_key = line.split(',')[0]
            dev_val = line.split(',')[1]
            devlist[dev_key] = dev_val
            print("%s -> %s" % (dev_key, dev_val))

if len(devlist) <= 0:
    print('Device list empty')
    sys.exit()

# Exten Stateの変換テーブル
exten_state = { \
    'Idle'          : 'NOT_INUSE', \
    'InUse'         : 'INUSE', \
    'Busy'          : 'BUSY', \
    'Unavailable'   : 'UNAVAILABLE', \
    'Ringing'       : 'RINGING', \
    'InUse&Ringing' : 'RINGINUSE', \
    'Hold'          : 'ONHOLD', \
    'InUse&Hold'    : 'ONHOLD', \
    'Unkown'        : 'UNKOWN' }


print("")
print("AMI Login")
# From側接続
try:
    ami_from = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    ami_from.connect((blfl_config.AMI_FROM_HOST, blfl_config.AMI_FROM_PORT))
    time.sleep(0.1)
    ret_from = ami_from.recv(1024) #結果は読み捨て
except:
    print('From HOST connection fail')
    sys.exit()

# To側接続
try:
    ami_to = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    ami_to.connect((blfl_config.AMI_TO_HOST, blfl_config.AMI_TO_PORT))
    time.sleep(0.1)
    ret_to = ami_to.recv(1024) #結果は読み捨て
except:
    print('To HOST connection fail')
    sys.exit()

# AMIにログインしイベントフィルタを設定

tmp_cmd = "Action: Login\r\nUsername: %s\r\nSecret: %s\r\n\r\n" % (blfl_config.AMI_FROM_USER, blfl_config.AMI_FROM_PASS)
tmp_cmd = tmp_cmd.encode(encoding='utf-8')
ami_from.sendall(tmp_cmd)
time.sleep(0.5)
ret_from = ami_from.recv(1024) #結果は読み捨て
print(ret_from.decode('utf-8'))
NOW = int(time.time())
ACTIONID = "blfl%d" % NOW
tmp_cmd = "Action: Filter\r\nActionID: %s\r\nOperation: add\r\nFilter: DeviceStateChange\r\n\r\n" % ACTIONID
tmp_cmd = tmp_cmd.encode(encoding='utf-8')
ami_from.sendall(tmp_cmd)
time.sleep(0.5)
ret_from = ami_from.recv(1024) #結果は読み捨て
print(ret_from.decode('utf-8'))
NOW = int(time.time())
ACTIONID = "blfl%d" % NOW
tmp_cmd = "Action: Filter\r\nActionID: %s\r\nOperation: add\r\nFilter: ExtensionStatus\r\n\r\n" % ACTIONID
tmp_cmd = tmp_cmd.encode(encoding='utf-8')
ami_from.sendall(tmp_cmd)
time.sleep(0.5)
ret_from = ami_from.recv(1024) #結果は読み捨て
print(ret_from.decode('utf-8'))

tmp_cmd = "Action: Login\r\nUsername: %s\r\nSecret: %s\r\n\r\n" % (blfl_config.AMI_TO_USER, blfl_config.AMI_TO_PASS)
tmp_cmd = tmp_cmd.encode(encoding='utf-8')
ami_to.sendall(tmp_cmd)
time.sleep(0.5)
ret_to = ami_to.recv(1024) #結果は読み捨て
print(ret_to.decode('utf-8'))
NOW = int(time.time())
ACTIONID = "blfl%d" % NOW
tmp_cmd = "Action: Filter\r\nActionID: %s\r\nOperation: add\r\nFilter: DeviceStateChange\r\n\r\n" % ACTIONID
tmp_cmd = tmp_cmd.encode(encoding='utf-8')
ami_to.sendall(tmp_cmd)
time.sleep(0.5)
ret_to = ami_to.recv(1024) #結果は読み捨て
print(ret_to.decode('utf-8'))
NOW = int(time.time())
ACTIONID = "blfl%d" % NOW
tmp_cmd = "Action: Filter\r\nActionID: %s\r\nOperation: add\r\nFilter: ExtensionStatus\r\n\r\n" % ACTIONID
tmp_cmd = tmp_cmd.encode(encoding='utf-8')
ami_to.sendall(tmp_cmd)
time.sleep(0.5)
ret_to = ami_to.recv(1024) #結果は読み捨て
print(ret_to.decode('utf-8'))

# Ready
time.sleep(0.5)
print('Login complete. Raady.')

# シグナルハンドラ(終了処理)
def signal_handler(signal,stack):
    print('Got signal: Quiting...')

    # ログオフ
    ami_from.sendall(b"Action: Logoff\r\n\r\n")
    ret_from = ami_from.recv(1024)
    print(ret_from.decode('utf-8'))
    ami_to.sendall(b"Action: Logoff\r\n\r\n")
    ret_to = ami_to.recv(1024)
    print(ret_to.decode('utf-8'))
    time.sleep(0.5)
    # Close
    ami_from.close()
    ami_to.close()
    sys.exit()

# AMIでSTATE設定
def set_device_state(device,state):

    NOW = int(time.time())
    ACTIONID = "blfl%d" % NOW
    to_state_cmd = "devstate change %s %s" % (device,state)
    to_ami_cmd ="Action: Command\r\nActionID: %s\r\nCommand: %s\r\n\r\n" % (ACTIONID, to_state_cmd)
    to_ami_cmd = to_ami_cmd.encode(encoding='utf-8')
    #print(to_state_cmd)
    #print(to_ami_cmd)
    # devstate コマンドでSTATEを変更
    ami_to.sendall(to_ami_cmd)
    time.sleep(0.1)
    ret_to = ami_to.recv(1024) #結果は読み捨て
    #print(ret_to.decode('utf-8'))
    return


# メイン
def main():

    # シグナル(HUP,INT,QUIT,TERMで終了)
    signal.signal(signal.SIGHUP, signal_handler)
    signal.signal(signal.SIGINT, signal_handler)
    signal.signal(signal.SIGQUIT, signal_handler)
    signal.signal(signal.SIGTERM, signal_handler)

    # メインループ
    while True:
        # AMIのイベント受信待ち
        ret_from = ami_from.recv(1024)
        #print(ret_from.decode('utf-8'))
        # デコードして行単位に分割
        ret_from = ret_from.decode('utf-8')
        content = ret_from.splitlines() 
        #
        event = ''
        target_from = ''
        target_state = ''
        ev_match = 0
        tg_got = 0
        # イベントから目的のものを検索
        for line in content:
            # Fromから受信したAMIイベントからEventがDeviceStateChangeかExensionStatusを探す
            if line.find('Event:') != -1:
                if line.find('DeviceStateChange') != -1:
                    ev_match = 1 # 目的のイベントがDevice
                elif line.find('ExtensionStatus') != -1:
                    ev_match = 2 # 目的のイベントがExten
                else:
                    ev_match = 0

            if ev_match == 1: #目的のイベントがDeviceSateならデバイスを探す
                if line.find('Device:') != -1:
                    target_from = line.split(': ', 1)[1]
                    #print(target_from)
            elif ev_match == 2: #目的のイベントがExtensionStatusならExtenを探す
                if line.find('Exten:') != -1:
                    target_from = line.split(': ', 1)[1]
                    #print(target_from)
            else:
                target_from = ''

            # 処理対象イベントの場合
            if ev_match == 1 or ev_match == 2:
                #From側が取得できているか
                if target_from != '':

                     #Device or Extenが処理対象か？
                     try:
                         target_to = devlist[target_from]
                     except:
                         target_to =''

                     if target_to != '':
                         if ev_match == 1:
                             if line.find('State:') != -1:
                                 target_state = line.split(': ', 1)[1]
                                 #print(target_state)
                         elif ev_match == 2:
                             if line.find('StatusText:') != -1:
                                 state_tmp = line.split(': ', 1)[1]
                                 # devstate形式に変換する
                                 target_state = exten_state[state_tmp]
                                 #print(target_state)

                # 全情報が揃ったら送る
                if ev_match != 0 and target_state != '' and target_to != '':
                    try:
                        blfl_config.quiet
                    except:
                        print("%s %s -> %s" % (target_from, target_state, target_to))
                    set_device_state(target_to,target_state)
                    target_state = ''
                    target_to = ''
                    ev_match = 0


#
#####
#
if __name__ == "__main__":
    main()
