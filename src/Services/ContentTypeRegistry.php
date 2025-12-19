<?php

namespace TCG\Voyager\Services;

use TCG\Voyager\Http\Controllers\ContentTypes\AdvInlineSetContentType;
use TCG\Voyager\Http\Controllers\ContentTypes\Checkbox;
use TCG\Voyager\Http\Controllers\ContentTypes\Coordinates;
use TCG\Voyager\Http\Controllers\ContentTypes\File;
use TCG\Voyager\Http\Controllers\ContentTypes\Image as ContentImage;
use TCG\Voyager\Http\Controllers\ContentTypes\MultipleCheckbox;
use TCG\Voyager\Http\Controllers\ContentTypes\MultipleImage;
use TCG\Voyager\Http\Controllers\ContentTypes\Password;
use TCG\Voyager\Http\Controllers\ContentTypes\Relationship;
use TCG\Voyager\Http\Controllers\ContentTypes\SelectMultiple;
use TCG\Voyager\Http\Controllers\ContentTypes\Text;
use TCG\Voyager\Http\Controllers\ContentTypes\Timestamp;

class ContentTypeRegistry
{
    public function resolve(string $type): ?string
    {
        $map = $this->defaultMap();

        $configured = config('voyager.content_types', []);
        if (is_array($configured) && count($configured) > 0) {
            $map = array_merge($map, $configured);
        }

        return $map[$type] ?? Text::class;
    }

    private function defaultMap(): array
    {
        return [
            'password' => Password::class,
            'checkbox' => Checkbox::class,
            'multiple_checkbox' => MultipleCheckbox::class,
            'file' => File::class,
            'multiple_images' => MultipleImage::class,
            'select_multiple' => SelectMultiple::class,
            'image' => ContentImage::class,
            'date' => Timestamp::class,
            'timestamp' => Timestamp::class,
            'coordinates' => Coordinates::class,
            'relationship' => Relationship::class,
            'adv_fields_group' => \TCG\Voyager\Http\Controllers\ContentTypes\AdvFieldsGroupContentType::class,
            'adv_inline_set' => AdvInlineSetContentType::class,
            'adv_media_files' => null,
        ];
    }
}

