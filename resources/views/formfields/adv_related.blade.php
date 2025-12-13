@if ($related_options = isset($options->related_model) ? $options->related_model : null)
<div class="adv-related-wrapper">
    <div id="adv-related-list-{{$row->field}}" data-field="{{$row->field}}" class="adv-related-list">
    @if (!empty($dataTypeContent->{$row->field}))
        @foreach(json_decode($dataTypeContent->{$row->field}) as $item)
        @php
            if (isset($item->display_field) && isset($item->fields)) {
                $displayValue = $item->fields->{$item->display_field};
                $itemData = $item;
            } else {
                $displayField = $related_options->display_field;
                $displayValue = $item->{$displayField};
                $itemData = (object)[
                    'display_field' => $displayField,
                    'fields' => $item
                ];
            }
        @endphp
        <div class="adv-related-item" data-data="{{ json_encode($itemData) }}">
            <div class="adv-related-item__handle"><span></span><span></span><span></span></div>
            <div class="adv-related-item__title">{{ $displayValue }}</div>
            <div class="adv-related-item__remove">
                <button data-field="{{ $row->field }}" type="button" class="btn btn-danger remove-related"><i class='voyager-x'></i></button>
            </div>
        </div>
        @endforeach
    @endif
    </div>
    <div class="adv-related-add-holder">
        <div class="adv-related-add-form">
            <div class="adv-related-add-autocomplete">
                <input class="related-autocomplete"
                       id="adv-related-autocomplete-{{$row->field}}"
                       name="adv-related-autocomplete-{{$row->field}}"
                       type="text"
                       placeholder="{{ __('voyager::generic.search') }}"
                       data-field="{{$row->field}}"
                       data-url="{{ route('voyager.related-records.search') }}"
                       data-slug="{{ $related_options->source }}"
                       data-search-field="{{ $related_options->search_field }}"
                       data-display-field="{{ $related_options->display_field }}"
                       data-fields="{{ implode(',', $related_options->fields) }}"
                        >
                <button data-field="{{$row->field}}" type="button" disabled class="btn btn-success add-related"><i class='voyager-list-add'></i></button>
            </div>
        </div>
    </div>
    <input id="{{$row->field}}" name="{{$row->field}}" type="hidden" value="{{ $dataTypeContent->{$row->field} }}">
</div>
@else
<div class="adv-related-no-options">{{ __('voyager::generic.no_results') }}: {{ $row->field }}</div>
@endif
