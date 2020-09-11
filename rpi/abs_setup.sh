#!/bin/sh

#
cd /root

#
echo "システムアップデート中"
apt-get -y update
apt-get -y upgrade

echo "追加パッケージインストール中"
apt-get -y install ncurses-dev libxml2 libxml2-dev sqlite libsqlite3-dev libssl-dev uuid uuid-runtime uuid-dev libedit-dev
apt-get -y install subversion git
apt-get -y install apache2 php php-mbstring
systemctl stop apache2

#
echo "Asterisk ダウンロード中"
wget http://downloads.asterisk.org/pub/telephony/asterisk/asterisk-16-current.tar.gz
tar zxvf asterisk-16-current.tar.gz
cd asterisk-16.*

#
echo "Asterisk コンパイル中"
./configure --with-jansson-bundled
make

#
echo "Asterisk インストール中"
make install
make samples
make config
systemctl stop asterisk

#
echo "ABSダウンロード中"
git clone https://github.com/takao-t/abs.git abs

# サウンドファイル展開
tar zxvf abs/sound/sounds-ja.tar.z -C /var/lib/asterisk/sounds/
mkdir /var/spool/asterisk/recording
cp /var/lib/asterisk/sounds/ja/abs-tc*.wav /var/spool/asterisk/recording/.

#
echo "ユーザ情報設定"
groupadd -g 5060 asterisk
useradd -u 5060 -g 5060 asterisk
adduser www-data asterisk
rm -rf /etc/asterisk/*
cp -r abs/exten/* /etc/asterisk/
chmod +x /etc/asterisk/pj_phonegen.sh
chmod +x /etc/asterisk/scripts/changemode.sh

#
echo "各種設定情報調整"
/etc/asterisk/pj_phonegen.sh > /etc/asterisk/pjsip_wizard.conf
/etc/asterisk/scripts/changemode.sh
cat /etc/default/asterisk | sed 's/#AST_USER="asterisk"/AST_USER="asterisk"/' | sed 's/#AST_GROUP="asterisk"/AST_GROUP="asterisk"/' > tmp
cp tmp /etc/default/asterisk
rm -f tmp
cp -r abs/panel/* /var/www/html/.
mkdir /var/www/absp
mv /var/www/html/userinfo.dat /var/www/absp/.
rm /var/www/html/index.html
cp /var/www/html/icons/* /usr/share/apache2/icons/.
echo "DirectoryIndex index.php" > /var/www/html/.htaccess
chown -R www-data:www-data /var/www/html
chown -R www-data:www-data /var/www/absp

#
echo "アドオンインストール"
mkdir /var/www/html/php/addon
mkdir /var/www/html/backup
mkdir /var/www/html/php/addon/templates
mkdir /var/www/html/prov
mkdir /var/www/html/prov/pana

cp abs/addons/*.php /var/www/html/php/addon/.
cp abs/addons/toolsdef.php /var/www/html/php/.
cp abs/addons/templates/* /var/www/html/php/addon/templates/.
chown -R www-data:www-data /var/www/html/php/addon
chown -R www-data:www-data /var/www/html/backup
chown -R www-data:www-data /var/www/html/prov/pana

#
echo "QPMインストール"
apt-get -y install php-sqlite3
mkdir /var/www/qpm
mkdir /var/www/qpm/csv
sqlite3 /var/www/qpm/qpm.db < abs/addons/qpm/qpminit.sql
chown -R www-data:www-data /var/www/qpm
mkdir /var/www/html/qpm
cp abs/addons/qpm/*.php /var/www/html/qpm/.
cp abs/addons/qpm/*.tmpl /var/www/html/qpm/.
cp -r /var/www/html/css /var/www/html/qpm/.
chown -R www-data:www-data /var/www/html/qpm
cp abs/addons/qpm/manage/* /var/www/html/php/addon/.
chown -R www-data:www-data /var/www/html/php
cp abs/addons/qpm/manage/toolsdef.php /var/www/html/php/.

pip3 install websocket-client
git clone https://github.com/Pithikos/python-websocket-server.git
cd python-websocket-server/
python3 ./setup.py install
cd ..

mkdir /var/lib/asterisk/qpmnd
cp  abs/addons/qpm/notifier/* /var/lib/asterisk/qpmnd/.

MYIPADDR=`ip add show eth0 |grep 'inet '|cut -f6,6 -d' '|cut -f1,1 -d'/'`
cat /var/lib/asterisk/qpmnd/qpmnd_config.tmpl | sed s/MYIPADDR/$MYIPADDR/ > /var/lib/asterisk/qpmnd/qpmnd_config.py
cat /var/www/html/qpm/config.php.tmpl | sed s/MYIPADDR/$MYIPADDR/ > /var/www/html/qpm/config.php

if grep -q qpmnd /etc/rc.local
then
  echo 'rc.local already updated'
else
  cat /etc/rc.local | sed 's,^exit 0,\/var\/lib\/asterisk\/qpmnd/qpmnd.sh \&\nexit 0,' > rclocal.tmp
  cp rclocal.tmp /etc/rc.local
fi
chmod +x /var/lib/asterisk/qpmnd/qpmnd.sh

#
wget https://raw.githubusercontent.com/takao-t/abs/abs2/rpi/exten_macro.patch
patch -p0 /etc/asterisk/extensions_macros.conf < exten_macro.patch


#
systemctl enable apache2
systemctl enable asterisk

echo "再起動してください"
