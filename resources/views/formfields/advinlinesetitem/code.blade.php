@php
    $editorId = $row_field . '_' . $key_field . '_' . ($row_id ?? '%id%') . '_ace';
    $language = isset($field->mode) ? $field->mode : 'html';
    $theme = isset($field->theme) ? $field->theme : 'github';
    $minLines = isset($field->minlines) ? $field->minlines : 4;
    $maxLines = isset($field->maxlines) ? $field->maxlines : null;
@endphp

<div
    id="{{ $editorId }}"
    class="adv-form-control ace_editor"
    data-language="{{ $language }}"
    data-theme="{{ $theme }}"
    data-min-lines="{{ $minLines }}"
    @if($maxLines) data-max-lines="{{ $maxLines }}" @endif
    data-field-type="{{ $field->type }}"
    @include('voyager::formfields.advinlinesetitem.attr')
>{{ $source ? ($source[$key_field] ?? '') : '' }}</div>

<textarea
    id="{{ $editorId }}_textarea"
    class="adv-form-control form-control hidden"
    name="{{ $row_field }}_{{ $key_field }}_{{ $row_id ?? '%id%' }}"
    data-field-type="{{ $field->type }}"
    style="display:none;"
>{{ $source ? ($source[$key_field] ?? '') : '' }}</textarea>
