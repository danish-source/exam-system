<?php
declare(strict_types=1);

/**
 * If the app is not at the server document root, set this to the URL path (no trailing slash).
 * Examples: '' for http://localhost/ | '/online-exam' for http://localhost/online-exam/
 */
if (!defined('BASE_URL')) {
    define('BASE_URL', '/year%20final');
}

if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'Skill UpLearner');
}

/** Footer / contact — edit for your organization */
if (!defined('CONTACT_PHONE')) {
    define('CONTACT_PHONE', '+92 300 1234567');
}
if (!defined('CONTACT_PHONE_TEL')) {
    define('CONTACT_PHONE_TEL', '+923001234567');
}
if (!defined('CONTACT_EMAIL')) {
    define('CONTACT_EMAIL', 'support@skilluplearner.com');
}
if (!defined('CONTACT_INSTAGRAM_URL')) {
    define('CONTACT_INSTAGRAM_URL', 'https://instagram.com/skilluplearner');
}
if (!defined('CONTACT_FACEBOOK_URL')) {
    define('CONTACT_FACEBOOK_URL', 'https://facebook.com/skilluplearner');
}

function url(string $path): string
{
    $p = ltrim($path, '/');
    $b = rtrim(BASE_URL, '/');
    if ($b === '') {
        return '/' . $p;
    }
    return $b . '/' . $p;
}
