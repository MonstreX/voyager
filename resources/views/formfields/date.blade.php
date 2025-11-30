@php
    $rawValue = old($row->field, $dataTypeContent->{$row->field} ?? null);
    try {
        $rawValue = $rawValue ? \Carbon\Carbon::parse($rawValue)->format('Y-m-d') : '';
    } catch (\Exception $e) {
        $rawValue = $rawValue ?? '';
    }

    $flatpickrOptions = [
        'altInput' => true,
        'altFormat' => $options->altFormat ?? 'F j, Y',
        'dateFormat' => 'Y-m-d',
    ];

    if (!empty($options->flatpickr)) {
        $flatpickrOptions = array_merge($flatpickrOptions, (array) $options->flatpickr);
    } elseif (!empty($options->datepicker)) {
        $flatpickrOptions = array_merge($flatpickrOptions, (array) $options->datepicker);
    }
@endphp

<input type="text" class="form-control voyager-date-input" name="{{ $row->field }}"
       data-flatpickr-type="date"
       data-flatpickr='@json($flatpickrOptions, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)'
       placeholder="{{ $row->getTranslatedAttribute('display_name') }}"
       autocomplete="off"
       value="{{ $rawValue }}">
