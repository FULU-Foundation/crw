<?php

wfLoadExtension('DarkMode');
$wgDefaultUserOptions['darkmode'] = 1;  // Enable Dark Mode for all users by default.
$wgVectorNightMode['beta'] = true;
$wgVectorNightMode['logged_out'] = true;
$wgVectorNightMode['logged_in'] = true;
$wgDefaultUserOptions['vector-theme'] = 'os';