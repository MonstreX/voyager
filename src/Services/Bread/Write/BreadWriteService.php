<?php

namespace TCG\Voyager\Services\Bread\Write;

use Illuminate\Http\Request;
use TCG\Voyager\Services\Bread\Media\BreadFieldUploadService;
use TCG\Voyager\Services\Bread\Media\BreadMediaPickerPathService;
use TCG\Voyager\Services\Bread\Relations\BreadBelongsToManySyncService;
use TCG\Voyager\Services\Bread\Support\BreadContentService;

class BreadWriteService
{
    public function __construct(
        protected BreadContentService $contentService,
        protected BreadTranslationService $translationService,
        protected BreadRowFillService $rowFillService,
        protected BreadFieldUploadService $fieldUploadService,
        protected BreadBelongsToManySyncService $belongsToManySyncService,
        protected BreadMediaPickerPathService $mediaPickerPathService
    ) {
    }

    public function persist(Request $request, string $slug, $rows, $data)
    {
        $multiSelect = [];
        $isCreating = !$data->exists;

        $request->attributes->set('voyagerModel', $data);
        $request->attributes->add(['breadRows' => $rows->pluck('field')->toArray()]);

        $translations = $this->translationService->prepare($data, $request);

        $this->rowFillService->fillModelFromRows(
            $request,
            $slug,
            $rows,
            $data,
            fn (Request $req, string $slugValue, $row, $options) => $this->contentService->getContent($req, $slugValue, $row, $options),
            $multiSelect
        );

        $this->fillAdditionalAttributes($request, $data);

        $data->save();

        $this->fieldUploadService->handleAdvImageUploads($request, $rows, $data);
        if ($data->isDirty()) {
            $data->save();
        }

        if ($isCreating) {
            $this->fieldUploadService->handleAdvInlineSetUploads($request, $rows, $data);
        }

        $this->fieldUploadService->handleAdvMediaFilesUploads($request, $rows, $data);

        $this->translationService->persist($data, $translations);
        $this->belongsToManySyncService->sync($data, $multiSelect);
        $this->mediaPickerPathService->renameFoldersIfNeeded($request, $slug, $rows, $data);

        return $data;
    }

    private function fillAdditionalAttributes(Request $request, $data): void
    {
        if (!isset($data->additional_attributes)) {
            return;
        }

        foreach ($data->additional_attributes as $attr) {
            if ($request->has($attr)) {
                $data->{$attr} = $request->{$attr};
            }
        }
    }
}
