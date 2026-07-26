#!/bin/sh
HOST=$(printf '%s' "$FULL_URL" | sed -E 's#^https?://##; s#/$##')
HC_HOST="$HOST" php -r '
$h = getenv("HC_HOST");
$ctx = stream_context_create(["http" => ["header" => "Host: $h\r\n", "timeout" => 5, "ignore_errors" => true]]);
$body = @file_get_contents("http://127.0.0.1/w/Main_Page", false, $ctx);
$code = 0;
if (isset($http_response_header[0]) && preg_match("/(\d{3})/", $http_response_header[0], $m)) {
    $code = (int)$m[1];
}
if ($code !== 200) {
    fwrite(STDERR, "MediaWiki health check failed (HTTP $code)\n");
    exit(1);
}
exit(0);
'
