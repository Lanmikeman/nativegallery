<?php

/**
 * Nginx stub for /lk/ — physical lk/ directory would otherwise return 403 (no index file).
 */
require dirname(__DIR__) . '/index.php';