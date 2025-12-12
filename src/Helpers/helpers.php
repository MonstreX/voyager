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

if (!function_exists('voyager_tree_build')) {
    function voyager_tree_build(array $elements, $parentId = null)
    {
        $branch = [];

        foreach ($elements as $element) {
            if ($element['parent_id'] == $parentId) {
                $children = voyager_tree_build($elements, $element['id']);
                if ($children) {
                    $element['children'] = $children;
                }
                $branch[] = $element;
            }
        }

        // Sort strictly by 'order'
        usort($branch, function ($a, $b) {
            return ((int)($a['order'] ?? 0)) <=> ((int)($b['order'] ?? 0));
        });

        return $branch;
    }
}

if (!function_exists('flat_to_tree')) {
    /**
     * Convert flat array to tree structure (wrapper for voyager_tree_build)
     */
    function flat_to_tree($flat_array)
    {
        if (empty($flat_array) || !is_array($flat_array)) {
            return [];
        }
        if (!array_key_exists('parent_id', $flat_array[0])) {
            return $flat_array;
        }
        return voyager_tree_build($flat_array);
    }
}

if (!function_exists('build_flat_from_tree')) {
    /**
     * Convert tree back to flat array with level info
     */
    function build_flat_from_tree($tree)
    {
        $result = [];
        $level = 0;
        build_flat_children($tree, $result, $level);
        return $result;
    }
}

if (!function_exists('build_flat_children')) {
    /**
     * Recursive helper for flattening tree and adding level info
     */
    function build_flat_children($children, &$result, &$level)
    {
        foreach ($children as $child) {
            $elements = [];
            foreach ($child as $key => $field) {
                if ($key !== 'children') {
                    $elements[$key] = $field;
                    $elements['level'] = $level;
                }
            }
            $result[] = $elements;
            if (isset($child['children'])) {
                $level++;
                build_flat_children($child['children'], $result, $level);
                $level--;
            }
        }
    }
}
