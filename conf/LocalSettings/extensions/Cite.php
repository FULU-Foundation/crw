<?php

wfLoadExtension( 'Cite' );          // Enable citation features.
wfLoadExtension( 'TemplateData' );  // Add template documentation for VisualEditor.
wfLoadExtension( 'Citoid' );        // Enable automatic citation generation.

# Enable citoid, if configured
if(getenv("CITOID_URL")) {
# Enable citation templates and reference features.
    $wgCiteVisualEditorOtherGroup = true;          // Group "other" citation templates.

    # Allow import of citation templates from Wikipedia for better citation functionality.
    $wgImportSources = [ 'wikipedia' ];

    $wgCitoidServiceUrl = getenv("CITOID_URL");  // URL for the Citoid API.
}
