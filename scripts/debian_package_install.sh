#!/bin/sh
# package instalation for Asterisk and ABS

apt update
apt -y install build-essential libedit-dev uuid-dev libxml2-dev libncurses-dev libsqlite3-dev sqlite3 libssl-dev subversion git net-tools dnsutils libsrtp2-dev libunbound-dev
apt -y install apache2 php php-mbstring php-sqlite3
