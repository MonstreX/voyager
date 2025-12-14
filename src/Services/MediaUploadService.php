<?php

namespace TCG\Voyager\Services;

use TCG\Voyager\Media\ImageProcessor;

class MediaUploadService
{
    protected $model;
    protected $collectionName;
    protected $file;
    protected $disk = 'public';
    protected $props = [];
    protected $originalFileName = null;
    protected MediaService $mediaService;

    public function __construct($model, $collectionName = 'default', $file = null)
    {
        $this->model = $model;
        $this->collectionName = $collectionName;
        $this->setFile($file);
        $this->mediaService = app(MediaService::class);
    }

    public function file($file)
    {
        $this->setFile($file);
        return $this;
    }

    public function disk($disk)
    {
        $this->disk = $disk;
        return $this;
    }

    public function withProps($props)
    {
        $this->props = $props;
        return $this;
    }

    public function withProp($key, $value)
    {
        $this->props[$key] = $value;
        return $this;
    }

    public function crop($width, $height, $x = 0, $y = 0)
    {
        if (!$this->file) {
            throw new \Exception('No file set for cropping');
        }

        $processor = ImageProcessor::make($this->file);
        $processor->crop($width, $height, $x, $y);

        $this->file = (string) $processor->encode();
        return $this;
    }

    public function resize($width, $height = null, $aspectRatio = true)
    {
        if (!$this->file) {
            throw new \Exception('No file set for resizing');
        }

        $processor = ImageProcessor::make($this->file);
        $processor->resize($width, $height, $aspectRatio);

        $this->file = (string) $processor->encode();
        return $this;
    }

    public function save()
    {
        if (!$this->file) {
            throw new \Exception('No file set for upload');
        }

        $media = $this->mediaService->createFromFile(
            $this->model,
            $this->file,
            $this->collectionName,
            $this->disk,
            [
                'original_name' => $this->originalFileName,
            ]
        );

        if ($this->props) {
            $this->mediaService->updateMediaProps($media, $this->props);
        }

        return $media;
    }

    protected function setFile($file): void
    {
        $this->file = $file;

        if ($file instanceof \Illuminate\Http\UploadedFile) {
            $this->originalFileName = $file->getClientOriginalName();
        } elseif (is_string($file)) {
            $this->originalFileName = $this->originalFileName ?: 'file';
        }
    }
}
