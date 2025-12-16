<?php

namespace TCG\Voyager\Http\Controllers\ContentTypes;

use TCG\Voyager\Services\MediaService;

class AdvInlineSetContentType extends BaseType
{
    public function handle()
    {
        $model = $this->request->attributes->get('voyagerModel');
        $inlineSet = $this->options->inline_set ?? null;
        $fields = $inlineSet->fields ?? null;

        if (!$fields) {
            return json_encode([]);
        }

        $fields = (array) $fields;

        $rowIdsRaw = (string) $this->request->input($this->row->field . '_row_ids', '');
        $rowIds = array_values(array_filter(array_map('intval', array_filter(explode(',', $rowIdsRaw)))));

        if (empty($rowIds)) {
            return json_encode([]);
        }

        $mediaService = app(MediaService::class);
        $result = [];

        foreach ($rowIds as $index => $rowId) {
            $rowData = [
                'row_id' => $rowId,
                'order' => $index,
            ];

            foreach ($fields as $fieldName => $fieldDef) {
                $type = is_array($fieldDef) ? ($fieldDef['type'] ?? 'text') : ($fieldDef->type ?? 'text');
                $inputName = $this->row->field . '_' . $fieldName . '_' . $rowId;

                if ($type === 'media') {
                    $collectionName = $this->row->field . '_' . $fieldName . '_' . $rowId;

                    $idsRaw = (string) $this->request->input($inputName . '_media_ids', '');
                    $ids = array_values(array_filter(array_map('intval', array_filter(explode(',', $idsRaw)))));

                    $deletedRaw = (string) $this->request->input($inputName . '_media_deleted_ids', '');
                    $deletedIds = array_values(array_filter(array_map('intval', array_filter(explode(',', $deletedRaw)))));

                    if (!empty($deletedIds) && $model && method_exists($model, 'media')) {
                        $mediaItems = $model->media()
                            ->where('collection_name', $collectionName)
                            ->whereIn('id', $deletedIds)
                            ->get();
                        foreach ($mediaItems as $media) {
                            $mediaService->deleteMedia($media);
                        }
                    }

                    if (!empty($deletedIds)) {
                        $ids = array_values(array_diff($ids, $deletedIds));
                    }

                    if ($this->request->hasFile($inputName) && $model && $model->getKey()) {
                        $files = (array) $this->request->file($inputName);
                        foreach ($files as $file) {
                            if (!$file) {
                                continue;
                            }
                            $media = $mediaService->createFromFile($model, $file, $collectionName);
                            $ids[] = $media->id;
                        }
                    }

                    $rowData[$fieldName] = array_values(array_unique(array_filter($ids)));
                    continue;
                }

                if ($type === 'checkbox') {
                    $raw = $this->request->input($inputName, 0);
                    $rowData[$fieldName] = ($raw === 'on' || $raw === '1' || $raw === 1 || $raw === true) ? 1 : 0;
                    continue;
                }

                $default = is_array($fieldDef) ? ($fieldDef['default'] ?? null) : ($fieldDef->default ?? null);
                $rowData[$fieldName] = $this->request->input($inputName, $default);
            }

            $result[] = $rowData;
        }

        $globalDeletedRaw = (string) $this->request->input($this->row->field . '_deleted_media_ids', '');
        $globalDeleted = array_values(array_filter(array_map('intval', array_filter(explode(',', $globalDeletedRaw)))));

        if (!empty($globalDeleted) && $model && method_exists($model, 'media')) {
            $mediaItems = $model->media()->whereIn('id', $globalDeleted)->get();
            foreach ($mediaItems as $media) {
                $mediaService->deleteMedia($media);
            }
        }

        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }
}
