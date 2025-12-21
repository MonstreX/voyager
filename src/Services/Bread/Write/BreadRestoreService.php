<?php

namespace TCG\Voyager\Services\Bread\Write;

use Illuminate\Http\Request;
use TCG\Voyager\Events\BreadDataRestored;
use TCG\Voyager\Models\DataType;
use TCG\Voyager\Services\Bread\Support\BreadDataResolverService;

class BreadRestoreService
{
    public function __construct(protected BreadDataResolverService $dataResolverService)
    {
    }

    public function restore(Request $request, string $slug, DataType $dataType, $id)
    {
        $model = app($dataType->model_name);
        $data = $this->dataResolverService->findOrFail($dataType, $id, true);

        $displayName = $dataType->getTranslatedAttribute('display_name_singular');

        $res = $data->restore($id);
        $flash = $res
            ? [
                'message' => __('voyager::generic.successfully_restored')." {$displayName}",
                'alert-type' => 'success',
            ]
            : [
                'message' => __('voyager::generic.error_restoring')." {$displayName}",
                'alert-type' => 'error',
            ];

        if ($res) {
            event(new BreadDataRestored($dataType, $data));
        }

        return redirect()->route("voyager.{$dataType->slug}.index")->with($flash);
    }
}
