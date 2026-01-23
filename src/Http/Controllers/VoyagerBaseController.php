<?php

namespace TCG\Voyager\Http\Controllers;

use Illuminate\Http\Request;
use TCG\Voyager\Events\BreadDataRestored;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Http\Controllers\Traits\BreadRelationshipParser;
use TCG\Voyager\Services\Bread\Browse\BreadBrowseService;
use TCG\Voyager\Services\Bread\Media\BreadRemoveMediaService;
use TCG\Voyager\Services\Bread\Media\BreadReorderMediaService;
use TCG\Voyager\Services\Bread\Relations\BreadRelationService;
use TCG\Voyager\Services\Bread\Read\BreadReadViewService;
use TCG\Voyager\Services\Bread\Support\BreadActionService;
use TCG\Voyager\Services\Bread\Support\BreadDataResolverService;
use TCG\Voyager\Services\Bread\Support\BreadFormViewService;
use TCG\Voyager\Services\Bread\Support\BreadOrderService;
use TCG\Voyager\Services\Bread\Write\BreadCleanupService;
use TCG\Voyager\Services\Bread\Write\BreadDestroyActionService;
use TCG\Voyager\Services\Bread\Write\BreadDestroyService;
use TCG\Voyager\Services\Bread\Write\BreadRestoreService;
use TCG\Voyager\Services\Bread\Write\BreadStoreService;
use TCG\Voyager\Services\Bread\Write\BreadUpdateService;

class VoyagerBaseController extends Controller
{
    use BreadRelationshipParser;

    private function resolveView(string $slug, string $defaultView, string $suffix): string
    {
        $customView = "voyager::$slug.$suffix";
        return view()->exists($customView) ? $customView : $defaultView;
    }

    private function applyColWidths($rows, int $defaultWidth = 100): void
    {
        foreach ($rows as $key => $row) {
            $rows[$key]['col_width'] = isset($row->details->width) ? $row->details->width : $defaultWidth;
        }
    }

    //***************************************
    //               ____
    //              |  _ \
    //              | |_) |
    //              |  _ <
    //              | |_) |
    //              |____/
    //
    //      Browse our Data Type (B)READ
    //
    //****************************************

    public function index(Request $request, BreadBrowseService $breadBrowseService, BreadDataResolverService $breadDataResolverService)
    {
        $slug = $this->getSlug($request);
        $dataType = $breadDataResolverService->getDataTypeBySlug($slug);
        $this->authorize('browse', app($dataType->model_name));

        $viewData = $breadBrowseService->buildBrowseViewDataForDataType($request, $slug, $dataType);

        $view = $this->resolveView($slug, 'voyager::bread.browse', 'browse');

        return Voyager::view($view, $viewData);
    }

    //***************************************
    //                _____
    //               |  __ \
    //               | |__) |
    //               |  _  /
    //               | | \ \
    //               |_|  \_\
    //
    //  Read an item of our Data Type B(R)EAD
    //
    //****************************************

    public function show(Request $request, $id, BreadDataResolverService $breadDataResolverService)
    {
        $slug = $this->getSlug($request);

        $dataType = $breadDataResolverService->getDataTypeBySlug($slug);
        $viewData = app(BreadReadViewService::class)->buildReadViewData($request, $dataType, $id);
        $dataTypeContent = $viewData['dataTypeContent'];

        $this->authorize('read', $dataTypeContent);

        $view = $this->resolveView($slug, 'voyager::bread.read', 'read');

        return Voyager::view($view, $viewData);
    }

    //***************************************
    //                ______
    //               |  ____|
    //               | |__
    //               |  __|
    //               | |____
    //               |______|
    //
    //  Edit an item of our Data Type BR(E)AD
    //
    //****************************************

    public function edit(Request $request, $id, BreadDataResolverService $breadDataResolverService)
    {
        $slug = $this->getSlug($request);

        $dataType = $breadDataResolverService->getDataTypeBySlug($slug);
        $viewData = app(BreadFormViewService::class)->buildEditViewData($request, $dataType, $id);
        $dataTypeContent = $viewData['dataTypeContent'];

        $this->authorize('edit', $dataTypeContent);

        $view = $this->resolveView($slug, 'voyager::bread.edit-add', 'edit-add');

        return Voyager::view($view, $viewData);
    }

    // POST BR(E)AD
    public function update(Request $request, $id, BreadDataResolverService $breadDataResolverService, BreadCleanupService $breadCleanupService)
    {
        $slug = $this->getSlug($request);

        $dataType = $breadDataResolverService->getDataTypeBySlug($slug);

        // Compatibility with Model binding.
        $id = $id instanceof \Illuminate\Database\Eloquent\Model ? $id->{$id->getKeyName()} : $id;

        $data = $breadDataResolverService->findOrFail($dataType, $id, true);

        // Check permission
        $this->authorize('edit', $data);

        return app(BreadUpdateService::class)->update($request, $slug, $dataType, $id);
    }

    //***************************************
    //
    //                   /\
    //                  /  \
    //                 / /\ \
    //                / ____ \
    //               /_/    \_\
    //
    //
    // Add a new item of our Data Type BRE(A)D
    //
    //****************************************

    public function create(Request $request, BreadDataResolverService $breadDataResolverService)
    {
        $slug = $this->getSlug($request);

        $dataType = $breadDataResolverService->getDataTypeBySlug($slug);

        // Check permission
        $this->authorize('add', app($dataType->model_name));
        $viewData = app(BreadFormViewService::class)->buildCreateViewData($request, $dataType);

        $view = $this->resolveView($slug, 'voyager::bread.edit-add', 'edit-add');

        return Voyager::view($view, $viewData);
    }

    /**
     * POST BRE(A)D - Store data.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, BreadDataResolverService $breadDataResolverService)
    {
        $slug = $this->getSlug($request);

        $dataType = $breadDataResolverService->getDataTypeBySlug($slug);

        // Check permission
        $this->authorize('add', app($dataType->model_name));

        return app(BreadStoreService::class)->store($request, $slug, $dataType);
    }

    //***************************************
    //                _____
    //               |  __ \
    //               | |  | |
    //               | |  | |
    //               | |__| |
    //               |_____/
    //
    //         Delete an item BREA(D)
    //
    //****************************************

    public function destroy(Request $request, $id, BreadDestroyService $breadDestroyService, BreadDataResolverService $breadDataResolverService)
    {
        $slug = $this->getSlug($request);

        $dataType = $breadDataResolverService->getDataTypeBySlug($slug);

        return app(BreadDestroyActionService::class)->destroy($request, $dataType, $id);
    }

    public function restore(Request $request, $id, BreadDataResolverService $breadDataResolverService)
    {
        $slug = $this->getSlug($request);

        $dataType = $breadDataResolverService->getDataTypeBySlug($slug);

        // Check permission
        $model = app($dataType->model_name);
        $this->authorize('delete', $model);

        return app(BreadRestoreService::class)->restore($request, $slug, $dataType, $id);
    }

    //***************************************
    //
    //  Delete uploaded file
    //
    //****************************************

    public function remove_media(Request $request, BreadRemoveMediaService $breadRemoveMediaService)
    {
        return $breadRemoveMediaService->removeMedia($request);
    }

    public function reorder_media(Request $request, BreadReorderMediaService $breadReorderMediaService)
    {
        return $breadReorderMediaService->reorderMedia($request);
    }

    /**
     * Remove translations, images and files related to a BREAD item.
     *
     * @param \Illuminate\Database\Eloquent\Model $dataType
     * @param \Illuminate\Database\Eloquent\Model $data
     *
     * @return void
     */
    protected function cleanup($dataType, $data)
    {
        app(BreadCleanupService::class)->cleanup($dataType, $data);
    }

    /**
     * Delete all images related to a BREAD item.
     *
     * @param \Illuminate\Database\Eloquent\Model $data
     * @param \Illuminate\Database\Eloquent\Model $rows
     *
     * @return void
     */
    public function deleteBreadImages($data, $rows, $single_image = null)
    {
        app(BreadCleanupService::class)->deleteBreadImages($data, $rows, $single_image);
    }

    /**
     * Order BREAD items.
     *
     * @param string $table
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function order(Request $request, BreadOrderService $breadOrderService)
    {
        $slug = $this->getSlug($request);

        $viewData = $breadOrderService->buildOrderViewData($slug);
        $dataType = $viewData['dataType'];

        // Check permission
        $this->authorize('edit', app($dataType->model_name));

        if (!empty($viewData['invalid'])) {
            return redirect()
            ->route("voyager.{$dataType->slug}.index")
            ->with([
                'message'    => __('voyager::bread.ordering_not_set'),
                'alert-type' => 'error',
            ]);
        }

        $view = $this->resolveView($slug, 'voyager::bread.order', 'order');

        return Voyager::view($view, $viewData);
    }

    public function update_order(Request $request, BreadOrderService $breadOrderService)
    {
        $slug = $this->getSlug($request);

        $dataType = $breadOrderService->buildOrderViewData($slug)['dataType'];

        // Check permission
        $this->authorize('edit', app($dataType->model_name));

        $breadOrderService->updateOrder($slug, $request);
    }

    public function action(Request $request, BreadActionService $breadActionService, BreadDataResolverService $breadDataResolverService)
    {
        $slug = $this->getSlug($request);
        $dataType = $breadDataResolverService->getDataTypeBySlug($slug);

        return $breadActionService->run($request, $dataType);
    }

    /**
     * Get BREAD relations data.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function relation(Request $request, BreadRelationService $breadRelationService, BreadDataResolverService $breadDataResolverService)
    {
        $slug = $this->getSlug($request);
        $dataType = $breadDataResolverService->getDataTypeBySlug($slug);
        return $breadRelationService->relation($request, $dataType);
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

    protected function getSortableColumns($rows)
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

    protected function relationIsUsingAccessorAsLabel($details)
    {
        return in_array($details->label, app($details->model)->additional_attributes ?? []);
    }
}
