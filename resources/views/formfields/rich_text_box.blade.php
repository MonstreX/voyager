<textarea class="form-control richTextBox" name="{{ $row->field }}" id="richtext{{ $row->field }}">{{ old($row->field, $dataTypeContent->{$row->field} ?? '') }}</textarea>

@include('voyager::partials.editors-assets')

@push('javascript')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var joditOptions = {!! json_encode($options ?? (object)[]) !!};

            // Extend options with Voyager-specific configurations
            joditOptions.type_slug = '{{ $dataType->slug }}';
            joditOptions.upload_url = '{{ route('voyager.upload') }}';

            if (window.Voyager && typeof window.Voyager.loadEditors === 'function') {
                window.Voyager.loadEditors()
                    .then(function(module) {
                        var initJodit = module && module.initJodit
                            ? module.initJodit
                            : window.VoyagerInitJodit;
                        if (typeof initJodit === 'function') {
                            initJodit('textarea.richTextBox[name="{{ $row->field }}"]', joditOptions);
                        }
                    })
                    .catch(function(error) {
                        console.error('[Voyager] Failed to initialize rich text editor', error);
                    });
            }
        });
    </script>
@endpush
