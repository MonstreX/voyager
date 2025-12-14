@php
    $collectionName = $options->collection_name ?? 'default';
    $maxSize = $options->max_size ?? 10240;
    $mimeTypes = $options->mime_types ?? ['image/jpeg', 'image/png', 'image/webp'];
    $showProps = $options->show_props ?? true;
@endphp

<div class="form-group adv-image-wrapper">
    <div class="adv-image-preview">
        @php
            $media = null;
            if ($dataTypeContent && isset($dataTypeContent->{$row->field})) {
                $mediaId = $dataTypeContent->{$row->field};
                if (is_numeric($mediaId)) {
                    $media = \TCG\Voyager\Models\Media::find($mediaId);
                }
            }
        @endphp

        @if ($media)
            <div class="adv-image-current" id="adv-image-preview-{{ $row->field }}">
                <div class="adv-image-item">
                    <img src="{{ $media->url() }}" alt="{{ $media->prop('alt', $media->fileName()) }}" class="img-thumbnail">
                    <div class="adv-image-info">
                        <p class="filename">{{ $media->fileName() }}</p>
                        <p class="filesize">{{ $media->sizeForHumans() }}</p>
                        @if ($showProps)
                            <div class="adv-image-props">
                                <input type="text" class="form-control adv-image-title" placeholder="Title" value="{{ $media->prop('title', '') }}" data-media-id="{{ $media->id }}" data-prop="title">
                                <input type="text" class="form-control adv-image-alt" placeholder="Alt Text" value="{{ $media->prop('alt', '') }}" data-media-id="{{ $media->id }}" data-prop="alt">
                            </div>
                        @endif
                        <button type="button" class="btn btn-sm btn-danger adv-image-delete" data-media-id="{{ $media->id }}" data-field="{{ $row->field }}">
                            <i class="voyager-trash"></i> Delete
                        </button>
                    </div>
                </div>
            </div>
        @else
            <div class="adv-image-empty" id="adv-image-preview-{{ $row->field }}">
                <p class="text-muted">No image selected</p>
            </div>
        @endif
    </div>

    <div class="adv-image-upload">
        <div class="adv-image-dropzone" id="adv-image-dropzone-{{ $row->field }}" data-field="{{ $row->field }}" data-model-type="{{ \get_class($dataTypeContent) }}" data-model-id="{{ $dataTypeContent->id ?? 0 }}" data-collection="{{ $collectionName }}">
            <div class="dz-message">
                <span>Drop image here or click to upload</span>
            </div>
        </div>
    </div>

    <input type="hidden" id="adv-image-field-{{ $row->field }}" name="{{ $row->field }}" value="{{ $media->id ?? '' }}">
</div>
