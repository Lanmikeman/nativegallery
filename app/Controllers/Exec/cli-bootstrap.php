<?php

use App\Services\DB;
use Symfony\Component\Yaml\Yaml;

$projectRoot = dirname(__DIR__, 3);

if (!class_exists(DB::class)) {
    require_once $projectRoot . '/vendor/autoload.php';
}

if (!defined('NGALLERY')) {
    $configPath = $projectRoot . '/ngallery.yaml';
    if (!file_exists($configPath)) {
        fwrite(STDERR, "ngallery.yaml not found: {$configPath}\n");
        exit(1);
    }
    define('NGALLERY', Yaml::parse(file_get_contents($configPath))['ngallery']);
}

if (empty($_SERVER['DOCUMENT_ROOT'])) {
    $_SERVER['DOCUMENT_ROOT'] = $projectRoot;
}

\App\Services\Date::applySiteTimezone();
DB::ensureInitialized();