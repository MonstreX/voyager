<?php

namespace TCG\Voyager\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Storage;
use TCG\Voyager\Events\FileDeleted;
use TCG\Voyager\Traits\AlertsMessages;
use TCG\Voyager\Services\BreadFieldUploadService;
use TCG\Voyager\Services\BreadValidationService;
use TCG\Voyager\Services\BreadWriteService;
use TCG\Voyager\Services\BreadContentService;
use Throwable;

abstract class Controller extends BaseController
{
    use DispatchesJobs;
    use ValidatesRequests;
    use AuthorizesRequests;
    use AlertsMessages;

    public function getSlug(Request $request)
    {
        if (isset($this->slug)) {
            $slug = $this->slug;
        } else {
            $slug = explode('.', $request->route()->getName())[1];
        }

        return $slug;
    }

    protected function voyagerJsonFlags(): int
    {
        $flags = JSON_UNESCAPED_UNICODE;
        if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $flags |= JSON_INVALID_UTF8_SUBSTITUTE;
        }

        return $flags;
    }

    protected function shouldExposeApiExceptionMessages(): bool
    {
        $configured = config('voyager.media.api.expose_exception_messages');
        if (!is_null($configured)) {
            return (bool) $configured;
        }

        return (bool) config('app.debug', false);
    }

    protected function apiExceptionMessage(Throwable $e, string $fallback): string
    {
        return $this->shouldExposeApiExceptionMessages() ? (string) $e->getMessage() : $fallback;
    }

    protected function apiErrorResponse(Throwable $e, string $fallbackMessage, int $statusCode = 500, array $extra = []): JsonResponse
    {
        $payload = ['status' => 'error', 'message' => $this->apiExceptionMessage($e, $fallbackMessage)];
        if ($extra) {
            $payload = $payload + $extra;
        }

        return response()->json($payload, $statusCode, [], $this->voyagerJsonFlags());
    }

    public function insertUpdateData($request, $slug, $rows, $data)
    {
        return app(BreadWriteService::class)->persist($request, $slug, $rows, $data);
    }

    // row fill logic moved to BreadRowFillService

    protected function handleAdvInlineSetUploads($request, $rows, $data)
    {
        app(BreadFieldUploadService::class)->handleAdvInlineSetUploads($request, $rows, $data);
    }

    /**
     * Validates bread POST request.
     *
     * @param array  $data The data
     * @param array  $rows The rows
     * @param string $slug Slug
     * @param int    $id   Id of the record to update
     *
     * @return mixed
     */
    public function validateBread($data, $rows, $name = null, $id = null)
    {
        $resolvedId = is_null($id) ? null : (int) $id;

        return app(BreadValidationService::class)->validateBread($data, $rows, $name, $resolvedId);
    }

    public function getContentBasedOnType(Request $request, $slug, $row, $options = null)
    {
        return app(BreadContentService::class)->getContent($request, $slug, $row, $options);
    }

    public function deleteFileIfExists($path)
    {
        if ($path && Storage::disk(config('voyager.storage.disk'))->exists($path)) {
            Storage::disk(config('voyager.storage.disk'))->delete($path);
            event(new FileDeleted($path));
        }
    }

    protected function handleAdvImageUploads($request, $rows, $data)
    {
        app(BreadFieldUploadService::class)->handleAdvImageUploads($request, $rows, $data);
    }

    protected function handleAdvMediaFilesUploads($request, $rows, $data)
    {
        app(BreadFieldUploadService::class)->handleAdvMediaFilesUploads($request, $rows, $data);
    }

}
