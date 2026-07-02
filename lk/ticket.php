<?php

/**
 * Legacy transphoto-style URL (/lk/ticket.php).
 * Nginx passes *.php to PHP-FPM as real files; this stub boots the front controller.
 */
require dirname(__DIR__) . '/index.php';