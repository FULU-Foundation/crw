docker compose exec -T crw-local bash -c 'MW_CONFIG_FILE=/var/www/html/LocalSettings.php php /var/www/html/maintenance/run.php CirrusSearch:ForceSearchIndex --skipLinks --indexOnSkip && MW_CONFIG_FILE=/var/www/html/LocalSettings.php php /var/www/html/maintenance/run.php CirrusSearch:ForceSearchIndex --skipParse'
# Run the queued index jobs
until [ "$(docker compose exec -T crw-local bash -c 'MW_CONFIG_FILE=/var/www/html/LocalSettings.php php /var/www/html/maintenance/run.php showJobs --type=cirrusSearchElasticaWrite' | tr -dc '0-9')" = "0" ]; do
    docker compose exec -T crw-local bash -c 'MW_CONFIG_FILE=/var/www/html/LocalSettings.php php -d memory_limit=1G /var/www/html/maintenance/run.php runJobs --type=cirrusSearchElasticaWrite --maxjobs=500'
done
