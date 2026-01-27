<?php

namespace TCG\Voyager\Widgets;

class RuntimeLimitsDimmer extends BaseDimmer
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
        $memoryLimit = ini_get('memory_limit') ?: 'n/a';
        $maxExecutionTime = ini_get('max_execution_time') ?: 'n/a';
        $uploadMaxFilesize = ini_get('upload_max_filesize') ?: 'n/a';
        $postMaxSize = ini_get('post_max_size') ?: 'n/a';

        $lines = [
            "Memory: {$memoryLimit}",
            "Max Exec: {$maxExecutionTime}s",
            "Upload Max: {$uploadMaxFilesize}",
            "Post Max: {$postMaxSize}",
        ];

        return view('voyager::dimmer', array_merge($this->config, [
            'icon'   => 'voyager-tools',
            'title'  => 'Runtime Limits',
            'text'   => implode('<br>', $lines),
            'image' => voyager_asset('images/widget-backgrounds/02.jpg'),
        ]));
    }
}
