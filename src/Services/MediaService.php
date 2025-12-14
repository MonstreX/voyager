<?php

namespace TCG\Voyager\Services;

use TCG\Voyager\Models\Media;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaService
{
    public function createFromFile($model, $file, $collectionName = 'default', $disk = null, array $meta = [])
    {
        $disk = $disk ?: config('voyager.storage.disk', 'public');

        $filePayload = $this->normalizeFilePayload($file, $meta);
        $originalFileName = $filePayload['original_name'];
        $mimeType = $filePayload['mime_type'];
        $size = $filePayload['size'];

        $pathGeneratorClass = config('voyager.media.path_generator', PathGeneratorService::class);
        $dirPath = $pathGeneratorClass::generate([
            'model' => $model,
        ]);

        $fileName = $this->generateUniqueFileName($originalFileName, $dirPath, $disk);
        $fullPath = $dirPath . '/' . $fileName;

        if ($filePayload['uploaded_file']) {
            Storage::disk($disk)->putFileAs($dirPath, $filePayload['uploaded_file'], $fileName);
        } else {
            Storage::disk($disk)->put($fullPath, $filePayload['contents']);
        }

        return Media::create([
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'collection_name' => $collectionName,
            'file_name' => $originalFileName,
            'path' => $fullPath,
            'disk' => $disk,
            'mime_type' => $mimeType,
            'size' => $size,
            'order' => $this->getNextOrder($model, $collectionName),
        ]);
    }

    public function deleteMedia(Media $media)
    {
        $this->deleteFile($media->path, $media->disk);
        return $media->delete();
    }

    public function updateMediaProps(Media $media, $props)
    {
        $existingProps = $media->props ?? [];
        $media->props = array_merge($existingProps, $props);
        return $media->save();
    }

    public function reorderCollection($model, $collectionName, $order)
    {
        foreach ($order as $index => $mediaId) {
            $media = $model->media()
                ->where('id', $mediaId)
                ->where('collection_name', $collectionName)
                ->first();

            if ($media) {
                $media->update(['order' => $index]);
            }
        }

        return true;
    }

    protected function deleteFile($path, $disk = 'public')
    {
        if (Storage::disk($disk)->exists($path)) {
            return Storage::disk($disk)->delete($path);
        }

        return false;
    }

    protected function getNextOrder($model, $collectionName)
    {
        $lastMedia = $model->media()
            ->where('collection_name', $collectionName)
            ->orderByDesc('order')
            ->first();

        return $lastMedia ? $lastMedia->order + 1 : 0;
    }

    protected function generateUniqueFileName($originalName, $dirPath, $disk = 'public'): string
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $nameWithoutExt = pathinfo($originalName, PATHINFO_FILENAME);

        $slugName = Str::slug($nameWithoutExt, '_') ?: 'file';

        $fileName = $extension ? $slugName . '.' . $extension : $slugName;

        if (!Storage::disk($disk)->exists($dirPath . '/' . $fileName)) {
            return $fileName;
        }

        $counter = 1;
        while (Storage::disk($disk)->exists($dirPath . '/' . $slugName . '_' . $counter . ($extension ? '.' . $extension : ''))) {
            $counter++;
        }

        return $slugName . '_' . $counter . ($extension ? '.' . $extension : '');
    }

    protected function normalizeFilePayload($file, array $meta = []): array
    {
        $originalName = $meta['original_name'] ?? null;
        $mimeType = $meta['mime_type'] ?? null;
        $size = $meta['size'] ?? null;
        $uploadedFile = null;
        $contents = null;

        if ($file instanceof \Illuminate\Http\UploadedFile) {
            $uploadedFile = $file;
            $originalName = $originalName ?: $file->getClientOriginalName();
            $mimeType = $mimeType ?: $file->getMimeType();
            $size = $size ?: $file->getSize();
        } elseif ($file instanceof \SplFileInfo) {
            $originalName = $originalName ?: $file->getFilename();
            $mimeType = $mimeType ?: mime_content_type($file->getRealPath());
            $contents = file_get_contents($file->getRealPath());
            $size = $size ?: strlen($contents);
        } elseif (is_string($file)) {
            $contents = $file;
            $size = $size ?: strlen($contents);
            if (!$mimeType) {
                $mimeType = (new \finfo(FILEINFO_MIME_TYPE))->buffer($contents);
            }
            if (!$originalName) {
                $extension = $this->guessExtensionFromMime($mimeType);
                $originalName = 'file' . ($extension ? '.' . $extension : '');
            }
        } else {
            throw new \InvalidArgumentException('Unsupported file type for media upload');
        }

        return [
            'original_name' => $originalName,
            'mime_type' => $mimeType,
            'size' => $size,
            'uploaded_file' => $uploadedFile,
            'contents' => $contents,
        ];
    }

    protected function guessExtensionFromMime(?string $mimeType): ?string
    {
        if (!$mimeType) {
            return null;
        }

        $map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];

        return $map[$mimeType] ?? null;
    }
}
