<?php

namespace TCG\Voyager\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    protected $table = 'media';

    protected $guarded = [];

    public function model()
    {
        return $this->morphTo();
    }

    public function url()
    {
        $disk = $this->disk ?? 'public';
        $path = $this->path;

        if ($disk === 'public') {
            return asset('storage/' . $path);
        }

        return \Storage::disk($disk)->url($path);
    }

    public function fullUrl()
    {
        return url($this->url());
    }

    public function fileName()
    {
        return $this->file_name;
    }

    public function prop($key, $default = null)
    {
        $props = $this->props ? json_decode($this->props, true) : [];
        return $props[$key] ?? $default;
    }

    public function setProp($key, $value)
    {
        $props = $this->props ? json_decode($this->props, true) : [];
        $props[$key] = $value;
        $this->props = json_encode($props);
        return $this;
    }

    public function getPropsAttribute()
    {
        return $this->props ? json_decode($this->props, true) : [];
    }

    public function sizeForHumans()
    {
        if (!$this->size) {
            return null;
        }

        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function isImage()
    {
        if (!$this->mime_type) {
            return false;
        }

        return strpos($this->mime_type, 'image/') === 0;
    }
}
