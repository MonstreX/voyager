<?php

namespace TCG\Voyager\Traits;

use TCG\Voyager\Models\Media;

trait HasMedia
{
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
