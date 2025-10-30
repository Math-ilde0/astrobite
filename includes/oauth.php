<?php
// OAuth config placeholders – fill with your credentials

// Base path for redirects
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$siteBase = $basePath === '' ? '' : $basePath;

// Google
define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: 'YOUR_GOOGLE_CLIENT_ID');
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: 'YOUR_GOOGLE_CLIENT_SECRET');
define('GOOGLE_REDIRECT_URI', (isset($_SERVER['HTTP_HOST']) ? (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] : '') . $siteBase . '/google-login/callback.php');

// Facebook
define('FACEBOOK_CLIENT_ID', getenv('FACEBOOK_CLIENT_ID') ?: 'YOUR_FACEBOOK_APP_ID');
define('FACEBOOK_CLIENT_SECRET', getenv('FACEBOOK_CLIENT_SECRET') ?: 'YOUR_FACEBOOK_APP_SECRET');
define('FACEBOOK_REDIRECT_URI', (isset($_SERVER['HTTP_HOST']) ? (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] : '') . $siteBase . '/facebook-login/callback.php');

?>

