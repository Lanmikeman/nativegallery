<?php

if (!function_exists('ng_asset_query')) {
    function ng_asset_query(): string
    {
        static $query = null;
        if ($query !== null) {
            return $query;
        }
        $hash = @file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/.git/refs/heads/main');
        $query = $hash ? mb_substr(trim($hash), 0, 7) : 'dev';
        if (NGALLERY['root']['cloudflare-caching'] === true) {
            $query .= '.' . time();
        }
        return $query;
    }

    function ng_asset(string $path): string
    {
        return $path . '?' . ng_asset_query();
    }
}