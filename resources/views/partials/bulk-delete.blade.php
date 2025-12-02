<a class="btn btn-danger" id="bulk_delete_btn"><i class="voyager-trash"></i> <span>{{ __('voyager::generic.bulk_delete') }}</span></a>

{{-- Bulk delete modal --}}
<div class="modal modal-danger fade" tabindex="-1" id="bulk_delete_modal" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">
                    <i class="voyager-trash"></i> {{ __('voyager::generic.are_you_sure_delete') }} <span id="bulk_delete_count"></span> <span id="bulk_delete_display_name"></span>?
                </h4>
            </div>
            <div class="modal-body" id="bulk_delete_modal_body">
            </div>
            <div class="modal-footer">
                <form action="{{ route('voyager.'.$dataType->slug.'.index') }}/0" id="bulk_delete_form" method="POST">
                    {{ method_field("DELETE") }}
                    {{ csrf_field() }}
                    <input type="hidden" name="ids" id="bulk_delete_input" value="">
                    <input type="submit" class="btn btn-danger pull-right delete-confirm"
                             value="{{ __('voyager::generic.bulk_delete_confirm') }} {{ strtolower($dataType->getTranslatedAttribute('display_name_plural')) }}">
                </form>
                <button type="button" class="btn btn-default pull-right" data-dismiss="modal">
                    {{ __('voyager::generic.cancel') }}
                </button>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<script>
window.addEventListener('load', function () {
    const bulkDeleteBtn = document.getElementById('bulk_delete_btn');
    const bulkDeleteModal = document.getElementById('bulk_delete_modal');
    const bulkDeleteCount = document.getElementById('bulk_delete_count');
    const bulkDeleteDisplayName = document.getElementById('bulk_delete_display_name');
    const bulkDeleteInput = document.getElementById('bulk_delete_input');
    const bootstrapCompat = window.VoyagerBootstrapCompat;

    const showModal = (modal) => {
        if (!modal) {
            return;
        }
        if (bootstrapCompat && typeof bootstrapCompat.showModal === 'function') {
            bootstrapCompat.showModal(modal);
            return;
        }
        modal.classList.add('in');
        modal.style.display = 'block';
        modal.setAttribute('aria-hidden', 'false');
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade in';
        backdrop.dataset.modalTarget = modal.id;
        document.body.appendChild(backdrop);
        document.body.classList.add('modal-open');
    };

    if (bulkDeleteModal && bulkDeleteModal.parentElement !== document.body) {
        document.body.appendChild(bulkDeleteModal);
    }

    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener('click', function () {
            const ids = [];
            const checkedBoxes = Array.from(document.querySelectorAll('#dataTable input[type="checkbox"]:checked'))
                .filter((checkbox) => !checkbox.classList.contains('select_all'));
            const count = checkedBoxes.length;
            if (!count) {
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
            showModal(bulkDeleteModal);
        });
    }
});
</script>
