<?php

wfLoadExtension( 'Cite' );          // Enable citation features.
wfLoadExtension( 'TemplateData' );  // Add template documentation for VisualEditor.
wfLoadExtension( 'Citoid' );        // Enable automatic citation generation.

# Enable citation templates and reference features.
$wgVisualEditorEnableCitations = true;         // Enable citation tools in VisualEditor.
$wgCiteVisualEditorOtherGroup = true;          // Group "other" citation templates.
$wgVisualEditorEnableReferences = true;        // Enable references in VisualEditor.

# Allow import of citation templates from Wikipedia for better citation functionality.
$wgImportSources = [ 'wikipedia' ];

# Configure Citoid service for automatic citation generation.
$wgCitoidServiceUrl = "https://citoid.wikimedia.org/api";  // URL for the Citoid API.
$wgVisualEditorEnableCitoidSupport = true;                // Enable automatic citation features in VisualEditor.
