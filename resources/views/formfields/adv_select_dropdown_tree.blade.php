@if(isset($options->relationship))
    {{-- Check if relationship method exists --}}
    @if( !method_exists( $dataType->model_name, Str::camel($options->relationship->field) ) )
        <p class="label label-warning">
            <i class="voyager-warning"></i>
            {{ __('voyager::form.field_select_dd_relationship', [
                'method' => Str::camel($options->relationship->field).'()',
                'class' => $dataType->model_name
            ]) }}
        </p>
    @endif

    @if( method_exists( $dataType->model_name, Str::camel($options->relationship->field) ) )
        @php
            // Get selected value from relationship
            $selected_value = $dataTypeContent->{$options->relationship->field}?->{$options->relationship->key};

            // Build tree structure with level info
            $flatRecords = app($row->details->relationship->model)->get()->toArray();
            $tree = flat_to_tree($flatRecords);
            $treeOptions = build_flat_from_tree($tree);
        @endphp

        <select class="form-control select2" name="{{ $options->relationship->ref_field }}">
            <option value="0" @if(empty($selected_value)) selected="selected" @endif>
                {{ __('voyager::generic.none') }}
            </option>
            @foreach($treeOptions as $option)
                <option value="{{ $option['id'] }}"
                        @if($selected_value == $option['id']) selected="selected" @endif>
                    @if($option['level'] > 0){{ str_repeat("--", $option['level']) }} @endif
                    {{ $option[$options->relationship->label] }}
                </option>
            @endforeach
        </select>
    @endif
@endif
