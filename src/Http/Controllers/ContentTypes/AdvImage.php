<?php

namespace TCG\Voyager\Http\Controllers\ContentTypes;

use TCG\Voyager\Services\MediaService;

class AdvImage extends BaseType
{
    public function handle()
    {
        if ($this->request->hasFile($this->row->field)) {
            $file = $this->request->file($this->row->field);
            $collectionName = $this->options->collection_name ?? $this->row->field;

            $route = $this->request->route();
            if ($route && $route->getParameter($this->slug)) {
                $model = $route->getParameter($this->slug);

                if ($model->id && method_exists($model, 'getFirstMedia')) {
                    $oldMedia = $model->getFirstMedia($collectionName);
                    if ($oldMedia) {
                        app(MediaService::class)->deleteMedia($oldMedia);
                    }
                }

                $media = app(MediaService::class)->createFromFile($model, $file, $collectionName);

                $titleField = $this->row->field . '_title';
                $altField = $this->row->field . '_alt';
                $props = [];

                if ($this->request->has($titleField)) {
                    $props['title'] = $this->request->input($titleField);
                }
                if ($this->request->has($altField)) {
                    $props['alt'] = $this->request->input($altField);
                }

                if (!empty($props)) {
                    app(MediaService::class)->updateMediaProps($media, $props);
                }

                return $media->id;
            }
        }

        return null;
    }
}
