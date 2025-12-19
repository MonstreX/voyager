<?php

namespace TCG\Voyager\Services;

use Illuminate\Http\Request;
use TCG\Voyager\Models\DataType;

class BreadDestroyActionService
{
    public function __construct(protected BreadDestroyService $destroyService)
    {
    }

    public function destroy(Request $request, DataType $dataType, $id): \Illuminate\Http\RedirectResponse
    {
        $ids = [];
        if (empty($id)) {
            $ids = explode(',', (string) $request->ids);
        } else {
            $ids[] = $id;
        }

        $affected = $this->destroyService->destroy($dataType, $ids);

        $displayName = $affected > 1
            ? $dataType->getTranslatedAttribute('display_name_plural')
            : $dataType->getTranslatedAttribute('display_name_singular');

        $flash = $affected
            ? [
                'message' => __('voyager::generic.successfully_deleted')." {$displayName}",
                'alert-type' => 'success',
            ]
            : [
                'message' => __('voyager::generic.error_deleting')." {$displayName}",
                'alert-type' => 'error',
            ];

        return redirect()->route("voyager.{$dataType->slug}.index")->with($flash);
    }
}

