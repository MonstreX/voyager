<?php

namespace TCG\Voyager\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use TCG\Voyager\Exceptions\MediaApiException;
use TCG\Voyager\Models\Media;
use TCG\Voyager\Media\ImageProcessor;
use TCG\Voyager\Services\Media\MediaService;
use Illuminate\Support\Facades\Storage;
use TCG\Voyager\Traits\HasMedia;
use Throwable;

class MediaController extends Controller
{
    public function __construct(protected MediaService $mediaService)
    {
    }

    protected function resolveModelFromRequest(Request $request): Model
    {
        $modelClass = $request->input('model_type');
        $modelId = (int) $request->input('model_id');

        if (!is_string($modelClass) || $modelClass === '') {
            throw MediaApiException::badRequest('missing_model_type', 'Invalid request');
        }

        if (!class_exists($modelClass) || !is_subclass_of($modelClass, Model::class)) {
            throw MediaApiException::badRequest('invalid_model_type', 'Invalid request');
        }

        $allowed = config('voyager.media.api.allowed_model_types', []);
        if (is_array($allowed) && count($allowed) > 0 && !in_array($modelClass, $allowed, true)) {
            throw MediaApiException::badRequest('invalid_model_type', 'Invalid request');
        }

        if (config('voyager.media.api.require_has_media_trait', true)) {
            $usesHasMedia = in_array(HasMedia::class, class_uses_recursive($modelClass), true);
            $hasMediaMethod = method_exists($modelClass, 'media');

            if (!$usesHasMedia && !$hasMediaMethod) {
                throw MediaApiException::badRequest('invalid_model_type', 'Invalid request');
            }
        }

        /** @var Model $model */
        $model = $modelClass::findOrFail($modelId);

        return $model;
    }

    public function upload(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|max:10240',
                'model_type' => 'required|string',
                'model_id' => 'required|integer',
                'collection_name' => 'sometimes|string',
            ]);

            $collectionName = $request->input('collection_name', 'default');
            $model = $this->resolveModelFromRequest($request);

            $this->authorize('edit', $model);

            $file = $request->file('file');
            if (is_null($file)) {
                $file = $request->input('file');
            }
            if (is_array($file)) {
                $file = $file[0] ?? null;
            }
            if (is_null($file)) {
                throw MediaApiException::badRequest('missing_file', 'Invalid request');
            }

            $media = $this->mediaService->createFromFile($model, $file, $collectionName);

            return $this->apiSuccessResponse(
                ['media' => $media],
                null,
                200,
                ['media' => $media]
            );
        } catch (ValidationException $e) {
            return $this->apiErrorCodeResponse('validation_failed', 'Validation failed', 422, ['errors' => $e->errors()]);
        } catch (MediaApiException $e) {
            return $this->apiErrorCodeResponse($e->apiCode, $e->getMessage(), $e->statusCode, $e->extra);
        } catch (ModelNotFoundException $e) {
            return $this->apiErrorCodeResponse('model_not_found', 'Not found', 404);
        } catch (AuthorizationException $e) {
            return $this->apiErrorCodeResponse('forbidden', 'Unauthorized', 403);
        } catch (Throwable $e) {
            report($e);
            return $this->apiErrorCodeResponse('server_error', 'Server error', 500);
        }
    }

    protected function resolveMedia($mediaId): Media
    {
        /** @var Media $media */
        $media = Media::query()->findOrFail($mediaId);
        return $media;
    }

    public function show($media)
    {
        try {
            $media = $this->resolveMedia($media);
            $model = $media->model;
            if ($model) {
                $this->authorize('edit', $model);
            }

            $payload = $media->toArray() + [
                'props' => $media->props,
                'url' => $media->url(),
                'full_url' => $media->fullUrl(),
            ];

            return $this->apiSuccessResponse(['media' => $payload], null, 200, ['media' => $payload]);
        } catch (ModelNotFoundException $e) {
            return $this->apiErrorCodeResponse('media_not_found', 'Not found', 404);
        } catch (AuthorizationException $e) {
            return $this->apiErrorCodeResponse('forbidden', 'Unauthorized', 403);
        } catch (Throwable $e) {
            report($e);
            return $this->apiErrorCodeResponse('server_error', 'Server error', 500);
        }
    }

    public function delete($media)
    {
        try {
            $media = $this->resolveMedia($media);
            $model = $media->model;
            if ($model) {
                $this->authorize('delete', $model);
            }

            $this->mediaService->deleteMedia($media);

            return $this->apiSuccessResponse([], __('voyager::generic.successfully_deleted'), 200);
        } catch (ModelNotFoundException $e) {
            return $this->apiErrorCodeResponse('media_not_found', 'Not found', 404);
        } catch (AuthorizationException $e) {
            return $this->apiErrorCodeResponse('forbidden', 'Unauthorized', 403);
        } catch (Throwable $e) {
            report($e);
            return $this->apiErrorCodeResponse('server_error', 'Server error', 500);
        }
    }

    public function updateProps(Request $request, $media)
    {
        try {
            $media = $this->resolveMedia($media);
            $model = $media->model;
            if ($model) {
                $this->authorize('edit', $model);
            }

            $props = $request->input('props', []);
            if (!is_array($props)) {
                throw MediaApiException::badRequest('invalid_props', 'Invalid request');
            }
            $this->mediaService->updateMediaProps($media, $props);

            return $this->apiSuccessResponse(['media' => $media], null, 200, ['media' => $media]);
        } catch (ModelNotFoundException $e) {
            return $this->apiErrorCodeResponse('media_not_found', 'Not found', 404);
        } catch (AuthorizationException $e) {
            return $this->apiErrorCodeResponse('forbidden', 'Unauthorized', 403);
        } catch (MediaApiException $e) {
            return $this->apiErrorCodeResponse($e->apiCode, $e->getMessage(), $e->statusCode, $e->extra);
        } catch (Throwable $e) {
            report($e);
            return $this->apiErrorCodeResponse('server_error', 'Server error', 500);
        }
    }

    public function reorder(Request $request)
    {
        try {
            $request->validate([
                'model_type' => 'required|string',
                'model_id' => 'required|integer',
                'collection_name' => 'required|string',
                'order' => 'required|array',
            ]);

            $collectionName = $request->input('collection_name');
            $order = $request->input('order');
            $model = $this->resolveModelFromRequest($request);

            $this->authorize('edit', $model);

            $this->mediaService->reorderCollection($model, $collectionName, $order);

            return $this->apiSuccessResponse([], 'Media reordered successfully', 200);
        } catch (ValidationException $e) {
            return $this->apiErrorCodeResponse('validation_failed', 'Validation failed', 422, ['errors' => $e->errors()]);
        } catch (MediaApiException $e) {
            return $this->apiErrorCodeResponse($e->apiCode, $e->getMessage(), $e->statusCode, $e->extra);
        } catch (ModelNotFoundException $e) {
            return $this->apiErrorCodeResponse('model_not_found', 'Not found', 404);
        } catch (AuthorizationException $e) {
            return $this->apiErrorCodeResponse('forbidden', 'Unauthorized', 403);
        } catch (Throwable $e) {
            report($e);
            return $this->apiErrorCodeResponse('server_error', 'Server error', 500);
        }
    }

    public function crop(Request $request, $media)
    {
        try {
            $media = $this->resolveMedia($media);
            $model = $media->model;
            if ($model) {
                $this->authorize('edit', $model);
            }

            if (!$media->mime_type || strpos($media->mime_type, 'image/') !== 0) {
                throw MediaApiException::badRequest('not_image', 'Only image media can be cropped');
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

            return $this->apiSuccessResponse([], __('voyager::media.success_crop_image'), 200);
        } catch (ValidationException $e) {
            return $this->apiErrorCodeResponse('validation_failed', 'Validation failed', 422, ['errors' => $e->errors()]);
        } catch (ModelNotFoundException $e) {
            return $this->apiErrorCodeResponse('media_not_found', 'Not found', 404);
        } catch (AuthorizationException $e) {
            return $this->apiErrorCodeResponse('forbidden', 'Unauthorized', 403);
        } catch (MediaApiException $e) {
            return $this->apiErrorCodeResponse($e->apiCode, $e->getMessage(), $e->statusCode, $e->extra);
        } catch (Throwable $e) {
            report($e);
            return $this->apiErrorCodeResponse('server_error', 'Server error', 500);
        }
    }
}
