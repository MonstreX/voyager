<?php

namespace TCG\Voyager\Http\Controllers;

use Illuminate\Http\Request;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Services\Bread\Support\BreadDataResolverService;
use TCG\Voyager\Services\Bread\Write\BreadCleanupService;
use TCG\Voyager\Services\Bread\Write\BreadValidationService;
use TCG\Voyager\Services\Bread\Write\BreadWriteService;

class VoyagerRoleController extends VoyagerBaseController
{
    // POST BR(E)AD
    public function update(Request $request, $id, BreadDataResolverService $breadDataResolverService, BreadCleanupService $breadCleanupService)
    {
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Check permission
        $this->authorize('edit', app($dataType->model_name));

        //Validate fields
        app(BreadValidationService::class)->validateBread($request->all(), $dataType->editRows, $dataType->name, (int) $id)->validate();

        $data = call_user_func([$dataType->model_name, 'findOrFail'], $id);
        app(BreadWriteService::class)->persist($request, $slug, $dataType->editRows, $data);

        $data->permissions()->sync($request->input('permissions', []));

        return redirect()
            ->route("voyager.{$dataType->slug}.index")
            ->with([
                'message'    => __('voyager::generic.successfully_updated')." {$dataType->getTranslatedAttribute('display_name_singular')}",
                'alert-type' => 'success',
            ]);
    }

    // POST BRE(A)D
    public function store(Request $request, BreadDataResolverService $breadDataResolverService)
    {
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Check permission
        $this->authorize('add', app($dataType->model_name));

        //Validate fields
        app(BreadValidationService::class)->validateBread($request->all(), $dataType->addRows)->validate();

        $data = new $dataType->model_name();
        app(BreadWriteService::class)->persist($request, $slug, $dataType->addRows, $data);

        $data->permissions()->sync($request->input('permissions', []));

        return redirect()
            ->route("voyager.{$dataType->slug}.index")
            ->with([
                'message'    => __('voyager::generic.successfully_added_new')." {$dataType->getTranslatedAttribute('display_name_singular')}",
                'alert-type' => 'success',
            ]);
    }
}
