<?php
ini_set('display_errors', False);

if(getenv('WIKI_ENV') == "Dev") {
    # Display errors and enable the Debug toolbar for development environments
    ini_set('display_errors', True);
    $wgDebugToolbar = true;
    $wgShowExceptionDetails = true;
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
