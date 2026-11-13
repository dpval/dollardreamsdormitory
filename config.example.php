<?php
/*
 * Copy this file to config.php on your hosting and fill in the real values.
 * config.php is listed in .gitignore, so it never goes to GitHub.
 */
return [
    // MySQL (InfinityFree control panel -> MySQL Databases)
    'db_host' => 'sqlXXX.byetcluster.com',
    'db_user' => 'if0_XXXXXXXX',
    'db_pass' => 'YOUR_DATABASE_PASSWORD',
    'db_name' => 'if0_XXXXXXXX_dorm_tracker',

    // Resend (resend.com -> API Keys)
    'resend_api_key' => 're_YOUR_KEY',
    'mail_from'      => 'Dollar Dreams Dormitory <onboarding@resend.dev>',
    'notify_emails'  => ['you@example.com'],
];
