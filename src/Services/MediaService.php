<?php

namespace TCG\Voyager\Services;

use TCG\Voyager\Models\Media;

class MediaService
{
    public function createFromFile($model, $file, $collectionName = 'default', $disk = 'public')
    {
        $fileName = $file->getClientOriginalName();
        $mimeType = $file->getMimeType();
        $size = $file->getSize();

        $path = $this->storeFile($file, $disk);

        return Media::create([
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'collection_name' => $collectionName,
            'file_name' => $fileName,
            'path' => $path,
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

    protected function storeFile($file, $disk = 'public')
    {
        $path = $file->store('media', $disk);
        return $path;
    }

    protected function deleteFile($path, $disk = 'public')
    {
        if (\Storage::disk($disk)->exists($path)) {
            return \Storage::disk($disk)->delete($path);
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
}
