set -eu

rm -rf content/.build
mkdir -p content/.build

jq -r '.raw[].path' content/config.json | while read -r path; do
	cp "content/$path" "content/.build/$(echo "$path" | tr '/' '_')"
done

styles=$(jq -r '.styles[].path' content/config.json | tr '\n' ' ')

docker run --rm -v "$PWD/content:/c" -w /c node:20-alpine sh -c "
	npm install -g --silent --no-audit --no-fund sass@1.79.4 >/dev/null 2>&1
	fail=0
	for path in $styles; do
		sass --no-source-map --no-error-css --style=expanded --quiet \"\$path\" \".build/\$(echo \"\$path\" | tr / _).css\" || fail=1
	done
	chown -R $(id -u):$(id -g) .build
	exit \$fail
"

echo "Built $(find content/.build -type f | wc -l) file(s)."
