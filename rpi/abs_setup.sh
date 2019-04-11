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
echo "起動中"
systemctl enable apache2
systemctl enable asterisk
systemctl start apache2
systemctl start asterisk
