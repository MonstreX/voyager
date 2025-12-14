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
        <input type="hidden" name="{{ $row->field }}_clear" value="0">
        <input type="file" class="form-control" id="adv-image-input-{{ $row->field }}" name="{{ $row->field }}" accept="image/*">
    </div>
</div>

<!-- Modal for confirming delete -->
@include('voyager::components.modal-confirm', [
    'id' => 'adv-image-delete-modal-'.$row->field,
    'title' => __('voyager::generic.are_you_sure_delete'),
    'message' => __('voyager::generic.delete_confirm'),
    'confirmText' => __('voyager::generic.delete_confirm'),
    'confirmClass' => 'btn-danger',
    'confirmButtonClass' => 'adv-image-delete-confirm',
    'icon' => 'voyager-trash'
])
