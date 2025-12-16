@php
    $default = isset($field->default) && $field->default === 'on' ? 1 : 0;
    $checked = $source ? (isset($source[$key_field]) && (int) $source[$key_field] === 1 ? 1 : 0) : $default;
    $checked = !$row_id ? $default : $checked;
    $onLabel = $field->on ?? 'On';
    $offLabel = $field->off ?? 'Off';
@endphp

<input type="hidden" name="{{ $row_field }}_{{ $key_field }}_{{ $row_id ?? '%id%' }}" value="0">
<input
    type="checkbox"
    id="{{ $row_field }}_{{ $key_field }}_{{ $row_id ?? '%id%' }}"
    name="{{ $row_field }}_{{ $key_field }}_{{ $row_id ?? '%id%' }}"
    data-field-type="{{ $field->type }}"
    class="adv-form-control toggleswitch"
    data-on="{{ $onLabel }}"
    data-off="{{ $offLabel }}"
    data-onstyle="primary"
    data-offstyle="default"
    @if($checked) checked="checked" @endif
    @include('voyager::formfields.advinlinesetitem.attr')
>

