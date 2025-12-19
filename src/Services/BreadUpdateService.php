<?php

namespace TCG\Voyager\Services;

use Illuminate\Http\Request;
use TCG\Voyager\Events\BreadDataUpdated;
use TCG\Voyager\Models\DataType;

class BreadUpdateService
{
    public function __construct(
        protected BreadValidationService $validationService,
        protected BreadWriteService $writeService,
        protected BreadRedirectService $redirectService,
        protected BreadDataResolverService $dataResolverService,
        protected BreadCleanupService $cleanupService
    ) {
    }

    public function update(Request $request, string $slug, DataType $dataType, $id)
    {
        // Compatibility with Model binding.
        $resolvedId = $id instanceof \Illuminate\Database\Eloquent\Model ? $id->{$id->getKeyName()} : $id;

        $data = $this->dataResolverService->findOrFail($dataType, $resolvedId, true);

        $this->validationService
            ->validateBread($request->all(), $dataType->editRows, $dataType->name, (int) $resolvedId)
            ->validate();

        $toRemove = $dataType->editRows->where('type', 'image')
            ->filter(function ($item) use ($request) {
                return $request->hasFile($item->field);
            });

        $originalData = clone $data;

        $this->writeService->persist($request, $slug, $dataType->editRows, $data);

        $this->cleanupService->deleteBreadImages($originalData, $toRemove);

        event(new BreadDataUpdated($dataType, $data));

        $redirect = $this->redirectService->resolveAfterSave(
            $request,
            $request->input('redirect_to'),
            $dataType,
            auth()->user()->can('browse', app($dataType->model_name))
        );

        return $redirect->with([
            'message' => __('voyager::generic.successfully_updated')." {$dataType->getTranslatedAttribute('display_name_singular')}",
            'alert-type' => 'success',
        ]);
    }
}

