<br>
<?php $checked = false; ?>
@if(isset($options->options))
    @php
        $inline = property_exists($options, 'inline') ? (bool)$options->inline : true;
    @endphp
    @foreach($options->options as $key => $label)
        @if(isset($dataTypeContent->{$row->field}) || old($row->field))
            @php
                $checkedData = old($row->field, $dataTypeContent->{$row->field});
                if (!is_array($checkedData)) {
                    $decoded = is_string($checkedData) ? json_decode($checkedData, true) : null;
                    if (is_array($decoded)) {
                        $checkedData = $decoded;
                    }
                }
                if (!is_array($checkedData)) {
                    $checkedData = array_filter([$checkedData], static function ($value) {
                        return $value !== null && $value !== '';
                    });
                }
                $checked = in_array($key, $checkedData, false) || in_array((string) $key, $checkedData, false);
            @endphp
        @else
            <?php $checked = isset($options->checked) && $options->checked ? true : false; ?>
        @endif

        <label style="{{ $inline ? 'display:inline-block;margin-right:15px;' : 'display:block;margin-bottom:5px;' }}">
            <input type="checkbox" name="{{ $row->field }}[{{$key}}]" {!! $checked ? 'checked="checked"' : '' !!} value="{{$key}}" id="{{$key}}"/>
            <span>{{$label}}</span>
        </label>
    @endforeach
@endif
