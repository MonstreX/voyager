<textarea class="form-control richTextBox" name="{{ $row->field }}" id="richtext{{ $row->field }}">
    {{ old($row->field, $dataTypeContent->{$row->field} ?? '') }}
</textarea>

@push('javascript')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var joditOptions = {!! json_encode($options ?? (object)[]) !!};
            
            // Extend options with Voyager-specific configurations
            joditOptions.type_slug = '{{ $dataType->slug }}';
            joditOptions.upload_url = '{{ route('voyager.upload') }}';

            if (window.VoyagerInitJodit) {
                window.VoyagerInitJodit('textarea.richTextBox[name="{{ $row->field }}"]', joditOptions);
            }
        });
    </script>
@endpush
