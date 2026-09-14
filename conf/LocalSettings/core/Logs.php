<?php
ini_set('display_errors', False);

if(getenv('WIKI_ENV') == "Dev") {
    # Display errors and enable the Debug toolbar for development environments
    ini_set('display_errors', True);
    $wgDebugToolbar = true;
    $wgShowExceptionDetails = true;

    # Main Debug Log File
    $wgDebugLogFile = "/var/log/mediawiki/debug.log"; // Central log file for general debugging information.
    $wgDebugLogGroups['redis'] = '/var/log/mediawiki/redis.log';
    $wgDebugLogGroups['CirrusSearch'] = '/var/log/mediawiki/cirrussearch.log';
    $wgDebugLogGroups['DBQuery'] = '/var/log/mediawiki/dbquery.log';
    $wgDebugLogGroups['runJobs'] = '/var/log/mediawiki/jobs.log';
} else {
    # Use structured JSON logging in non-development environments
    $wgMWLoggerDefaultSpi = [
        'class' => \MediaWiki\Logger\MonologSpi::class,
        'args' => [[
            'loggers' => [
                '@default' => [ 'handlers' => [ 'json' ] ],
            ],
            'handlers' => [
                'json' => [
                    'class' => Monolog\Handler\StreamHandler::class,
                    'args'  => [ '/var/log/mediawiki/mediawiki-json.log', Monolog\Logger::INFO ],
                    'formatter' => 'json',
                ],
            ],
            'formatters' => [
                'json' => [
                    'class' => Monolog\Formatter\JsonFormatter::class,
                    'args'  => [ true ],
                ],
            ],
        ]]
    ];
}

# Debug Log Groups
$wgDebugLogGroups['exception'] = '/var/log/mediawiki/exception.log'; // Log all exceptions to a dedicated file.
$wgDebugLogGroups['error'] = '/var/log/mediawiki/error.log';         // Log all errors to a separate file.
$wgDebugLogGroups['fatal'] = '/var/log/mediawiki/fatal.log';         // Log fatals to a separate file.
$wgDebugLogGroups['exec'] = '/var/log/mediawiki/exec.log';           // Log shell commands to a separate file.
