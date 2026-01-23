<?php

namespace TCG\Voyager\Services\Bread\Media;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use TCG\Voyager\Facades\Voyager;

class BreadReorderMediaService
{
    public function reorderMedia(Request $request)
    {
        try {
            $slug = $request->input('slug');
            $id = $request->input('id');
            $field = $request->input('field');
            $order = $request->input('order');

            if (!is_string($slug) || $slug === '') {
                throw new Exception(__('voyager::generic.invalid'), 400);
            }

            if (!is_string($field) || $field === '') {
                throw new Exception(__('voyager::generic.invalid'), 400);
            }

            if (!is_array($order)) {
                throw new Exception(__('voyager::json.invalid'), 400);
            }

            $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();
            if (!$dataType) {
                throw new Exception(__('voyager::generic.invalid'), 400);
            }

            $model = app($dataType->model_name);
            $data = $model::find($id);
            if (!$data) {
                throw new Exception(__('voyager::generic.not_found'), 404);
            }

            if (!isset($data->{$field})) {
                throw new Exception(__('voyager::generic.field_does_not_exist'), 400);
            }

            Gate::authorize('edit', $data);

            $fieldData = @json_decode($data->{$field}, true);
            if (!is_array($fieldData)) {
                throw new Exception(__('voyager::json.invalid'), 500);
            }

            $getItemKey = static function ($item) {
                if (is_string($item)) {
                    return $item;
                }
                if (is_array($item)) {
                    if (!empty($item['download_link'])) {
                        return $item['download_link'];
                    }
                    if (!empty($item['original_name'])) {
                        return $item['original_name'];
                    }
                    if (isset($item[0]) && is_string($item[0])) {
                        return $item[0];
                    }
                    foreach ($item as $value) {
                        if (is_string($value)) {
                            return $value;
                        }
                    }
                }
                return null;
            };

            $itemsByKey = [];
            foreach ($fieldData as $item) {
                $key = $getItemKey($item);
                if ($key === null) {
                    continue;
                }
                if (!isset($itemsByKey[$key])) {
                    $itemsByKey[$key] = [];
                }
                $itemsByKey[$key][] = $item;
            }

            $newOrder = [];
            foreach ($order as $key) {
                if (!is_string($key) || !isset($itemsByKey[$key])) {
                    continue;
                }
                $newOrder[] = array_shift($itemsByKey[$key]);
                if (empty($itemsByKey[$key])) {
                    unset($itemsByKey[$key]);
                }
            }

            foreach ($fieldData as $item) {
                $key = $getItemKey($item);
                if ($key === null || !isset($itemsByKey[$key])) {
                    continue;
                }
                $newOrder[] = array_shift($itemsByKey[$key]);
                if (empty($itemsByKey[$key])) {
                    unset($itemsByKey[$key]);
                }
            }

            $data->{$field} = empty($newOrder) ? null : json_encode(array_values($newOrder));
            $data->save();

            return response()->json([
                'data' => [
                    'status' => 200,
                    'message' => __('voyager::generic.successfully_updated'),
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
                    'status' => $code,
                    'message' => $message,
                ],
            ], $code);
        }
    }
}
