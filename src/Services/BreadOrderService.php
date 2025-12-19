<?php

namespace TCG\Voyager\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use TCG\Voyager\Facades\Voyager;

class BreadOrderService
{
    public function __construct(protected BreadDataResolverService $dataResolverService)
    {
    }

    public function buildOrderViewData(string $slug)
    {
        $dataType = $this->dataResolverService->getDataTypeBySlug($slug);

        if (empty($dataType->order_column) || empty($dataType->order_display_column)) {
            return [
                'dataType' => $dataType,
                'invalid' => true,
                'results' => null,
                'display_column' => null,
                'dataRow' => null,
            ];
        }

        /** @var Model $model */
        $model = app($dataType->model_name);
        $query = $this->dataResolverService->query($model, $dataType, true);
        $results = $query->orderBy($dataType->order_column, $dataType->order_direction)->get();

        $display_column = $dataType->order_display_column;
        $dataRow = Voyager::model('DataRow')
            ->whereDataTypeId($dataType->id)
            ->whereField($display_column)
            ->first();

        return compact('dataType', 'results', 'display_column', 'dataRow') + ['invalid' => false];
    }

    public function updateOrder(string $slug, Request $request): void
    {
        $dataType = $this->dataResolverService->getDataTypeBySlug($slug);

        /** @var Model $model */
        $model = app($dataType->model_name);

        $order = json_decode($request->input('order'));
        $column = $dataType->order_column;

        foreach ($order as $key => $item) {
            $record = $this->dataResolverService->query($model, $dataType, true)->findOrFail($item->id);
            $record->$column = ($key + 1);
            $record->save();
        }
    }
}

