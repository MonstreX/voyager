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
        $this->authorize('add', \TCG\Voyager\Models\DataType::class);

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

    public function delete(Media $media)
    {
        $this->authorize('delete', $media->model());

        try {
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
        $this->authorize('edit', $media->model());

        try {
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
        $this->authorize('edit', \TCG\Voyager\Models\DataType::class);

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
