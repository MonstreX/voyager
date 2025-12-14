<?php

namespace TCG\Voyager\Services;

use TCG\Voyager\Models\Media;
use Illuminate\Support\Facades\Storage;

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

        $sanitizedName = $this->sanitizeFileName($nameWithoutExt);
        $transliteratedName = $this->transliterate($sanitizedName);

        $fileName = $transliteratedName . '.' . $extension;

        if (!Storage::disk($disk)->exists($dirPath . '/' . $fileName)) {
            return $fileName;
        }

        $counter = 1;
        while (Storage::disk($disk)->exists($dirPath . '/' . $transliteratedName . '_' . $counter . '.' . $extension)) {
            $counter++;
        }

        return $transliteratedName . '_' . $counter . '.' . $extension;
    }

    protected function transliterate(string $str): string
    {
        $translitMap = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
            'е' => 'e', 'ё' => 'yo', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
            'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
            'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch',
            'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '',
            'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
            'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D',
            'Е' => 'E', 'Ё' => 'Yo', 'Ж' => 'Zh', 'З' => 'Z', 'И' => 'I',
            'Й' => 'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N',
            'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T',
            'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C', 'Ч' => 'Ch',
            'Ш' => 'Sh', 'Щ' => 'Sch', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '',
            'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya',
        ];

        $result = '';
        foreach (mb_str_split($str) as $char) {
            $result .= $translitMap[$char] ?? $char;
        }

        return $result;
    }

    protected function sanitizeFileName(string $fileName): string
    {
        $fileName = preg_replace('/[^\p{L}\p{N}\s._-]/u', '', $fileName);
        $fileName = preg_replace('/\s+/', '_', trim($fileName));
        $fileName = preg_replace('/_+/', '_', $fileName);
        $fileName = trim($fileName, '_');

        return $fileName ?: 'file';
    }
}
