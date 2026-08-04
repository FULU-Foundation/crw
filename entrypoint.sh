#!/usr/bin/env sh
su -s /bin/bash www-data -c "bash /wiki/cron/update_spamlist.sh"
/usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf