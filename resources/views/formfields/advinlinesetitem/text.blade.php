@php
    $default = $field->default ?? '';
    $value = $source && isset($source[$key_field]) ? $source[$key_field] : $default;
@endphp
<input
    type="text"
    id="{{ $row_field }}_{{ $key_field }}_{{ $row_id ?? '%id%' }}"
    name="{{ $row_field }}_{{ $key_field }}_{{ $row_id ?? '%id%' }}"
    value="{{ $value }}"
    data-field-type="{{ $field->type }}"
    class="adv-form-control form-control"
    @include('voyager::formfields.advinlinesetitem.attr')
>

