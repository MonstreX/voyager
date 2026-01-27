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
        <div class="adv-media-files-item adv-image-item" id="adv-image-{{ $row->field }}">
            <div class="adv-media-files-item-holder"
                 data-type="adv_image"
                 data-field-name="{{ $row->field }}"
                 data-file-name="{{ $media->file_name }}"
                 data-file-id="{{ $media->id }}"
                 data-is-image="{{ $media->isImage() ? '1' : '0' }}"
                 data-delete-url-template="{{ $deleteUrlTemplate }}">

                <div class="adv-media-files-actions">
                    <span class="adv-media-files-edit adv-image-edit icon voyager-edit" title="{{ __('voyager::generic.edit') }}"></span>
                    @if($media->isImage())
                        <span class="adv-media-files-crop single-adv-image-crop icon voyager-crop" title="{{ __('voyager::media.crop') }}"></span>
                    @endif
                    <span class="adv-media-files-remove single-adv-image-remove icon voyager-x" title="{{ __('voyager::generic.delete') }}"></span>
                </div>

                <div class="adv-media-files-file">
                    <img src="{{ $media->url() }}?v={{ $media->updated_at?->getTimestamp() ?? $media->id }}"
                         alt="{{ $media->prop('alt', $media->fileName()) }}"
                         draggable="false">
                </div>
            </div>

            <div class="adv-media-files-data">
                <span class="adv-media-files-filename">{{ \Illuminate\Support\Str::limit($media->file_name, 20, ' (...)') }} <i class="{{ $media->size > 100000 ? 'large' : '' }}">{{ $media->sizeForHumans() }}</i></span>
                <span class="adv-media-files-title adv-media-prop-display">
                    @php $titleProp = $media->prop('title', ''); @endphp
                    @if(!empty($titleProp))
                        {{ \Illuminate\Support\Str::limit($titleProp, 30, ' (...)') }}
                    @else
                        <i>...</i>
                    @endif
                </span>
            </div>
        </div>
    @endif

    <div class="form-group">
        <input type="hidden" name="{{ $row->field }}_clear" value="0">
        <input type="hidden" name="{{ $row->field }}_title" value="{{ $media ? $media->prop('title', '') : '' }}">
        <input type="hidden" name="{{ $row->field }}_alt" value="{{ $media ? $media->prop('alt', '') : '' }}">
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

@php
    $cropModalId = 'adv-image-crop-modal-'.$row->field;
    $cropImageId = 'adv-image-crop-image-'.$row->field;
@endphp
@include('voyager::components.modal-crop', [
    'id' => $cropModalId,
    'imageId' => $cropImageId,
    'prefix' => 'adv-image',
    'modalClass' => 'modal-voyager-crop',
])

@php
    $propsModalId = 'adv-image-props-modal-'.$row->field;
@endphp
<div class="modal fade modal-adv-media-props modal-adv-image-props" tabindex="-1" role="dialog" id="{{ $propsModalId }}">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">{{ __('voyager::generic.edit') }}</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>{{ __('voyager::generic.title') }}</label>
                    <input type="text" class="form-control modal-prop-title" value="">
                </div>
                <div class="form-group">
                    <label>{{ __('voyager::generic.alt_text') }}</label>
                    <input type="text" class="form-control modal-prop-alt" value="">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('voyager::generic.cancel') }}</button>
                <button type="button" class="btn btn-primary modal-prop-save">{{ __('voyager::generic.save') }}</button>
            </div>
        </div>
    </div>
</div>
