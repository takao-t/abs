#enoding: UTF-8

# ABSからSlackに着信通知を投げる
# argv[1] : 電話番号
# argv[2] : CID名(名前)

import sys
# SlackWebが必要なのでpipでインストールしておく
import slackweb

# SlackのWebHook URLを設定する
slack = slackweb.Slack(url="URL_HERE")

# Slackのメッセージをクリックして外部サービスを使用する場合のリンクを設定する
# CRM等でリンクパラメータで開ける場合には利用可能
# 電話番号に対するリンク
number_link = "https://www.google.com/search?hl=ja&q="
# CID名に対するリンク
title_link = "https://www.google.com/search?hl=ja&q="


try:
    sys.argv[1]
    in_number = sys.argv[1]
    try:
        in_text = sys.argv[2]
    except:
        in_text = in_number

    try:
        title_link
        if title_link != "":
            t_title = "<" + title_link + in_text + "|" + in_text + ">"
        else:
            t_title = in_text
    except:
        t_title = in_text

    try:
        number_link
        if number_link != "":
            t_number = "<" + number_link + in_number + "|" + in_number + ">"
        else:
            t_number = in_text
    except:
        t_number = in_number

    attach = []
    attach.append({"title": "外線着信"})
    attach.append({"pretext": t_title})
    attach.append({"text": t_number})
    attach.append({"mrkdwn_in": ["text", "pretext"]})
    slack.notify(attachments=attach)

except:
    pass
