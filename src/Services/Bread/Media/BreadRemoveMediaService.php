<?php

namespace TCG\Voyager\Services\Bread\Media;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Services\Bread\Write\BreadCleanupService;

class BreadRemoveMediaService
{
    public function __construct(protected BreadCleanupService $cleanupService)
    {
    }

    public function removeMedia(Request $request)
    {
        try {
            $slug = $request->get('slug');
            $filename = $request->get('filename');
            $id = $request->get('id');
            $field = $request->get('field');
            $multi = $request->get('multi');

            $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

            $model = app($dataType->model_name);
            $data = $model::find([$id])->first();

            if (!isset($data->{$field})) {
                throw new Exception(__('voyager::generic.field_does_not_exist'), 400);
            }

            Gate::authorize('edit', $data);

            $fileToRemove = null;

            if (@json_decode($multi)) {
                if (is_null(@json_decode($data->{$field}))) {
                    throw new Exception(__('voyager::json.invalid'), 500);
                }

                $fieldData = @json_decode($data->{$field}, true);
                $key = null;

                if (is_array($fieldData[0] ?? null)) {
                    foreach ($fieldData as $index => $fileItem) {
                        if (!empty($fileItem['original_name'])) {
                            if ($fileItem['original_name'] == $filename) {
                                $key = $index;
                                break;
                            }
                        } else {
                            $flipped = array_flip($fileItem);
                            if (array_key_exists($filename, $flipped)) {
                                $key = $index;
                                break;
                            }
                        }
                    }
                } else {
                    $key = array_search($filename, $fieldData);
                }

                if (is_null($key) || $key === false) {
                    throw new Exception(__('voyager::media.file_does_not_exist'), 400);
                }

                $fileToRemove = $fieldData[$key]['download_link'] ?? $fieldData[$key];

                unset($fieldData[$key]);

                $data->{$field} = empty($fieldData) ? null : json_encode(array_values($fieldData));
            } else {
                if ($filename == $data->{$field}) {
                    $fileToRemove = $data->{$field};
                    $data->{$field} = null;
                } else {
                    throw new Exception(__('voyager::media.file_does_not_exist'), 400);
                }
            }

            $row = $dataType->rows->where('field', $field)->first();

            if (in_array($row->type, ['image', 'multiple_images'])) {
                $this->cleanupService->deleteBreadImages($data, [$row], $fileToRemove);
            } else {
                $this->cleanupService->deleteFileIfExists($fileToRemove);
            }

            $data->save();

            return response()->json([
                'data' => [
                    'status'  => 200,
                    'message' => __('voyager::media.file_removed'),
                ],
            ]);
        } catch (Exception $e) {
            $code = 500;
            $message = __('voyager::generic.internal_error');

            if ($e->getCode()) {
                $code = $e->getCode();
            }

            if ($e->getMessage()) {
                $message = $e->getMessage();
            }

            return response()->json([
                'data' => [
                    'status'  => $code,
                    'message' => $message,
                ],
            ], $code);
        }
    }
}
