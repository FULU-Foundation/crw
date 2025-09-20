docker compose --file=./docker-compose.dev.yml exec crw-local bash -c "MW_CONFIG_FILE=/var/www/html/LocalSettings.php php /var/www/html/maintenance/run.php runJobs.php"
