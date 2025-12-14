@php
    $collectionName = $options->collection_name ?? $row->field;
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
            <button type="button" class="btn btn-sm btn-danger single-adv-image-remove" data-media-id="{{ $media->id }}" data-field="{{ $row->field }}">
                <i class="voyager-trash"></i> Delete
            </button>
        </div>
    @endif

    <div class="form-group">
        <input type="file" class="form-control" id="adv-image-input-{{ $row->field }}" name="{{ $row->field }}" accept="image/*">
    </div>
</div>
