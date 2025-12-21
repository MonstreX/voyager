<?php

namespace TCG\Voyager\Services\Bread;

use Illuminate\Http\Request;

class BreadBrowseFilterService
{
    /**
     * Resolve current browse filters for a given slug.
     *
     * Behaviour matches the legacy inline implementation:
     * - filters are stored in session under `filters`
     * - switching slug resets stored filters
     * - `?reset_filters` clears stored filters
     * - providing `field` and `value` in request overrides stored filters
     */
    public function resolve(Request $request, string $slug): ?array
    {
        if ($request->session()->has('filters') && ($request->session()->get('filters')['slug'] ?? null) !== $slug) {
            $request->session()->forget('filters');
        }

        if ($request->has('field') && $request->has('value')) {
            $filters = [
                'slug' => $slug,
                'field' => $request->get('field'),
                'value' => $request->get('value'),
            ];

            $request->session()->put('filters', $filters);

            return $filters;
        }

        if ($request->session()->has('filters') && !$request->has('reset_filters')) {
            return $request->session()->get('filters');
        }

        $request->session()->forget('filters');

        return null;
    }

    public function apply($query, ?array $filters): void
    {
        if (!$filters) {
            return;
        }

        $fields = $filters['field'] ?? null;
        $values = $filters['value'] ?? null;

        if (!is_array($fields) || !is_array($values)) {
            return;
        }

        foreach ($fields as $index => $field) {
            if (!isset($values[$index])) {
                continue;
            }
            $query->where($fields[$index], '=', $values[$index]);
        }
    }
}
