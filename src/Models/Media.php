<?php

namespace TCG\Voyager\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    protected $table = 'media';

    protected $guarded = [];

    protected static function jsonFlags(): int
    {
        $flags = JSON_UNESCAPED_UNICODE;
        if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $flags |= JSON_INVALID_UTF8_SUBSTITUTE;
        }

        return $flags;
    }

    public function model()
    {
        return $this->morphTo();
    }

    public function url()
    {
        $disk = $this->disk ?: config('voyager.storage.disk', 'public');

        try {
            return \Illuminate\Support\Facades\Storage::disk($disk)->url($this->path);
        } catch (\Exception $e) {
            return '/storage/' . $this->path;
        }
    }

    public function fullUrl()
    {
        return $this->url();
    }

    public function cacheBustedUrl(): string
    {
        $url = (string) $this->url();

        $version = $this->updated_at ? $this->updated_at->getTimestamp() : $this->id;
        $glue = str_contains($url, '?') ? '&' : '?';

        return $url.$glue.'v='.$version;
    }

    public function cacheBustedFullUrl(): string
    {
        return $this->cacheBustedUrl();
    }

    public function fileName()
    {
        return $this->file_name;
    }

    public function prop($key, $default = null)
    {
        $propsValue = $this->attributes['props'] ?? null;
        $props = $propsValue ? json_decode($propsValue, true) : [];
        return $props[$key] ?? $default;
    }

    public function setProp($key, $value)
    {
        $propsValue = $this->attributes['props'] ?? null;
        $props = $propsValue ? json_decode($propsValue, true) : [];
        $props[$key] = $value;
        $this->attributes['props'] = json_encode($props, static::jsonFlags());
        return $this;
    }

    public function getPropsAttribute()
    {
        $propsValue = $this->attributes['props'] ?? null;
        if (is_array($propsValue)) {
            return $propsValue;
        }

        return $propsValue ? json_decode($propsValue, true) : [];
    }

    public function setPropsAttribute($value)
    {
        $this->attributes['props'] = is_array($value) ? json_encode($value, static::jsonFlags()) : $value;
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
