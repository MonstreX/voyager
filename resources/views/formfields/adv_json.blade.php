<div class="adv-json-wrapper">
    @php
        $fieldsData = json_decode($dataTypeContent->{$row->field});
        if (!$fieldsData) {
            $fieldsData = (object)[
                'fields' => $options->json_fields ?? new stdClass(),
                'rows' => []
            ];
        }
    @endphp

    <!-- Hidden input to store JSON -->
    <input id="{{ $row->field }}" name="{{ $row->field }}" type="hidden" value="{{ $dataTypeContent->{$row->field} ?? '' }}">

    <!-- List of rows (sortable) -->
    <div id="adv-json-list-{{ $row->field }}" class="adv-json-list" data-field="{{ $row->field }}">
        @foreach($fieldsData->rows as $key => $item)
        <div class="adv-json-item">
            <div class="adv-json-drag-handle">
                <span></span>
                <span></span>
                <span></span>
            </div>
            @foreach($fieldsData->fields as $fieldKey => $fieldLabel)
            <div class="form-group-line">
                <input type="text"
                       class="form-control"
                       data-master-field="{{ $row->field }}"
                       data-field="{{ $fieldKey }}"
                       data-title="{{ $fieldLabel }}"
                       value="{{ $item->{$fieldKey} ?? '' }}"
                       placeholder="{{ $fieldLabel }}">
            </div>
            @endforeach
            <button type="button" class="btn btn-danger remove-json" data-field="{{ $row->field }}">
                <i class="voyager-x"></i>
            </button>
        </div>
        @endforeach
    </div>

    <!-- Form to add new rows -->
    <div class="adv-json-add-holder">
        <div class="adv-json-add-form">
            <div class="adv-json-drag-handle">
                <span></span>
                <span></span>
                <span></span>
            </div>
            @foreach($fieldsData->fields as $fieldKey => $fieldLabel)
            <div class="form-group-line">
                <label for="{{ $row->field }}-{{ $fieldKey }}">{{ $fieldLabel }}</label>
                <input id="{{ $row->field }}-{{ $fieldKey }}"
                       type="text"
                       class="form-control"
                       data-field="{{ $fieldKey }}"
                       data-title="{{ $fieldLabel }}"
                       placeholder="{{ $fieldLabel }}">
            </div>
            @endforeach
            <button type="button" class="btn btn-success add-json" data-field="{{ $row->field }}">
                <i class="voyager-list-add"></i>
            </button>
        </div>
    </div>
</div>
