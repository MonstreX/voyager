<?php

namespace TCG\Voyager\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Services\Bread\BreadValidationService;

class ModelInlineUpdateService
{
    private const FORBIDDEN_FIELDS = [
        'id',
        'created_at',
        'updated_at',
        'deleted_at',
        'password',
        'remember_token',
    ];

    public function __construct(protected BreadValidationService $validationService)
    {
    }

    public function updateField(Request $request, $id)
    {
        $field = (string) $request->input('field', '');
        $slug = (string) $request->input('slug', '');

        if ($slug === '' || $field === '') {
            return response()->json(['status' => 'error', 'message' => 'Invalid parameters'], 400);
        }

        if (in_array($field, self::FORBIDDEN_FIELDS, true)) {
            return response()->json(['status' => 'error', 'message' => 'Field is not editable'], 400);
        }

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();
        if (!$dataType) {
            return response()->json(['status' => 'error', 'message' => 'DataType not found'], 404);
        }

        $row = $dataType->editRows->firstWhere('field', $field);
        if (!$row) {
            return response()->json(['status' => 'error', 'message' => 'Field is not editable'], 400);
        }

        $modelClass = $dataType->model_name;
        $model = app($modelClass)->findOrFail($id);

        $this->authorizeModelEdit($model);

        $value = $request->input('value');

        $this->validationService
            ->validateBread([$field => $value], collect([$row]), $dataType->name, (int) $id)
            ->validate();

        $model->{$field} = $value;
        $model->save();

        return response()->json([
            'status' => 'success',
            'message' => __('voyager::generic.successfully_updated'),
            'data' => [
                'field' => $field,
                'value' => $model->{$field},
            ],
        ]);
    }

    private function authorizeModelEdit($model): void
    {
        Gate::authorize('edit', $model);
    }
}
