<?php

namespace TCG\Voyager\Widgets;

use TCG\Voyager\Facades\Voyager;

class SystemInfoDimmer extends BaseDimmer
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
        $phpVersion = PHP_VERSION;
        $laravelVersion = app()->version();
        $environment = app()->environment();
        $timezone = config('app.timezone', 'UTC');
        $locale = app()->getLocale();

        $lines = [
            "PHP: {$phpVersion}",
            "Laravel: {$laravelVersion}",
            "Env: {$environment}",
            "TZ: {$timezone}",
            "Locale: {$locale}",
        ];

        return view('voyager::dimmer', array_merge($this->config, [
            'icon'   => 'voyager-rocket',
            'title'  => 'System Info',
            'text'   => implode('<br>', $lines),
            'image' => voyager_asset('images/widget-backgrounds/01.jpg'),
        ]));
    }
}
