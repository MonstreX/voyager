<textarea class="form-control richTextBox"
          name="{{ $row->field }}"
          id="richtext{{ $row->field }}"
          data-voyager-rich-text="true"
          data-type-slug="{{ $dataType->slug }}"
          data-upload-url="{{ route('voyager.upload') }}"
          data-jodit-options='@json($options ?? (object)[], JSON_HEX_APOS | JSON_HEX_QUOT)'>{{ old($row->field, $dataTypeContent->{$row->field} ?? '') }}</textarea>

@include('voyager::partials.editors-assets')
