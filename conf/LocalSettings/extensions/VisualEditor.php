<?php

# Load VisualEditor and its dependencies for enhanced editing capabilities.
wfLoadExtension( 'VisualEditor' );
wfLoadExtension( 'TemplateData' );  // Add template documentation for VisualEditor.

# VisualEditor Preferences
$wgDefaultUserOptions['visualeditor-editor'] = 'visualeditor';  // Set VisualEditor as the default editor.
$wgHiddenPrefs[] = 'visualeditor-editor';                      // Hide editor preference option from user settings.
$wgVisualEditorUseSingleEditTab = false;                       // Show separate tabs for "Edit" and "Edit source".

# Define namespaces where VisualEditor is enabled.
$wgVisualEditorAvailableNamespaces = [
    NS_MAIN => true,
    NS_USER => true,
    NS_PROJECT => true,
    NS_FILE => true,
    NS_HELP => true
];

# Configure how the edit tabs are displayed in the MediaWiki interface.
$wgVisualEditorEnableDiffPage = true;          // Enable visual diffs for easier comparison of changes.
$wgVisualEditorEnableWikitext = true;         // Enable wikitext mode in VisualEditor.
$wgVisualEditorTabPosition = 'before';        // Place the VisualEditor tab before the source editor tab.
$wgVisualEditorPreferStandardEdit = false;    // Do not force the standard editor for complex pages.
$wgVisualEditorShowBetaWelcome = false;       // Disable beta welcome popup for a cleaner user experience.
$wgVisualEditorEnableCite = true;    // Enable citations in VisualEditor.

# Remove duplicate or conflicting tab configurations.
unset($wgHooks['SkinTemplateNavigation::Universal']);

# Enable reference and citation features.
$wgVisualEditorEnableReferences = true;       // Ensure references are available in the editor.
