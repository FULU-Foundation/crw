<?php

# InstantCommons allows wiki to use images from https://commons.wikimedia.org
$wgUseInstantCommons = true;

# File upload settings
$wgEnableUploads = true; // Enable file uploads to the wiki.
$wgMaxUploadSize = 64 * 1024 * 1024; // Maximum file upload size (64 MB).
$wgUploadSizeWarning = 32 * 1024 * 1024; // Show a warning for files larger than 32 MB.
$wgAllowCopyUploads = true; // Allow uploading files from remote URLs.
$wgDisableUploadScriptChecks = false; // Perform security checks for uploaded script files.

# File storage directories
$wgUploadDirectory = "{$IP}/images"; // Directory for storing uploaded files.
$wgUploadPath = "/images"; // Web-accessible path to uploaded files.
$wgDeletedDirectory = "{$IP}/images/deleted"; // Directory for files deleted from the wiki.
//$wgTmpDirectory = "{$IP}/images/temp"; // Temporary directory for processing uploads.

# Image processing and tools
$wgUseImageMagick = true; // Enable ImageMagick for advanced image processing.
$wgImageMagickConvertCommand = "/usr/bin/convert"; // Path to ImageMagick's `convert` command.
$wgSVGConverter = 'ImageMagick'; // Use ImageMagick for processing SVG files.
$wgMaxImageArea = 500e6; // Allow images up to 500 million pixels in total area.
$wgImageMagickTempDir = "/tmp"; // Temporary directory for ImageMagick operations.
$wgSVGMaxSize = 8192; // Maximum size (in pixels) for SVG files (default is 5120).
$wgThumbnailEpoch = '20250114000000'; // Forces re-generation of thumbnails after this timestamp.
$wgGenerateThumbnailOnParse = true; // Ensures thumbnails are created during page parsing.

# Thumbnail generation and display
$wgThumbnailScriptPath = "{$wgScriptPath}/thumb.php"; // MediaWiki script for dynamic thumbnail generation.
$wgShowImageInline = true; // Allow large images to display inline without scaling.

# Thumbnail and image size limits
$wgThumbLimits = [120, 150, 180, 200, 250, 300, 1024]; // Allowed thumbnail sizes.
$wgImageLimits = [
    [1024, 768],    // Standard 4:3 aspect ratio.
    [2048, 1536],   // Larger 4:3 images.
    [4096, 3072],   // High-resolution 4:3 images.
    [10000, 10000]  // Allow very large square images.
];

# Allowed file types and formats
$wgFileExtensions = array_merge($wgFileExtensions ?? [], [
    'png', 'gif', 'jpg', 'jpeg', 'webp', // Common image formats.
    'pdf', 'svg',                        // Document and vector formats.
    'doc', 'docx', 'xls', 'xlsx', 'odt', 'ods', // Office file formats.
    'mp4', 'm4v', 'webm', 'mov', 'avi', 'mkv', 'flv', 'wmv', 'ogv', // Video formats.
    'mp3', 'oga', 'ogg', 'wav', 'flac' // Audio formats.
]);

# Audio and video uploads are restricted to holders of upload-media (sysop, superadmin).
$wgHooks['UploadVerifyFile'][] = function ( $upload, $mime, &$error ) {
    $context = RequestContext::getMain();
    $user = $context->getUser();

    $restrictedExts = [
        'mp4', 'm4v', 'webm', 'mov', 'avi', 'mkv', 'flv', 'wmv', 'ogv',
        'mp3', 'oga', 'ogg', 'wav', 'flac',
    ];
    $ext = strtolower( pathinfo( $upload->getLocalFile()->getName(), PATHINFO_EXTENSION ) );

    if ( in_array( $ext, $restrictedExts, true ) && !$user->isAllowed( 'upload-media' ) ) {
        $error = 'Audio and video uploads are restricted to administrators.';
        return false;
    }

    return true;
};

$wgTrustedMediaFormats = array_merge($wgTrustedMediaFormats ?? [], [
    'image/jpeg', 'image/png', 'image/gif', 'image/webp', // Image MIME types.
    'application/pdf', 'image/svg+xml',                   // Document MIME types.
    'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // Word MIME types.
    'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // Excel MIME types.
    'application/vnd.oasis.opendocument.text', 'application/vnd.oasis.opendocument.spreadsheet', // OpenDocument MIME types.
    'video/mp4', 'video/webm', 'video/ogg' // Video MIME types.
]);

$wgVerifyMimeType = true; // Verify that file content matches the declared MIME type.
$wgCheckFileExtensions = true; // Restrict uploads to explicitly allowed file extensions.
$wgStrictFileExtensions = true; // Strictly block unallowed file extensions.
$wgMimeDetectorCommand = 'file -bi'; // Command to detect MIME types of uploaded files.
$wgProhibitedFileExtensions = ['exe', 'scr', 'vbs', 'bat', 'cmd', 'sh', 'php', 'pl']; // Block dangerous file types.

# Image handling and display
$wgShowArchiveThumbnails = true; // Allow thumbnails of archived images to display.
$wgEnableAutoRotation = true; // Automatically rotate images based on EXIF data.
$wgAllowExternalImagesFrom = []; // Disallow embedding images hosted externally.
$wgAllowImageTag = true; // Enable the use of <img> HTML tags in content.