<div id="{{ $row->field }}" data-theme="{{ @$options->theme }}" data-language="{{ @$options->language }}" class="ace_editor min_height_200" name="{{ $row->field }}">{{ old($row->field, $dataTypeContent->{$row->field} ?? $options->default ?? '') }}</div>
<textarea name="{{ $row->field }}" id="{{ $row->field }}_textarea" class="hidden">{{ old($row->field, $dataTypeContent->{$row->field} ?? $options->default ?? '') }}</textarea>

@include('voyager::partials.editors-assets')

@push('javascript')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.Voyager && typeof window.Voyager.loadEditors === 'function') {
                window.Voyager.loadEditors()
                    .then(function(module) {
                        var initAceEditors = module && module.initAceEditors
                            ? module.initAceEditors
                            : window.VoyagerInitAceEditors;
                        if (typeof initAceEditors === 'function') {
                            var fieldWrapper = document.getElementById('{{ $row->field }}');
                            initAceEditors(fieldWrapper ? fieldWrapper.parentElement || fieldWrapper : document);
                        }
                    })
                    .catch(function(error) {
                        console.error('[Voyager] Failed to initialize ACE field "{{ $row->field }}"', error);
                    });
            }
        });
    </script>
@endpush
