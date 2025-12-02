<textarea class="form-control richTextBox" name="{{ $row->field }}" id="richtext{{ $row->field }}">
    {{ old($row->field, $dataTypeContent->{$row->field} ?? '') }}
</textarea>

@push('javascript')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var additionalConfig = {
                selector: 'textarea.richTextBox[name="{{ $row->field }}"]',
            };

            var tinymceOverrides = {!! json_encode($options->tinymceOptions ?? (object)[]) !!} || {};
            additionalConfig = Object.assign(additionalConfig, tinymceOverrides);

            if (window.loadVoyagerTinyMCE) {
                window.loadVoyagerTinyMCE()
                    .then(function () {
                        window.tinymce.init(window.voyagerTinyMCE.getConfig(additionalConfig));
                    })
                    .catch(function (error) {
                        console.error('TinyMCE failed to load', error);
                    });
            }
        });
    </script>
@endpush
