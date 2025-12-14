@php
    $modalId = $id ?? 'voyager-confirm-'.uniqid();
    $title = $title ?? __('voyager::generic.are_you_sure');
    $message = $message ?? __('voyager::generic.are_you_sure_delete');
    $confirmText = $confirmText ?? __('voyager::generic.delete_confirm');
    $cancelText = $cancelText ?? __('voyager::generic.cancel');
    $confirmClass = trim(($confirmClass ?? 'btn-danger').' '.($confirmButtonClass ?? ''));
    $confirmButtonId = $confirmButtonId ?? null;
    $icon = $icon ?? 'voyager-trash';
@endphp

<div class="modal fade modal-danger" id="{{ $modalId }}" tabindex="-1" role="dialog" aria-hidden="true" data-voyager-confirm-modal>
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="{{ $cancelText }}"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">
                    <i class="{{ $icon }}"></i> {!! $title !!}
                </h4>
            </div>
            <div class="modal-body">
                <div class="voyager-confirm-message">{!! $message !!}</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default pull-right" data-dismiss="modal">{{ $cancelText }}</button>
                <button type="button"
                        class="btn {{ $confirmClass }} pull-right"
                        @if($confirmButtonId) id="{{ $confirmButtonId }}" @endif
                        data-voyager-confirm-accept>
                    {{ $confirmText }}
                </button>
            </div>
        </div>
    </div>
</div>
