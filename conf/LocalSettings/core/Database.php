<?php

# Database settings
$wgDBtype = getenv('DB_TYPE');
$wgDBserver = getenv('DB_SERVER');
$wgDBname = getenv('DB_NAME');;
$wgDBuser = getenv('DB_USER');
$wgDBpassword = getenv('DB_PASSWORD');

# MySQL specific settings
$wgDBprefix = "";

# MySQL table options to use during installation or update
$wgDBTableOptions = "ENGINE=InnoDB, DEFAULT CHARSET=binary";

$wgDBssl = true;