@php
    $default = $field->default ?? '';
    $value = $source && array_key_exists($key_field, $source) ? $source[$key_field] : $default;
@endphp
<input
    type="number"
    id="{{ $row_field }}_{{ $key_field }}_{{ $row_id ?? '%id%' }}"
    name="{{ $row_field }}_{{ $key_field }}_{{ $row_id ?? '%id%' }}"
    value="{{ $value }}"
    data-field-type="{{ $field->type }}"
    class="adv-form-control form-control"
    @include('voyager::formfields.advinlinesetitem.attr')
>

