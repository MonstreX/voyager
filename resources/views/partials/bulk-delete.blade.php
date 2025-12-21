<a class="btn btn-danger"
   id="bulk_delete_btn"
   data-confirm-target="#bulk_delete_modal"
   data-confirm-form="#bulk_delete_form"
   data-bulk-delete-table="#dataTable"
   data-bulk-delete-nothing="{{ __('voyager::generic.bulk_delete_nothing') }}"
   data-bulk-delete-plural="{{ $dataType->getTranslatedAttribute('display_name_plural') }}"
   data-bulk-delete-singular="{{ $dataType->getTranslatedAttribute('display_name_singular') }}">
    <i class="voyager-trash"></i> <span>{{ __('voyager::generic.bulk_delete') }}</span>
</a>

{{-- Bulk delete modal --}}
@include('voyager::components.modal-confirm', [
    'id' => 'bulk_delete_modal',
    'title' => __('voyager::generic.are_you_sure_delete').' <span id="bulk_delete_count"></span> <span id="bulk_delete_display_name"></span>?',
    'message' => '<div id="bulk_delete_modal_body"></div>',
    'confirmText' => __('voyager::generic.bulk_delete_confirm').' '.strtolower($dataType->getTranslatedAttribute('display_name_plural')),
    'confirmClass' => 'btn-danger delete-confirm',
    'icon' => 'voyager-trash'
])
<form action="{{ route('voyager.'.$dataType->slug.'.index') }}/0" id="bulk_delete_form" method="POST" style="display:none">
    {{ method_field("DELETE") }}
    {{ csrf_field() }}
    <input type="hidden" name="ids" id="bulk_delete_input" value="">
</form>
