<?php

namespace TCG\Voyager\Services\Bread;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use TCG\Voyager\Models\DataType;

class BreadReadViewService
{
    public function __construct(
        protected BreadDataResolverService $dataResolverService,
        protected BreadRelationshipViewService $relationshipViewService
    )
    {
    }

    public function buildReadViewData(Request $request, DataType $dataType, $id): array
    {
        $isSoftDeleted = false;

        if (strlen($dataType->model_name) != 0) {
            $dataTypeContent = $this->dataResolverService->findOrFail($dataType, $id, true);
            if ($dataTypeContent->deleted_at) {
                $isSoftDeleted = true;
            }
        } else {
            $dataTypeContent = DB::table($dataType->name)->where('id', $id)->first();
        }

        $dataTypeContent = $this->relationshipViewService->resolveRelations($dataTypeContent, $dataType);
        $this->relationshipViewService->removeRelationshipField($dataType, 'read');

        $isModelTranslatable = is_bread_translatable($dataTypeContent);
        $this->relationshipViewService->eagerLoadRelations($dataTypeContent, $dataType, 'read', $isModelTranslatable);

        return compact('dataType', 'dataTypeContent', 'isModelTranslatable', 'isSoftDeleted');
    }
}
