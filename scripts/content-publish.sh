set -eu

sh scripts/content-build.sh

jq -r '(.styles + .raw)[] | "\(.path)\t\(.page)"' content/config.json | while IFS="$(printf '\t')" read -r path page; do
	built="content/.build/$(echo "$path" | tr '/' '_')"
	case "$path" in *.scss) built="$built.css" ;; esac

	live=$(docker compose exec -T crw-local bash -c \
		'MW_CONFIG_FILE=/var/www/html/LocalSettings.php php /var/www/html/maintenance/run.php getText "$1" 2>/dev/null' _ "$page" </dev/null || true)

	if [ "$(printf '%s' "$live" | sed -e 's/[[:space:]]*$//')" = "$(sed -e 's/[[:space:]]*$//' "$built")" ]; then
		continue
	fi

	echo "  publishing $page"
	docker compose exec -T crw-local bash -c \
		'MW_CONFIG_FILE=/var/www/html/LocalSettings.php php /var/www/html/maintenance/run.php edit --bot --no-rc --user="$1" --summary="Published from content/$2" "$3"' \
		_ "${CONTENT_BOT_USER:-Admin}" "$path" "$page" < "$built" >/dev/null
done

# Process link updates
docker compose exec -T crw-local bash -c \
	"MW_CONFIG_FILE=/var/www/html/LocalSettings.php php /var/www/html/maintenance/run.php runJobs --maxjobs=500" \
	</dev/null >/dev/null

echo "Publish complete."
