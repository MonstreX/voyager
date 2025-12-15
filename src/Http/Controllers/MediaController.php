<?php

namespace TCG\Voyager\Http\Controllers;

use Illuminate\Http\Request;
use TCG\Voyager\Models\Media;
use TCG\Voyager\Media\ImageProcessor;
use TCG\Voyager\Services\MediaService;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    protected $mediaService;

    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240',
            'model_type' => 'required|string',
            'model_id' => 'required|integer',
            'collection_name' => 'sometimes|string',
        ]);

        try {
            $modelClass = $request->input('model_type');
            $modelId = $request->input('model_id');
            $collectionName = $request->input('collection_name', 'default');

            if (!class_exists($modelClass)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid model class',
                ], 400);
            }

            $model = $modelClass::findOrFail($modelId);

            $this->authorize('edit', $model);

            $file = $request->file('file');

            $media = $this->mediaService->createFromFile($model, $file, $collectionName);

            return response()->json([
                'status' => 'success',
                'media' => $media,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Media $media)
    {
        try {
            $model = $media->model;
            if ($model) {
                $this->authorize('edit', $model);
            }

            $jsonFlags = JSON_UNESCAPED_UNICODE;
            if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
                $jsonFlags |= JSON_INVALID_UTF8_SUBSTITUTE;
            }

            return response()->json([
                'status' => 'success',
                'media' => $media->toArray() + [
                    'props' => $media->props,
                    'url' => $media->url(),
                    'full_url' => $media->fullUrl(),
                ],
            ], 200, [], $jsonFlags);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function delete(Media $media)
    {
        try {
            $model = $media->model;
            if ($model) {
                $this->authorize('delete', $model);
            }

            $this->mediaService->deleteMedia($media);

            return response()->json([
                'status' => 'success',
                'message' => 'Media deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateProps(Request $request, Media $media)
    {
        try {
            $model = $media->model;
            if ($model) {
                $this->authorize('edit', $model);
            }

            $props = $request->input('props', []);
            $this->mediaService->updateMediaProps($media, $props);

            $jsonFlags = JSON_UNESCAPED_UNICODE;
            if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
                $jsonFlags |= JSON_INVALID_UTF8_SUBSTITUTE;
            }

            return response()->json([
                'status' => 'success',
                'media' => $media,
            ], 200, [], $jsonFlags);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'model_type' => 'required|string',
            'model_id' => 'required|integer',
            'collection_name' => 'required|string',
            'order' => 'required|array',
        ]);

        try {
            $modelClass = $request->input('model_type');
            $modelId = $request->input('model_id');
            $collectionName = $request->input('collection_name');
            $order = $request->input('order');

            if (!class_exists($modelClass)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid model class',
                ], 400);
            }

            $model = $modelClass::findOrFail($modelId);

            $this->authorize('edit', $model);

            $this->mediaService->reorderCollection($model, $collectionName, $order);

            return response()->json([
                'status' => 'success',
                'message' => 'Media reordered successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function crop(Request $request, Media $media)
    {
        try {
            $model = $media->model;
            if ($model) {
                $this->authorize('edit', $model);
            }

            if (!$media->mime_type || strpos($media->mime_type, 'image/') !== 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only image media can be cropped',
                ], 400);
            }

            $data = $request->validate([
                'x' => 'required|integer|min:0',
                'y' => 'required|integer|min:0',
                'width' => 'required|integer|min:1',
                'height' => 'required|integer|min:1',
                'max_width' => 'nullable|integer|min:1',
                'max_height' => 'nullable|integer|min:1',
            ]);

            $disk = $media->disk ?: config('voyager.storage.disk', 'public');

            $content = Storage::disk($disk)->get($media->path);
            $processor = ImageProcessor::make($content);
            $processor->crop(
                (int) $data['width'],
                (int) $data['height'],
                (int) $data['x'],
                (int) $data['y']
            );

            $maxWidth = isset($data['max_width']) ? (int) $data['max_width'] : null;
            $maxHeight = isset($data['max_height']) ? (int) $data['max_height'] : null;

            $curWidth = (int) $processor->width();
            $curHeight = (int) $processor->height();

            $scaleRatio = 1.0;
            if ($maxWidth && $curWidth > $maxWidth) {
                $scaleRatio = min($scaleRatio, $maxWidth / $curWidth);
            }
            if ($maxHeight && $curHeight > $maxHeight) {
                $scaleRatio = min($scaleRatio, $maxHeight / $curHeight);
            }

            if ($scaleRatio < 1.0) {
                $newWidth = max(1, (int) round($curWidth * $scaleRatio));
                $newHeight = max(1, (int) round($curHeight * $scaleRatio));
                $processor->scale($newWidth, $newHeight);
            }

            $format = 'jpeg';
            switch ($media->mime_type) {
                case 'image/png':
                    $format = 'png';
                    break;
                case 'image/gif':
                    $format = 'gif';
                    break;
                case 'image/webp':
                    $format = 'webp';
                    break;
                case 'image/jpeg':
                case 'image/jpg':
                default:
                    $format = 'jpeg';
                    break;
            }

            $encoded = (string) $processor->encode($format)->encoded;

            Storage::disk($disk)->put($media->path, $encoded);

            $media->size = strlen($encoded);
            $media->save();

            $jsonFlags = JSON_UNESCAPED_UNICODE;
            if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
                $jsonFlags |= JSON_INVALID_UTF8_SUBSTITUTE;
            }

            return response()->json([
                'status' => 'success',
                'message' => __('voyager::media.success_crop_image'),
            ], 200, [], $jsonFlags);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
