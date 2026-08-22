#!/usr/bin/env sh
su -s /bin/bash www-data -c "bash /wiki/cron/update_spamlist.sh"
su -s /bin/bash www-data -c "bash /wiki/cron/generate_sitemap.sh"
su -s /bin/bash www-data -c "bash /wiki/cron/load_tor_nodes.sh"
/usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf