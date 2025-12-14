@php
    $collectionName = $options->collection_name ?? $row->field;
    $deleteUrlTemplate = route('voyager.media-api.delete', ['media' => '__MEDIA_ID__']);
@endphp

<div class="form-group adv-image-wrapper">
    @php
        $media = null;
        if ($dataTypeContent && method_exists($dataTypeContent, 'getFirstMedia')) {
            $media = $dataTypeContent->getFirstMedia($collectionName);
        }
    @endphp

    @if ($media)
        <div class="adv-image" id="adv-image-{{ $row->field }}">
            <img src="{{ $media->fullUrl() }}" alt="{{ $media->prop('alt', $media->fileName()) }}" class="img-responsive">
            <div class="adv-image-fields">
                <div class="adv-image-file">
                    <span class="adv-image-file-name">{{ $media->fileName() }}</span>
                    <span class="adv-image-file-size">{{ $media->sizeForHumans() }}</span>
                </div>
                <input type="text" class="form-control" placeholder="Title" name="{{ $row->field }}_title" value="{{ $media->prop('title', '') }}">
                <input type="text" class="form-control" placeholder="Alt Text" name="{{ $row->field }}_alt" value="{{ $media->prop('alt', '') }}">
            </div>
            <button type="button" class="btn btn-sm btn-danger single-adv-image-remove" data-media-id="{{ $media->id }}" data-field="{{ $row->field }}" data-delete-url-template="{{ $deleteUrlTemplate }}">
                <i class="voyager-trash"></i> Delete
            </button>
        </div>
    @endif

    <div class="form-group">
        <input type="file" class="form-control" id="adv-image-input-{{ $row->field }}" name="{{ $row->field }}" accept="image/*">
    </div>
</div>

<!-- Modal for confirming delete -->
<div class="modal modal-danger fade" tabindex="-1" id="adv-image-delete-modal-{{ $row->field }}" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><i class="voyager-trash"></i> {{ __('voyager::generic.are_you_sure_delete') }}</h4>
            </div>
            <div class="modal-body">
                <p>{{ __('voyager::generic.delete_confirm') }}</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default pull-right" data-dismiss="modal">{{ __('voyager::generic.cancel') }}</button>
                <button type="button" class="btn btn-danger pull-right adv-image-delete-confirm" data-field="{{ $row->field }}">{{ __('voyager::generic.delete_confirm') }}</button>
            </div>
        </div>
    </div>
</div>
