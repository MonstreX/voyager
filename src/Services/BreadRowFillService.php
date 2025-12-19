<?php

namespace TCG\Voyager\Services;

use Illuminate\Http\Request;

class BreadRowFillService
{
    public function fillModelFromRows(Request $request, string $slug, $rows, $data, callable $getContent, array &$multiSelect): void
    {
        foreach ($rows as $row) {
            if ($this->shouldSkipRowForRequest($request, $row)) {
                continue;
            }

            if ($this->isBelongsToRelationshipRow($row)) {
                continue;
            }

            $content = $getContent($request, $slug, $row, $row->details);

            if ($this->isNonBelongsToManyRelationshipRow($row)) {
                $row->field = @$row->details->column;
            }

            $content = $this->mergeExistingMultipleFieldContentIfNeeded($row, $data, $content);
            $content = $this->applyNullContentFallbacks($request, $row, $data, $content);

            if ($this->isBelongsToManyRelationshipRow($row)) {
                $multiSelect[] = $this->buildBelongsToManySyncPayload($row, $content);
                continue;
            }

            $data->{$row->field} = $content;
        }
    }

    protected function shouldSkipRowForRequest(Request $request, $row): bool
    {
        if ($request->hasFile($row->field) || $request->has($row->field) || $row->type === 'checkbox') {
            return false;
        }

        if (isset($row->details->type) && $row->details->type !== 'belongsToMany') {
            return true;
        }

        return false;
    }

    protected function isBelongsToRelationshipRow($row): bool
    {
        return $row->type == 'relationship' && $row->details->type == 'belongsTo';
    }

    protected function isBelongsToManyRelationshipRow($row): bool
    {
        return $row->type == 'relationship' && $row->details->type == 'belongsToMany';
    }

    protected function isNonBelongsToManyRelationshipRow($row): bool
    {
        return $row->type == 'relationship' && $row->details->type != 'belongsToMany';
    }

    protected function mergeExistingMultipleFieldContentIfNeeded($row, $data, $content)
    {
        if (!in_array($row->type, ['multiple_images', 'file'], true) || is_null($content)) {
            return $content;
        }

        if (!isset($data->{$row->field})) {
            return $content;
        }

        $existingFiles = json_decode($data->{$row->field}, true);
        if (is_null($existingFiles)) {
            return $content;
        }

        return json_encode(array_merge($existingFiles, json_decode($content)));
    }

    protected function applyNullContentFallbacks(Request $request, $row, $data, $content)
    {
        if (!is_null($content)) {
            return $content;
        }

        if ($row->type == 'image' && is_null($request->input($row->field)) && isset($data->{$row->field})) {
            return $data->{$row->field};
        }

        if ($row->type == 'multiple_images' && is_null($request->input($row->field)) && isset($data->{$row->field})) {
            return $data->{$row->field};
        }

        if ($row->type == 'file') {
            $current = $data->{$row->field};
            return $current ? $current : json_encode([]);
        }

        if ($row->type == 'password') {
            return $data->{$row->field};
        }

        return $content;
    }

    protected function buildBelongsToManySyncPayload($row, $content): array
    {
        return [
            'model'           => $row->details->model,
            'content'         => $content,
            'table'           => $row->details->pivot_table,
            'foreignPivotKey' => $row->details->foreign_pivot_key ?? null,
            'relatedPivotKey' => $row->details->related_pivot_key ?? null,
            'parentKey'       => $row->details->parent_key ?? null,
            'relatedKey'      => $row->details->key,
        ];
    }
}

