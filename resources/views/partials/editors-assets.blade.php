{{-- Editors assets: loaded once per page --}}
@once
@push('javascript')
<script>
    if (window.Voyager && typeof window.Voyager.loadEditors === 'function') {
        window.Voyager.loadEditors().catch(function () {});
    }
</script>
@endpush
@endonce
