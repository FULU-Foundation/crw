<?php

wfLoadExtension( 'OATHAuth' );

$wgOATHSecretKey = getenv( 'OATHAUTH_SECRET_KEY' );
