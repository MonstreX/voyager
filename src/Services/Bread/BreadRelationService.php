<?php

namespace TCG\Voyager\Services\Bread;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BreadRelationService
{
    public function relation(Request $request, $dataType)
    {
        $page = (int) $request->input('page', 1);
        $perPage = 50;
        $search = $request->input('search', false);

        $method = $request->input('method', 'add');

        $model = app($dataType->model_name);
        if ($method != 'add') {
            $model = $model->find($request->input('id'));
        }

        Gate::authorize($method, $model);

        $rows = $dataType->{$method.'Rows'};
        foreach ($rows as $row) {
            if ($row->field !== $request->input('type')) {
                continue;
            }

            $options = $row->details;
            $relatedModel = app($options->model);
            $skip = $perPage * max(0, $page - 1);

            $additionalAttributes = $relatedModel->additional_attributes ?? [];

            $relationshipOptions = $relatedModel;

            if (isset($options->scope) && $options->scope != '' && method_exists($relatedModel, 'scope'.ucfirst($options->scope))) {
                $relationshipOptions = $relatedModel->{$options->scope}();
            }

            $totalCount = null;

            if ($search) {
                if (in_array($options->label, $additionalAttributes)) {
                    $collection = method_exists($relationshipOptions, 'get')
                        ? $relationshipOptions->get()
                        : $relationshipOptions;

                    $relationshipOptions = $collection->filter(function ($item) use ($search, $options) {
                        return stripos($item->{$options->label}, $search) !== false;
                    });

                    $totalCount = $relationshipOptions->count();
                } else {
                    $query = $relationshipOptions->where($options->label, 'LIKE', '%' . $search . '%');
                    $totalCount = $query->count();
                    $relationshipOptions = $query;
                }
            } else {
                if (method_exists($relationshipOptions, 'count')) {
                    $totalCount = $relationshipOptions->count();
                } else {
                    $totalCount = $relationshipOptions->count();
                }
            }

            if (!empty($options->sort->field)) {
                if (method_exists($relationshipOptions, 'orderBy') && !empty($options->sort->direction)) {
                    $relationshipOptions = $relationshipOptions->orderBy($options->sort->field, $options->sort->direction);
                }
            }

            if (method_exists($relationshipOptions, 'skip') && method_exists($relationshipOptions, 'take')) {
                $relationshipOptions = $relationshipOptions->skip($skip)->take($perPage)->get();
            } else {
                $relationshipOptions = $relationshipOptions->get()
                    ->skip($skip)
                    ->take($perPage);
            }

            $results = [];

            if (!$row->required && !$search && $page === 1) {
                $results[] = [
                    'id'   => '',
                    'text' => __('voyager::generic.none'),
                ];
            }

            if (!empty($options->sort->field)) {
                if (!empty($options->sort->direction) && strtolower($options->sort->direction) == 'desc') {
                    $relationshipOptions = $relationshipOptions->sortByDesc($options->sort->field);
                } else {
                    $relationshipOptions = $relationshipOptions->sortBy($options->sort->field);
                }
            }

            foreach ($relationshipOptions as $relationshipOption) {
                $results[] = [
                    'id'   => $relationshipOption->{$options->key},
                    'text' => $relationshipOption->{$options->label},
                ];
            }

            return response()->json([
                'results'    => $results,
                'pagination' => [
                    'more' => ($totalCount > ($skip + $perPage)),
                ],
            ]);
        }

        return response()->json([], 404);
    }
}
