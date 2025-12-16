<?php

namespace TCG\Voyager\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class BreadFieldUploadService
{
    public function __construct(protected MediaService $mediaService)
    {
    }

    public function handleAdvImageUploads(Request $request, $rows, Model $data): void
    {
        foreach ($rows as $row) {
            if ($row->type !== 'adv_image') {
                continue;
            }

            $collectionName = $row->details->collection_name ?? $row->field;
            $titleField = $row->field . '_title';
            $altField = $row->field . '_alt';
            $clearField = $row->field . '_clear';
            $props = [];

            if ($request->has($titleField)) {
                $props['title'] = $request->input($titleField);
            }
            if ($request->has($altField)) {
                $props['alt'] = $request->input($altField);
            }

            if ($request->boolean($clearField)) {
                if ($data->id && method_exists($data, 'getFirstMedia')) {
                    $oldMedia = $data->getFirstMedia($collectionName);
                    if ($oldMedia) {
                        $this->mediaService->deleteMedia($oldMedia);
                    }
                }

                $data->{$row->field} = null;

                if (empty($props)) {
                    continue;
                }
            }

            if ($request->hasFile($row->field)) {
                $file = $request->file($row->field);

                if ($data->id && method_exists($data, 'getFirstMedia')) {
                    $oldMedia = $data->getFirstMedia($collectionName);
                    if ($oldMedia) {
                        $this->mediaService->deleteMedia($oldMedia);
                    }
                }

                $media = $this->mediaService->createFromFile($data, $file, $collectionName);

                if (!empty($props)) {
                    $this->mediaService->updateMediaProps($media, $props);
                }

                $data->{$row->field} = $media->id;
            } elseif (!empty($props) && method_exists($data, 'getFirstMedia')) {
                $existingMedia = $data->getFirstMedia($collectionName);
                if ($existingMedia) {
                    $this->mediaService->updateMediaProps($existingMedia, $props);
                }
            }
        }
    }

    public function handleAdvMediaFilesUploads(Request $request, $rows, Model $data): void
    {
        if (!$data->id || !method_exists($data, 'media')) {
            return;
        }

        foreach ($rows as $row) {
            if ($row->type !== 'adv_media_files') {
                continue;
            }

            $collectionName = $row->details->collection_name ?? $row->field;
            $propsInput = $request->input($row->field . '_props', []);
            $replaceFiles = $request->file($row->field . '_replace', []);

            if (is_array($replaceFiles)) {
                foreach ($replaceFiles as $mediaId => $file) {
                    if (!$file) {
                        continue;
                    }

                    $media = $data->media()
                        ->where('collection_name', $collectionName)
                        ->where('id', $mediaId)
                        ->first();

                    if ($media) {
                        $this->mediaService->replaceMediaFile($media, $file, [
                            'original_name' => $file->getClientOriginalName(),
                        ]);

                        if (isset($propsInput[$mediaId]) && is_array($propsInput[$mediaId])) {
                            $this->mediaService->updateMediaProps($media, $propsInput[$mediaId]);
                        }
                    }
                }
            }

            if (is_array($propsInput)) {
                foreach ($propsInput as $mediaId => $props) {
                    if (!is_array($props)) {
                        continue;
                    }

                    $media = $data->media()
                        ->where('collection_name', $collectionName)
                        ->where('id', $mediaId)
                        ->first();

                    if ($media) {
                        $this->mediaService->updateMediaProps($media, $props);
                    }
                }
            }

            if ($request->hasFile($row->field)) {
                $files = (array) $request->file($row->field);
                foreach ($files as $file) {
                    if ($file) {
                        $this->mediaService->createFromFile($data, $file, $collectionName);
                    }
                }
            }

            $orderInput = $request->input($row->field . '_order', []);
            if (is_array($orderInput) && !empty($orderInput)) {
                $this->mediaService->reorderCollection($data, $collectionName, $orderInput);
            }
        }
    }

    public function handleAdvInlineSetUploads(Request $request, $rows, Model $data): void
    {
        if (!$data->id || !method_exists($data, 'media')) {
            return;
        }

        foreach ($rows as $row) {
            if ($row->type !== 'adv_inline_set') {
                continue;
            }

            $inlineSet = $row->details->inline_set ?? null;
            $fields = $inlineSet->fields ?? null;
            if (!$fields) {
                continue;
            }

            $raw = $data->{$row->field} ?? '[]';
            $items = json_decode(!empty($raw) ? $raw : '[]', true);
            if (!is_array($items)) {
                continue;
            }

            $fields = (array) $fields;
            $updated = false;

            foreach ($items as $itemIndex => $item) {
                $rowId = isset($item['row_id']) ? (int) $item['row_id'] : 0;
                if ($rowId <= 0) {
                    continue;
                }

                foreach ($fields as $fieldName => $fieldDef) {
                    $type = is_array($fieldDef) ? ($fieldDef['type'] ?? 'text') : ($fieldDef->type ?? 'text');
                    if ($type !== 'media') {
                        continue;
                    }

                    $inputBase = $row->field . '_' . $fieldName . '_' . $rowId;

                    if (!$request->hasFile($inputBase)) {
                        continue;
                    }

                    $existing = $item[$fieldName] ?? [];
                    if (is_numeric($existing)) {
                        $existing = [(int) $existing];
                    }
                    $existing = array_values(array_filter(array_map('intval', (array) $existing)));

                    $files = (array) $request->file($inputBase);
                    foreach ($files as $file) {
                        if (!$file || !$file->isValid()) {
                            continue;
                        }
                        $media = $this->mediaService->createFromFile($data, $file, $inputBase);
                        $existing[] = $media->id;
                        $updated = true;
                    }

                    $items[$itemIndex][$fieldName] = array_values(array_unique(array_filter($existing)));
                }
            }

            if ($updated) {
                $data->{$row->field} = json_encode($items, JSON_UNESCAPED_UNICODE);
                $data->save();
            }
        }
    }
}

