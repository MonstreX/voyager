<?php

namespace TCG\Voyager\Widgets;

use Illuminate\Support\Facades\Storage;

class StorageStatusDimmer extends BaseDimmer
{
    /**
     * The configuration array.
     *
     * @var array
     */
    protected $config = [];

    /**
     * Treat this method as a controller action.
     * Return view() or other content to display.
     */
    public function run()
    {
        $disk = config('voyager.storage.disk', 'public');
        $driver = config("filesystems.disks.{$disk}.driver", 'unknown');

        $path = $this->diskPath($disk);
        $writable = $path ? (is_writable($path) ? 'yes' : 'no') : 'n/a';
        $freeSpace = $path ? $this->formatBytes(@disk_free_space($path)) : 'n/a';

        $lines = [
            "Disk: {$disk}",
            "Driver: {$driver}",
            "Writable: {$writable}",
            "Free: {$freeSpace}",
        ];

        return view('voyager::dimmer', array_merge($this->config, [
            'icon'   => 'voyager-folder',
            'title'  => 'Storage Status',
            'text'   => implode('<br>', $lines),
            'image' => voyager_asset('images/widget-backgrounds/03.jpg'),
        ]));
    }

    private function diskPath($disk)
    {
        try {
            $storage = Storage::disk($disk);

            if (method_exists($storage, 'path')) {
                return $storage->path('');
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }

    private function formatBytes($bytes)
    {
        if (!is_numeric($bytes)) {
            return 'n/a';
        }

        $size = (float) $bytes;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return number_format($size, 1, '.', '') . ' ' . $units[$unit];
    }
}
