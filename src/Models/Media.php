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
        return '/storage/' . $this->path;
    }

    public function fullUrl()
    {
        return url('/storage/' . $this->path);
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
        $this->attributes['props'] = json_encode($props);
        return $this;
    }

    public function getPropsAttribute()
    {
        $propsValue = $this->attributes['props'] ?? null;
        return $propsValue ? json_decode($propsValue, true) : [];
    }

    public function setPropsAttribute($value)
    {
        $this->attributes['props'] = is_array($value) ? json_encode($value) : $value;
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
