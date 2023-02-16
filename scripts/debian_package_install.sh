#!/bin/sh
# package instalation for Asterisk and ABS

apt update
apt -y install build-essential libedit-dev uuid-dev libxml2-dev ncurses-dev libsqlite3-dev sqlite3 libssl-dev subversion git net-tools dnsutils
apt -y install apache2 php php-mbstring php-sqlite3
