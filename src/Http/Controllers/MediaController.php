<?php

namespace TCG\Voyager\Http\Controllers;

use Illuminate\Http\Request;
use TCG\Voyager\Models\Media;
use TCG\Voyager\Services\MediaService;

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

            return response()->json([
                'status' => 'success',
                'media' => $media->toArray() + [
                    'props' => $media->props,
                    'url' => $media->url(),
                    'full_url' => $media->fullUrl(),
                ],
            ]);
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
}
