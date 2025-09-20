<?php

# Load the InputBox extension
wfLoadExtension( 'InputBox' );

# Configure preload templates and defaults for different content types
$wgInputBoxConfig = [
    # Enable placeholder text for better UX
    'enablePlaceholder' => true,

    # Set default width for input boxes
    'defaultWidth' => 60,

    # Enable inline display where appropriate
    'inlineDisplay' => true
];

$wgInputBoxTemplates = [
    # Incident Reports - For documenting specific consumer protection issues
    'incident' => [
        'preload' => 'Template:IncidentReport',
        'width' => 60,
        'placeholder' => 'Enter a descriptive title for the incident...',
        'buttonlabel' => 'Document Consumer Protection Issue',
        'editintro' => 'Template:IncidentGuide'
    ],

    # Company Profiles - For documenting company practices
    'company' => [
        'preload' => 'Template:CompanyProfile',
        'width' => 50,
        'placeholder' => 'Enter company name...',
        'buttonlabel' => 'Create Company Profile',
        'editintro' => 'Template:CompanyGuide'
    ],

    # Theme Articles - For broader consumer protection topics
    'theme' => [
        'preload' => 'Template:ThemeArticle',
        'width' => 60,
        'placeholder' => 'Enter theme title...',
        'buttonlabel' => 'Create Theme Article',
        'editintro' => 'Template:ThemeGuide'
    ]
];

# Configure default namespaces for different content types
$wgInputBoxNamespaces = [
    'incident' => NS_PRIMARY,
    'company' => NS_PRIMARY,
    'theme' => NS_PRIMARY
];

# Set up prefixes to help organize content
$wgInputBoxPrefixes = [
    'incident' => 'Incident/',
    'company' => 'Company/',
    'theme' => 'Theme/'
];
