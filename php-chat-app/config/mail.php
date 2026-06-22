<?php
declare(strict_types=1);

/**
 * Mail settings for password reset and admin digests.
 * Set enabled => true and from_address when SMTP/sendmail is configured on the server.
 */
return [
    'enabled' => (bool) (getenv('LETSCHAT_MAIL_ENABLED') ?: false),
    'from_address' => getenv('LETSCHAT_MAIL_FROM') ?: 'noreply@livingspring.local',
    'from_name' => getenv('LETSCHAT_MAIL_FROM_NAME') ?: 'LivingSpring LetsChat',
];
