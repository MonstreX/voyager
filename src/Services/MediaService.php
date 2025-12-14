<?php

namespace TCG\Voyager\Services;

use TCG\Voyager\Models\Media;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaService
{
    public function createFromFile($model, $file, $collectionName = 'default', $disk = 'public')
    {
        $originalFileName = $file->getClientOriginalName();
        $mimeType = $file->getMimeType();
        $size = $file->getSize();

        $pathGeneratorClass = config('voyager.media.path_generator', PathGeneratorService::class);
        $dirPath = $pathGeneratorClass::generate([
            'strategy' => PathGeneratorService::STRATEGY_DATED,
            'model' => $model,
        ]);

        $fileName = $this->generateUniqueFileName($file, $dirPath, $disk);
        $fullPath = $dirPath . '/' . $fileName;

        Storage::disk($disk)->putFileAs($dirPath, $file, $fileName);

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
        $existingProps = $media->props ? json_decode($media->props, true) : [];
        $media->props = json_encode(array_merge($existingProps, $props));
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

    protected function generateUniqueFileName($file, $dirPath, $disk = 'public'): string
    {
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $nameWithoutExt = pathinfo($originalName, PATHINFO_FILENAME);

        $slugName = Str::slug($nameWithoutExt, '_') ?: 'file';

        $fileName = $slugName . '.' . $extension;

        if (!Storage::disk($disk)->exists($dirPath . '/' . $fileName)) {
            return $fileName;
        }

        $counter = 1;
        while (Storage::disk($disk)->exists($dirPath . '/' . $slugName . '_' . $counter . '.' . $extension)) {
            $counter++;
        }

        return $slugName . '_' . $counter . '.' . $extension;
    }
}
