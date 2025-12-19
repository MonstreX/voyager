<?php

namespace TCG\Voyager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use TCG\Voyager\Events\BreadDataAdded;
use TCG\Voyager\Events\BreadDataRestored;
use TCG\Voyager\Events\BreadDataUpdated;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Http\Controllers\Traits\BreadRelationshipParser;
use TCG\Voyager\Services\BreadBrowseService;
use TCG\Voyager\Services\BreadCleanupService;
use TCG\Voyager\Services\BreadActionService;
use TCG\Voyager\Services\BreadDestroyService;
use TCG\Voyager\Services\BreadRelationService;
use TCG\Voyager\Services\BreadRemoveMediaService;
use TCG\Voyager\Services\BreadDataResolverService;
use TCG\Voyager\Services\BreadOrderService;

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

        $isSoftDeleted = false;

        if (strlen($dataType->model_name) != 0) {
            $dataTypeContent = $breadDataResolverService->findOrFail($dataType, $id, true);
            if ($dataTypeContent->deleted_at) {
                $isSoftDeleted = true;
            }
        } else {
            // If Model doest exist, get data from table name
            $dataTypeContent = DB::table($dataType->name)->where('id', $id)->first();
        }

        // Replace relationships' keys for labels and create READ links if a slug is provided.
        $dataTypeContent = $this->resolveRelations($dataTypeContent, $dataType);

        // If a column has a relationship associated with it, we do not want to show that field
        $this->removeRelationshipField($dataType, 'read');

        // Check permission
        $this->authorize('read', $dataTypeContent);

        // Check if BREAD is Translatable
        $isModelTranslatable = is_bread_translatable($dataTypeContent);

        // Eagerload Relations
        $this->eagerLoadRelations($dataTypeContent, $dataType, 'read', $isModelTranslatable);

        $view = $this->resolveView($slug, 'voyager::bread.read', 'read');

        return Voyager::view($view, compact('dataType', 'dataTypeContent', 'isModelTranslatable', 'isSoftDeleted'));
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

        if (strlen($dataType->model_name) != 0) {
            $dataTypeContent = $breadDataResolverService->findOrFail($dataType, $id, true);
        } else {
            // If Model doest exist, get data from table name
            $dataTypeContent = DB::table($dataType->name)->where('id', $id)->first();
        }

        $this->applyColWidths($dataType->editRows);

        // If a column has a relationship associated with it, we do not want to show that field
        $this->removeRelationshipField($dataType, 'edit');

        // Check permission
        $this->authorize('edit', $dataTypeContent);

        // Check if BREAD is Translatable
        $isModelTranslatable = is_bread_translatable($dataTypeContent);

        // Eagerload Relations
        $this->eagerLoadRelations($dataTypeContent, $dataType, 'edit', $isModelTranslatable);

        $view = $this->resolveView($slug, 'voyager::bread.edit-add', 'edit-add');

        return Voyager::view($view, compact('dataType', 'dataTypeContent', 'isModelTranslatable'));
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

        // Validate fields with ajax
        $val = $this->validateBread($request->all(), $dataType->editRows, $dataType->name, $id)->validate();

        // Get fields with images to remove before updating and make a copy of $data
        $to_remove = $dataType->editRows->where('type', 'image')
            ->filter(function ($item, $key) use ($request) {
                return $request->hasFile($item->field);
            });
        $original_data = clone($data);

        $this->insertUpdateData($request, $slug, $dataType->editRows, $data);

        // Delete Images
        $breadCleanupService->deleteBreadImages($original_data, $to_remove);

        event(new BreadDataUpdated($dataType, $data));

        $redirect = $this->resolveRedirectAfterSave(
            $request,
            $request->input('redirect_to'),
            $dataType,
            auth()->user()->can('browse', app($dataType->model_name))
        );

        return $redirect->with([
            'message'    => __('voyager::generic.successfully_updated')." {$dataType->getTranslatedAttribute('display_name_singular')}",
            'alert-type' => 'success',
        ]);
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

        $dataTypeContent = (strlen($dataType->model_name) != 0)
                            ? $breadDataResolverService->newModelInstance($dataType)
                            : false;

        $this->applyColWidths($dataType->addRows);

        // If a column has a relationship associated with it, we do not want to show that field
        $this->removeRelationshipField($dataType, 'add');

        // Check if BREAD is Translatable
        $isModelTranslatable = is_bread_translatable($dataTypeContent);

        // Eagerload Relations
        $this->eagerLoadRelations($dataTypeContent, $dataType, 'add', $isModelTranslatable);

        $view = $this->resolveView($slug, 'voyager::bread.edit-add', 'edit-add');

        return Voyager::view($view, compact('dataType', 'dataTypeContent', 'isModelTranslatable'));
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

        // Validate fields with ajax
        $val = $this->validateBread($request->all(), $dataType->addRows)->validate();
        $data = $this->insertUpdateData($request, $slug, $dataType->addRows, $breadDataResolverService->newModelInstance($dataType));

        event(new BreadDataAdded($dataType, $data));

        if (!$request->has('_tagging')) {
            $redirect = $this->resolveRedirectAfterSave(
                $request,
                $request->input('redirect_to'),
                $dataType,
                auth()->user()->can('browse', $data)
            );

            return $redirect->with([
                'message'    => __('voyager::generic.successfully_added_new')." {$dataType->getTranslatedAttribute('display_name_singular')}",
                'alert-type' => 'success',
            ]);
        } else {
            return response()->json(['success' => true, 'data' => $data]);
        }
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

        // Init array of IDs
        $ids = [];
        if (empty($id)) {
            // Bulk delete, get IDs from POST
            $ids = explode(',', $request->ids);
        } else {
            // Single item delete, get ID from URL
            $ids[] = $id;
        }

        $affected = $breadDestroyService->destroy($dataType, $ids);

        $displayName = $affected > 1 ? $dataType->getTranslatedAttribute('display_name_plural') : $dataType->getTranslatedAttribute('display_name_singular');

        $data = $affected
            ? [
                'message'    => __('voyager::generic.successfully_deleted')." {$displayName}",
                'alert-type' => 'success',
            ]
            : [
                'message'    => __('voyager::generic.error_deleting')." {$displayName}",
                'alert-type' => 'error',
            ];

        return redirect()->route("voyager.{$dataType->slug}.index")->with($data);
    }

    public function restore(Request $request, $id, BreadDataResolverService $breadDataResolverService)
    {
        $slug = $this->getSlug($request);

        $dataType = $breadDataResolverService->getDataTypeBySlug($slug);

        // Check permission
        $model = app($dataType->model_name);
        $this->authorize('delete', $model);

        $data = $breadDataResolverService->findOrFail($dataType, $id, true);

        $displayName = $dataType->getTranslatedAttribute('display_name_singular');

        $res = $data->restore($id);
        $data = $res
            ? [
                'message'    => __('voyager::generic.successfully_restored')." {$displayName}",
                'alert-type' => 'success',
            ]
            : [
                'message'    => __('voyager::generic.error_restoring')." {$displayName}",
                'alert-type' => 'error',
            ];

        if ($res) {
            event(new BreadDataRestored($dataType, $data));
        }

        return redirect()->route("voyager.{$dataType->slug}.index")->with($data);
    }

    protected function resolveRedirectAfterSave(Request $request, ?string $redirectUrl, $dataType, bool $canBrowse)
    {
        if ($this->isSafeRedirectUrl($request, $redirectUrl)) {
            return redirect()->to($redirectUrl);
        }

        if ($canBrowse) {
            return redirect()->route("voyager.{$dataType->slug}.index");
        }

        return redirect()->back();
    }

    protected function isSafeRedirectUrl(Request $request, ?string $redirectUrl): bool
    {
        if (!$redirectUrl || !is_string($redirectUrl)) {
            return false;
        }

        $parts = @parse_url($redirectUrl);

        if ($parts === false) {
            return false;
        }

        if (isset($parts['scheme']) && !in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return false;
        }

        if (isset($parts['host']) && $parts['host'] !== $request->getHost()) {
            return false;
        }

        return true;
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
