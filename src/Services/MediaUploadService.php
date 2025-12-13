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

    public function __construct($model, $collectionName = 'default', $file = null)
    {
        $this->model = $model;
        $this->collectionName = $collectionName;
        $this->file = $file;
    }

    public function file($file)
    {
        $this->file = $file;
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

        $this->file = $processor->encoded;
        return $this;
    }

    public function resize($width, $height = null, $aspectRatio = true)
    {
        if (!$this->file) {
            throw new \Exception('No file set for resizing');
        }

        $processor = ImageProcessor::make($this->file);
        $processor->resize($width, $height, $aspectRatio);

        $this->file = $processor->encoded;
        return $this;
    }

    public function save()
    {
        if (!$this->file) {
            throw new \Exception('No file set for upload');
        }

        $mediaService = new MediaService();
        $media = $mediaService->createFromFile(
            $this->model,
            $this->file,
            $this->collectionName,
            $this->disk
        );

        if ($this->props) {
            $mediaService->updateMediaProps($media, $this->props);
        }

        return $media;
    }
}
