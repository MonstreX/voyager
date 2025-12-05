{{-- Editors assets: loaded once per page --}}
@once
@push('javascript')
<script type="module" src="{{ voyager_asset('js/editors.js') }}"></script>
@endpush
@endonce
