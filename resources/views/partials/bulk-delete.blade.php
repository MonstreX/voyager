<a class="btn btn-danger"
   id="bulk_delete_btn"
   data-confirm-target="#bulk_delete_modal"
   data-confirm-form="#bulk_delete_form">
    <i class="voyager-trash"></i> <span>{{ __('voyager::generic.bulk_delete') }}</span>
</a>

{{-- Bulk delete modal --}}
@include('voyager::components.modal-confirm', [
    'id' => 'bulk_delete_modal',
    'title' => __('voyager::generic.are_you_sure_delete').' <span id="bulk_delete_count"></span> <span id="bulk_delete_display_name"></span>?',
    'message' => '<div id="bulk_delete_modal_body"></div>',
    'confirmText' => __('voyager::generic.bulk_delete_confirm').' '.strtolower($dataType->getTranslatedAttribute('display_name_plural')),
    'confirmClass' => 'btn-danger delete-confirm',
    'confirmButtonId' => 'bulk_delete_confirm',
    'icon' => 'voyager-trash'
])
<form action="{{ route('voyager.'.$dataType->slug.'.index') }}/0" id="bulk_delete_form" method="POST" style="display:none">
    {{ method_field("DELETE") }}
    {{ csrf_field() }}
    <input type="hidden" name="ids" id="bulk_delete_input" value="">
</form>

<script>
window.addEventListener('load', function () {
    const bulkDeleteBtn = document.getElementById('bulk_delete_btn');
    const bulkDeleteModal = document.getElementById('bulk_delete_modal');
    const bulkDeleteCount = document.getElementById('bulk_delete_count');
    const bulkDeleteDisplayName = document.getElementById('bulk_delete_display_name');
    const bulkDeleteInput = document.getElementById('bulk_delete_input');

    if (bulkDeleteModal && bulkDeleteModal.parentElement !== document.body) {
        document.body.appendChild(bulkDeleteModal);
    }

    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener('click', function (event) {
            const ids = [];
            const checkedBoxes = Array.from(document.querySelectorAll('#dataTable input[type="checkbox"]:checked'))
                .filter((checkbox) => !checkbox.classList.contains('select_all'));
            const count = checkedBoxes.length;
            if (!count) {
                event.preventDefault();
                event.stopPropagation();
                toastr.warning('{{ __('voyager::generic.bulk_delete_nothing') }}');
                return;
            }

            checkedBoxes.forEach((checkbox) => {
                ids.push(checkbox.value);
            });

            if (bulkDeleteCount) {
                bulkDeleteCount.textContent = count;
            }
            if (bulkDeleteDisplayName) {
                const displayName = count > 1
                    ? '{{ $dataType->getTranslatedAttribute('display_name_plural') }}'
                    : '{{ $dataType->getTranslatedAttribute('display_name_singular') }}';
                bulkDeleteDisplayName.textContent = displayName.toLowerCase();
            }
            if (bulkDeleteInput) {
                bulkDeleteInput.value = ids.join(',');
            }
        }, true);
    }
});
</script>
