<?php

namespace TCG\Voyager\Traits;

use TCG\Voyager\Models\Media;
use TCG\Voyager\Services\MediaService;

trait HasMedia
{
    public static function bootHasMedia()
    {
        static::deleting(function ($model) {
            if (!method_exists($model, 'media')) {
                return;
            }

            $mediaItems = $model->media()->get();

            if ($mediaItems->isEmpty()) {
                return;
            }

            $service = app(MediaService::class);
            $mediaItems->each(function (Media $media) use ($service) {
                $service->deleteMedia($media);
            });
        });
    }

    public function media()
    {
        return $this->morphMany(Media::class, 'model');
    }

    public function getMedia($collectionName = 'default')
    {
        return $this->media()
            ->where('collection_name', $collectionName)
            ->orderBy('order')
            ->get();
    }

    public function getFirstMedia($collectionName = 'default')
    {
        return $this->media()
            ->where('collection_name', $collectionName)
            ->orderBy('order')
            ->first();
    }

    public function getFirstMediaUrl($collectionName = 'default', $fallback = null)
    {
        $media = $this->getFirstMedia($collectionName);
        return $media ? $media->url() : $fallback;
    }

    public function addMedia($file, $collectionName = 'default')
    {
        return new \TCG\Voyager\Services\MediaUploadService($this, $collectionName, $file);
    }
}
