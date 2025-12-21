<?php

namespace TCG\Voyager\Services\Bread\Write;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use TCG\Voyager\Events\BreadDataAdded;
use TCG\Voyager\Models\DataType;
use TCG\Voyager\Services\Bread\Support\BreadDataResolverService;

class BreadStoreService
{
    public function __construct(
        protected BreadValidationService $validationService,
        protected BreadWriteService $writeService,
        protected BreadRedirectService $redirectService,
        protected BreadDataResolverService $dataResolverService
    ) {
    }

    /**
     * Store a new BREAD record.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function store(Request $request, string $slug, DataType $dataType)
    {
        $this->validationService
            ->validateBread($request->all(), $dataType->addRows)
            ->validate();

        $data = $this->writeService->persist(
            $request,
            $slug,
            $dataType->addRows,
            $this->dataResolverService->newModelInstance($dataType)
        );

        event(new BreadDataAdded($dataType, $data));

        if ($request->has('_tagging')) {
            return response()->json(['success' => true, 'data' => $data]);
        }

        $redirect = $this->redirectService->resolveAfterSave(
            $request,
            $request->input('redirect_to'),
            $dataType,
            auth()->user()->can('browse', $data)
        );

        return $redirect->with([
            'message' => __('voyager::generic.successfully_added_new')." {$dataType->getTranslatedAttribute('display_name_singular')}",
            'alert-type' => 'success',
        ]);
    }
}
