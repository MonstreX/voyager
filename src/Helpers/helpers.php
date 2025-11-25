<?php

if (!function_exists('setting')) {
    function setting($key, $default = null)
    {
        return TCG\Voyager\Facades\Voyager::setting($key, $default);
    }
}

if (!function_exists('menu')) {
    function menu($menuName, $type = null, array $options = [])
    {
        return TCG\Voyager\Facades\Voyager::model('Menu')->display($menuName, $type, $options);
    }
}

if (!function_exists('voyager_asset')) {
    function voyager_asset($path = '', $secure = null)
    {
        $normalizedPath = str_replace('\\', '/', ltrim($path, '/'));
        $segments = array_filter(explode('/', $normalizedPath), function ($segment) {
            return $segment !== '' && $segment !== '.';
        });
        $safeSegments = [];
        foreach ($segments as $segment) {
            if ($segment === '..' || str_contains($segment, '..')) {
                continue;
            }
            $safeSegments[] = $segment;
        }

        $cleanPath = implode('/', $safeSegments);
        $publicRelativePath = trim('vendor/voyager/'.$cleanPath, '/');

        if ($cleanPath === '') {
            $publicRelativePath = 'vendor/voyager';
        }

        return asset($publicRelativePath, $secure);
    }
}

if (!function_exists('get_file_name')) {
    function get_file_name($name)
    {
        preg_match('/(_)([0-9])+$/', $name, $matches);
        if (count($matches) == 3) {
            return Illuminate\Support\Str::replaceLast($matches[0], '', $name).'_'.(intval($matches[2]) + 1);
        } else {
            return $name.'_1';
        }
    }
}
