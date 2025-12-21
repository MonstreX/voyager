<?php

namespace TCG\Voyager\Services\Bread\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use TCG\Voyager\Models\DataType;
use TCG\Voyager\Services\Bread\Relations\BreadRelationshipViewService;

class BreadFormViewService
{
    public function __construct(
        protected BreadDataResolverService $dataResolverService,
        protected BreadRelationshipViewService $relationshipViewService
    )
    {
    }

    public function buildEditViewData(Request $request, DataType $dataType, $id): array
    {
        if (strlen($dataType->model_name) != 0) {
            $dataTypeContent = $this->dataResolverService->findOrFail($dataType, $id, true);
        } else {
            $dataTypeContent = DB::table($dataType->name)->where('id', $id)->first();
        }

        $this->applyColWidths($dataType->editRows);
        $this->relationshipViewService->removeRelationshipField($dataType, 'edit');

        $isModelTranslatable = is_bread_translatable($dataTypeContent);
        $this->relationshipViewService->eagerLoadRelations($dataTypeContent, $dataType, 'edit', $isModelTranslatable);

        return compact('dataType', 'dataTypeContent', 'isModelTranslatable');
    }

    public function buildCreateViewData(Request $request, DataType $dataType): array
    {
        $dataTypeContent = (strlen($dataType->model_name) != 0)
            ? $this->dataResolverService->newModelInstance($dataType)
            : false;

        $this->applyColWidths($dataType->addRows);
        $this->relationshipViewService->removeRelationshipField($dataType, 'add');

        $isModelTranslatable = is_bread_translatable($dataTypeContent);
        if ($dataTypeContent) {
            $this->relationshipViewService->eagerLoadRelations($dataTypeContent, $dataType, 'add', $isModelTranslatable);
        } else {
            $dataType->addRows->load('translations');
        }

        return compact('dataType', 'dataTypeContent', 'isModelTranslatable');
    }

    private function applyColWidths($rows, int $defaultWidth = 100): void
    {
        foreach ($rows as $key => $row) {
            $rows[$key]['col_width'] = isset($row->details->width) ? $row->details->width : $defaultWidth;
        }
    }
}
