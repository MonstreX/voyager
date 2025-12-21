<?php

namespace TCG\Voyager\Services\Bread\Write;

use Illuminate\Http\Request;

class BreadTranslationService
{
    public function prepare($data, Request $request): array
    {
        return is_bread_translatable($data) ? $data->prepareTranslations($request) : [];
    }

    public function persist($data, array $translations): void
    {
        if (count($translations) > 0) {
            $data->saveTranslations($translations);
        }
    }
}
