<?php
/*
    MUNCH EMAIL CONFIGURATION
    -------------------------
    Localhost/XAMPP normally cannot send real email unless SMTP/mail is configured.
    By default, every email will be saved into /emails_outbox so you can test the full flow.

    To try PHP mail(), set MUNCH_USE_PHP_MAIL to true after configuring sendmail.ini/php.ini.
*/
define('MUNCH_USE_PHP_MAIL', false);
define('MUNCH_FROM_EMAIL', 'no-reply@munch.local');
define('MUNCH_FROM_NAME', 'Munch Food Ordering System');
?>
