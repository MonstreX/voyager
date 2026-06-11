<div class="adv-fields-group-wrapper">
    @php
        if (!$fields = json_decode($dataTypeContent->{$row->field})) {
            $fields = $options;
        }
    @endphp

    @if ($fields && isset($fields->fields))
        @foreach ($fields->fields as $fieldKey => $field)
            @php
                $fieldValue = isset($field->value) ? $field->value : '';
                $fieldId = "{$row->field}_{$fieldKey}";
                $fieldName = "{$row->field}_{$fieldKey}";
                $fieldType = $field->type ?? 'text';
            @endphp

            <div class="form-group">
                <label for="{{ $fieldId }}">{{ $field->label ?? ucfirst($fieldKey) }}</label>

                @if ($fieldType === 'text')
                    <input type="text"
                           id="{{ $fieldId }}"
                           class="form-control"
                           name="{{ $fieldName }}"
                           value="{{ $fieldValue }}">

                @elseif ($fieldType === 'number')
                    <input type="number"
                           id="{{ $fieldId }}"
                           class="form-control"
                           name="{{ $fieldName }}"
                           value="{{ $fieldValue }}"
                           @if (isset($field->attributes->step)) step="{{ $field->attributes->step }}" @endif>

                @elseif ($fieldType === 'textarea')
                    <textarea id="{{ $fieldId }}"
                              class="form-control"
                              name="{{ $fieldName }}"
                              @if (isset($field->attributes->rows)) rows="{{ $field->attributes->rows }}" @endif>{{ $fieldValue }}</textarea>

                @endif

                @if (isset($field->description))
                    <small class="form-text text-muted">{{ $field->description }}</small>
                @endif
            </div>
        @endforeach
    @endif
</div>
