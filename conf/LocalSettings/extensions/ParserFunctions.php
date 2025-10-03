<?php

# Load the ParserFunctions extension.
# Provides advanced parser functions for templates, enabling logic like if-else conditions.
wfLoadExtension( 'ParserFunctions' );

# Enable advanced string functions for more powerful template capabilities.
# This allows operations like trimming strings, changing case, and finding string lengths.
# Examples of usage can include checking input lengths, formatting user inputs, etc.
$wgPFEnableStringFunctions = true;

# Optional: Limit the maximum string length for operations.
# This setting helps prevent performance issues with very large strings in templates.
# Set this to a reasonable value based on your use case.
# Default is 1000 characters.
$wgPFStringLengthLimit = 1000;
