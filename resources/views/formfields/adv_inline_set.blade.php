@php
    $inlineConfig = $row->details->inline_set ?? null;
    $inlineFields = $inlineConfig->fields ?? null;
    $many = (bool) ($inlineConfig->many ?? true);
    $columns = (int) ($inlineConfig->columns ?? 1);

    $rawValue = old($row->field, $dataTypeContent->{$row->field} ?? '[]');
    $inlineSource = json_decode(!empty($rawValue) ? $rawValue : '[]', true);
    if (!is_array($inlineSource)) {
        $inlineSource = [];
    }

    $rowIds = collect($inlineSource)
        ->map(function ($item) { return isset($item['row_id']) ? (int) $item['row_id'] : null; })
        ->filter()
        ->values()
        ->toArray();
@endphp

<div class="adv-inline-set-wrapper">
    @if($inlineFields)
        <div id="{{ $row->field }}_list"
             class="adv-inline-set-list"
             data-field="{{ $row->field }}"
             data-many="{{ $many ? '1' : '0' }}">

            <input class="adv-inline-set-row-ids"
                   type="hidden"
                   name="{{ $row->field }}_row_ids"
                   value="{{ implode(',', $rowIds) }}">
            <input class="adv-inline-set-deleted-media"
                   type="hidden"
                   name="{{ $row->field }}_deleted_media_ids"
                   value="">

            @if (!empty($inlineSource))
                @foreach($inlineSource as $source)
                    @include('voyager::formfields.adv_inline_set_item', [
                        'columns' => $columns,
                        'row_id' => $source['row_id'] ?? null,
                        'source' => $source,
                        'row_field' => $row->field,
                        'inline_fields' => $inlineFields,
                    ])
                @endforeach
            @endif
        </div>

        @include('voyager::formfields.adv_inline_set_item', [
            'columns' => $columns,
            'row_id' => null,
            'source' => null,
            'row_field' => $row->field,
            'inline_fields' => $inlineFields,
        ])

        @if ($many || empty($inlineSource))
            <div class="adv-inline-set-actions">
                <button type="button" class="btn btn-success add-inline-set">
                    {{ __('voyager::generic.add_new_inline_set') }}
                </button>
            </div>
        @endif
    @else
        {{ __('voyager::generic.no_inline_set_data') }}
    @endif
</div>
