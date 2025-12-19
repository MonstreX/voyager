<?php

namespace TCG\Voyager\Services;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Models\DataType;

class BreadBrowseService
{
    public function __construct(
        protected BrowseFilterService $browseFilterService,
        protected BreadDataResolverService $dataResolverService
    ) {
    }

    public function buildBrowseViewData(Request $request, string $slug): array
    {
        $dataType = $this->dataResolverService->getDataTypeBySlug($slug);
        return $this->buildBrowseViewDataForDataType($request, $slug, $dataType);
    }

    public function buildBrowseViewDataForDataType(Request $request, string $slug, DataType $dataType): array
    {

        $getter = $dataType->server_side ? 'paginate' : 'get';
        $search = (object) [
            'value' => $request->get('s'),
            'key' => $request->get('key'),
            'filter' => $request->get('filter'),
        ];

        $filters = $this->browseFilterService->resolve($request, $slug);

        $searchNames = [];
        if ($dataType->server_side) {
            $searchNames = $dataType->browseRows->mapWithKeys(function ($row) {
                return [$row['field'] => $row->getTranslatedAttribute('display_name')];
            });
        }

        $orderBy = $request->get('order_by', $dataType->order_column);
        $sortOrder = $request->get('sort_order', $dataType->order_direction);
        $usesSoftDeletes = false;
        $showSoftDeleted = false;

        $model = false;

        if (strlen($dataType->model_name) != 0) {
            $model = $this->dataResolverService->newModelInstance($dataType);

            $query = $model::select($dataType->name.'.*');

            if ($dataType->scope && $dataType->scope != '' && method_exists($model, 'scope'.ucfirst($dataType->scope))) {
                $query->{$dataType->scope}();
            }

            if ($model && $this->dataResolverService->modelUsesSoftDeletes($model) && Auth::user()->can('delete', app($dataType->model_name))) {
                $usesSoftDeletes = true;

                if ($request->get('showSoftDeleted')) {
                    $showSoftDeleted = true;
                    $query = $query->withTrashed();
                }
            }

            $this->removeRelationshipField($dataType, 'browse');

            $this->applySearch($query, $dataType, $search);

            $this->browseFilterService->apply($query, $filters);

            $row = $dataType->rows->where('field', $orderBy)->firstWhere('type', 'relationship');
            if ($orderBy && (in_array($orderBy, $dataType->fields()) || !empty($row))) {
                $querySortOrder = (!empty($sortOrder)) ? $sortOrder : 'desc';
                if (!empty($row)) {
                    $query->select([
                        $dataType->name.'.*',
                        'joined.'.$row->details->label.' as '.$orderBy,
                    ])->leftJoin(
                        $row->details->table.' as joined',
                        $dataType->name.'.'.$row->details->column,
                        'joined.'.$row->details->key
                    );
                }

                $dataTypeContent = call_user_func([
                    $query->orderBy($orderBy, $querySortOrder),
                    $getter,
                ]);
            } elseif ($model->timestamps) {
                $dataTypeContent = call_user_func([$query->latest($model::CREATED_AT), $getter]);
            } else {
                $dataTypeContent = call_user_func([$query->orderBy($model->getKeyName(), 'DESC'), $getter]);
            }
        } else {
            $dataTypeContent = call_user_func([DB::table($dataType->name), $getter]);
        }

        $isModelTranslatable = is_bread_translatable($model);
        $this->eagerLoadRelations($dataTypeContent, $dataType, 'browse', $isModelTranslatable);

        $isServerSide = isset($dataType->server_side) && $dataType->server_side;
        $defaultSearchKey = $dataType->default_search_key ?? null;

        $actions = $this->resolveActions($dataType, $dataTypeContent);

        $showCheckboxColumn = $this->resolveShowCheckboxColumn($dataType, $actions);

        $orderColumn = [];
        if ($orderBy) {
            $index = $dataType->browseRows->where('field', $orderBy)->keys()->first() + ($showCheckboxColumn ? 1 : 0);
            $orderColumn = [[$index, $sortOrder ?? 'desc']];
        }

        $sortableColumns = $this->getSortableColumns($dataType->browseRows);

        return compact(
            'actions',
            'dataType',
            'dataTypeContent',
            'isModelTranslatable',
            'search',
            'orderBy',
            'orderColumn',
            'sortableColumns',
            'sortOrder',
            'searchNames',
            'isServerSide',
            'defaultSearchKey',
            'usesSoftDeletes',
            'showSoftDeleted',
            'showCheckboxColumn',
            'filters'
        );
    }

    protected function applySearch($query, DataType $dataType, object $search): void
    {
        if ($search->value == '' || !$search->key || !$search->filter) {
            return;
        }

        $searchFilter = ($search->filter == 'equals') ? '=' : 'LIKE';
        $searchValue = ($search->filter == 'equals') ? $search->value : '%'.$search->value.'%';

        $searchField = $dataType->name.'.'.$search->key;

        if ($row = $this->findSearchableRelationshipRow($dataType->rows->where('type', 'relationship'), $search->key)) {
            $query->whereIn(
                $searchField,
                $row->details->model::where($row->details->label, $searchFilter, $searchValue)->pluck('id')->toArray()
            );
            return;
        }

        if ($dataType->browseRows->pluck('field')->contains($search->key)) {
            $query->where($searchField, $searchFilter, $searchValue);
        }
    }

    protected function resolveActions($dataType, $dataTypeContent): array
    {
        $actions = [];
        $first = null;

        if ($dataTypeContent instanceof LengthAwarePaginator) {
            $first = $dataTypeContent->first();
        } elseif (is_object($dataTypeContent) && method_exists($dataTypeContent, 'first')) {
            $first = $dataTypeContent->first();
        }

        if (!$first) {
            return [];
        }

        foreach (Voyager::actions() as $action) {
            $action = new $action($dataType, $first);
            if ($action->shouldActionDisplayOnDataType()) {
                $actions[] = $action;
            }
        }

        return $actions;
    }

    protected function resolveShowCheckboxColumn($dataType, array $actions): bool
    {
        if (Auth::user()->can('delete', app($dataType->model_name))) {
            return true;
        }

        foreach ($actions as $action) {
            if (method_exists($action, 'massAction')) {
                return true;
            }
        }

        return false;
    }

    protected function findSearchableRelationshipRow($relationshipRows, $searchKey)
    {
        return $relationshipRows->filter(function ($item) use ($searchKey) {
            if ($item->details->column != $searchKey) {
                return false;
            }
            if ($item->details->type != 'belongsTo') {
                return false;
            }

            return !$this->relationIsUsingAccessorAsLabel($item->details);
        })->first();
    }

    protected function getSortableColumns($rows): array
    {
        return $rows->filter(function ($item) {
            if ($item->type != 'relationship') {
                return true;
            }
            if ($item->details->type != 'belongsTo') {
                return false;
            }

            return !$this->relationIsUsingAccessorAsLabel($item->details);
        })
            ->pluck('field')
            ->toArray();
    }

    protected function relationIsUsingAccessorAsLabel($details): bool
    {
        return in_array($details->label, app($details->model)->additional_attributes ?? []);
    }

    protected function removeRelationshipField(DataType $dataType, string $breadType = 'browse'): void
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

    protected function eagerLoadRelations($dataTypeContent, DataType $dataType, string $action, bool $isModelTranslatable): void
    {
        if (!config('voyager.multilingual.enabled')) {
            return;
        }

        if ($isModelTranslatable && is_object($dataTypeContent) && method_exists($dataTypeContent, 'load')) {
            $dataTypeContent->load('translations');
        }

        $rows = $dataType->{$action.'Rows'} ?? null;
        if ($rows && method_exists($rows, 'load')) {
            $rows->load('translations');
        }
    }
}
