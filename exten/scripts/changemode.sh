chown -R asterisk:asterisk /var/lib/asterisk
chown -R asterisk:asterisk /var/log/asterisk
chown -R asterisk:asterisk /var/spool/asterisk
chown -R asterisk:asterisk /etc/asterisk
chmod -R u=rwX,g=rwX,o= /var/lib/asterisk 
chmod -R u=rwX,g=rwX,o= /var/log/asterisk 
chmod -R u=rwX,g=rwX,o= /var/spool/asterisk 
chmod -R u=rwX,g=rwX,o= /etc/asterisk

