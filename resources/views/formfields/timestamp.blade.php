@php
    $rawValue = old($row->field, $dataTypeContent->{$row->field} ?? null);
    try {
        $rawValue = $rawValue ? \Carbon\Carbon::parse($rawValue)->format('Y-m-d\\TH:i') : '';
    } catch (\Exception $e) {
        $rawValue = $rawValue ?? '';
    }

    $flatpickrOptions = [
        'enableTime' => true,
        'altInput' => true,
        'altFormat' => $options->altFormat ?? 'F j, Y h:i K',
        'dateFormat' => 'Y-m-d\\TH:i',
        'time_24hr' => $options->time_24hr ?? false,
    ];

    if (!empty($options->flatpickr)) {
        $flatpickrOptions = array_merge($flatpickrOptions, (array) $options->flatpickr);
    } elseif (!empty($options->datepicker)) {
        $flatpickrOptions = array_merge($flatpickrOptions, (array) $options->datepicker);
    }

    $placeholder = old($row->field, $options->placeholder ?? $row->getTranslatedAttribute('display_name'));
@endphp
<input @if($row->required == 1) required @endif type="text" class="form-control voyager-datetime-input"
       name="{{ $row->field }}"
       data-flatpickr-type="datetime"
       data-flatpickr="{{ e(json_encode($flatpickrOptions, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) }}"
       placeholder="{{ $placeholder }}"
       autocomplete="off"
       value="{{ $rawValue }}">
