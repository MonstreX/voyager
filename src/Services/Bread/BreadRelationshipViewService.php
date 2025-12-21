<?php

namespace TCG\Voyager\Services\Bread;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use TCG\Voyager\Models\DataType;

class BreadRelationshipViewService
{
    public function removeRelationshipField(DataType $dataType, string $breadType = 'browse'): void
    {
        $forgetKeys = [];
        foreach ($dataType->{$breadType.'Rows'} as $key => $row) {
            if ($row->type == 'relationship' && $row->details->type == 'belongsTo') {
                $relationshipField = @$row->details->column;
                $keyInCollection = key($dataType->{$breadType.'Rows'}->where('field', '=', $relationshipField)->toArray());
                $forgetKeys[] = $keyInCollection;
            }
        }

        foreach ($forgetKeys as $forgetKey) {
            $dataType->{$breadType.'Rows'}->forget($forgetKey);
        }

        $dataType->{$breadType.'Rows'} = $dataType->{$breadType.'Rows'}->values();
    }

    /**
     * Replace relationships' keys for labels and create READ links if a slug is provided.
     *
     * @param  mixed $dataTypeContent Can be either an eloquent Model, Collection or LengthAwarePaginator instance.
     */
    public function resolveRelations($dataTypeContent, DataType $dataType)
    {
        if ($dataTypeContent instanceof LengthAwarePaginator) {
            $dataTypeCollection = $dataTypeContent->getCollection();
        } elseif ($dataTypeContent instanceof Model) {
            return $dataTypeContent;
        } else {
            $dataTypeCollection = $dataTypeContent;
        }

        return $dataTypeContent instanceof LengthAwarePaginator ? $dataTypeContent->setCollection($dataTypeCollection) : $dataTypeCollection;
    }

    public function eagerLoadRelations($dataTypeContent, DataType $dataType, string $action, bool $isModelTranslatable): void
    {
        if (!config('voyager.multilingual.enabled')) {
            return;
        }

        if ($isModelTranslatable && $dataTypeContent instanceof Model) {
            $dataTypeContent->load('translations');
        }

        $dataType->{$action.'Rows'}->load('translations');
    }
}
