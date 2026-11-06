set -eu

sh scripts/content-build.sh

drift=0

jq -r '(.styles + .raw)[] | "\(.path)\t\(.page)"' content/config.json | {
	while IFS="$(printf '\t')" read -r path page; do
		built="content/.build/$(echo "$path" | tr '/' '_')"
		case "$path" in *.scss) built="$built.css" ;; esac

		live=$(docker compose exec -T crw-local bash -c \
			'MW_CONFIG_FILE=/var/www/html/LocalSettings.php php /var/www/html/maintenance/run.php getText "$1" 2>/dev/null' _ "$page" </dev/null || true)

		if [ "$(printf '%s' "$live" | sed -e 's/[[:space:]]*$//')" != "$(sed -e 's/[[:space:]]*$//' "$built")" ]; then
			echo "  DRIFT  $page  (from content/$path)"
			drift=$((drift + 1))
		fi
	done
	echo
	if [ "$drift" -gt 0 ]; then
		echo "$drift page(s) differ from content/. Run 'make content-publish' to update the wiki."
		exit 1
	fi
	echo "The wiki matches content/."
}
