<?php

namespace TCG\Voyager\Services\Bread;

use Illuminate\Http\Request;
use TCG\Voyager\Services\ContentTypeRegistry;

class BreadContentService
{
    public function __construct(protected ContentTypeRegistry $registry)
    {
    }

    public function getContent(Request $request, string $slug, $row, $options = null)
    {
        $contentType = $this->registry->resolve($row->type);
        if (!$contentType) {
            return null;
        }

        return (new $contentType($request, $slug, $row, $options))->handle();
    }
}
