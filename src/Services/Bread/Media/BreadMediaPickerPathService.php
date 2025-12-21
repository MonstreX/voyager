<?php

namespace TCG\Voyager\Services\Bread\Media;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BreadMediaPickerPathService
{
    public function renameFoldersIfNeeded(Request $request, string $slug, $rows, $data): void
    {
        if (!$request->session()->has($slug.'_path') && !$request->session()->has($slug.'_uuid')) {
            return;
        }

        $oldPath = $request->session()->get($slug.'_path');
        $uuid = $request->session()->get($slug.'_uuid');

        if (!$oldPath || !$uuid) {
            return;
        }

        $newPath = str_replace($uuid, $data->getKey(), $oldPath);
        $folderPath = substr($oldPath, 0, strpos($oldPath, $uuid)).$uuid;

        $rows->where('type', 'media_picker')->each(function ($row) use ($data, $uuid) {
            $data->{$row->field} = str_replace($uuid, $data->getKey(), $data->{$row->field});
        });

        $data->save();

        $disk = Storage::disk(config('voyager.storage.disk'));

        if (
            $oldPath != $newPath &&
            !$disk->exists($newPath) &&
            $disk->exists($oldPath)
        ) {
            $request->session()->forget([$slug.'_path', $slug.'_uuid']);
            $disk->move($oldPath, $newPath);
            $disk->deleteDirectory($folderPath);
        }
    }
}
