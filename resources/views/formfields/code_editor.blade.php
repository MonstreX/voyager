@php
    $aceRawValue = old($row->field, $dataTypeContent->{$row->field} ?? $options->default ?? '');
    $aceLanguage = isset($options->language) ? (string) $options->language : '';
    $aceDisplayValue = $aceRawValue;

    // Pretty-print JSON on display because model casts (array/json) will re-encode compactly on save.
    if ($aceLanguage === 'json' && is_string($aceRawValue)) {
        try {
            $decoded = json_decode($aceRawValue, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $aceDisplayValue = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        } catch (\Throwable $e) {
            // Keep raw
        }
    }
@endphp

<div id="{{ $row->field }}" data-theme="{{ @$options->theme }}" data-language="{{ @$options->language }}" class="ace_editor min_height_200" name="{{ $row->field }}">{{ $aceDisplayValue }}</div>
<textarea name="{{ $row->field }}" id="{{ $row->field }}_textarea" class="hidden">{{ $aceDisplayValue }}</textarea>

@include('voyager::partials.editors-assets')
