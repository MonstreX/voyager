<?php

namespace TCG\Voyager\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use TCG\Voyager\Facades\Voyager;

class BreadDataResolverService
{
    public function getDataTypeBySlug(string $slug)
    {
        return Voyager::model('DataType')->where('slug', '=', $slug)->first();
    }

    public function newModelInstance($dataType): ?Model
    {
        if (!$dataType || empty($dataType->model_name)) {
            return null;
        }

        return app($dataType->model_name);
    }

    public function modelUsesSoftDeletes(Model $model): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($model), true);
    }

    public function query(Model $model, $dataType, bool $withTrashed = false): Builder
    {
        $query = $model->query();

        if ($withTrashed && $this->modelUsesSoftDeletes($model)) {
            $query = $query->withTrashed();
        }

        if ($dataType && !empty($dataType->scope) && method_exists($model, 'scope'.ucfirst($dataType->scope))) {
            $query = $query->{$dataType->scope}();
        }

        return $query;
    }

    public function findOrFail($dataType, $id, bool $withTrashed = false)
    {
        $model = $this->newModelInstance($dataType);
        if (!$model) {
            return null;
        }

        return $this->query($model, $dataType, $withTrashed)->findOrFail($id);
    }
}

