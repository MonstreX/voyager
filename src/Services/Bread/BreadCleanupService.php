<?php

namespace TCG\Voyager\Services\Bread;

use Illuminate\Support\Facades\Storage;
use TCG\Voyager\Events\BreadImagesDeleted;
use TCG\Voyager\Events\FileDeleted;

class BreadCleanupService
{
    public function cleanup($dataType, $data): void
    {
        if (is_bread_translatable($data)) {
            $data->deleteAttributeTranslations($data->getTranslatableAttributes());
        }

        $this->deleteBreadImages($data, $dataType->deleteRows->whereIn('type', ['image', 'multiple_images']));

        foreach ($dataType->deleteRows->where('type', 'file') as $row) {
            if (!isset($data->{$row->field})) {
                continue;
            }

            foreach (json_decode($data->{$row->field}) as $file) {
                $this->deleteFileIfExists($file->download_link);
            }
        }

        $dataType->rows
            ->where('type', 'media_picker')
            ->where('details.delete_files', true)
            ->each(function ($row) use ($data) {
                $content = $data->{$row->field};
                if (!isset($content)) {
                    return;
                }
                if (!is_array($content)) {
                    $content = json_decode($content);
                }
                if (is_array($content)) {
                    foreach ($content as $file) {
                        $this->deleteFileIfExists($file);
                    }
                } else {
                    $this->deleteFileIfExists($content);
                }
            });
    }

    public function deleteBreadImages($data, $rows, $single_image = null): void
    {
        $imagesDeleted = false;

        foreach ($rows as $row) {
            if ($row->type == 'multiple_images') {
                $images_to_remove = json_decode($data->getOriginal($row->field), true) ?? [];
            } else {
                $images_to_remove = [$data->getOriginal($row->field)];
            }

            foreach ($images_to_remove as $image) {
                if ($image != config('voyager.user.default_avatar') && (is_null($single_image) || $single_image == $image)) {
                    $this->deleteFileIfExists($image);
                    $imagesDeleted = true;

                    if (isset($row->details->thumbnails)) {
                        foreach ($row->details->thumbnails as $thumbnail) {
                            $ext = explode('.', $image);
                            $extension = '.'.$ext[count($ext) - 1];

                            $path = str_replace($extension, '', $image);

                            $thumb_name = $thumbnail->name;

                            $this->deleteFileIfExists($path.'-'.$thumb_name.$extension);
                        }
                    }
                }
            }
        }

        if ($imagesDeleted) {
            event(new BreadImagesDeleted($data, $rows));
        }
    }

    public function deleteFileIfExists($path): void
    {
        if (!$path) {
            return;
        }

        $disk = config('voyager.storage.disk');
        if (Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
            event(new FileDeleted($path));
        }
    }
}
