<?php

use Wikimedia\Minify\CSSMin;

$wgHooks['BeforePageDisplay'][] = function ( OutputPage $out, Skin $skin ) {
    foreach ( glob( __DIR__ . '/css/*.css' ) as $cssFile ) {
        $out->addInlineStyle( CSSMin::minify( file_get_contents( $cssFile ) ) );
    }
};
