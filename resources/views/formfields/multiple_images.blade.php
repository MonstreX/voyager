@php
    $images = isset($dataTypeContent->{$row->field}) ? json_decode($dataTypeContent->{$row->field}) : null;
    $modelId = isset($dataTypeContent) ? $dataTypeContent->getKey() : null;
@endphp
<br>
<div class="multi-images-list"
     data-field-name="{{ $row->field }}"
     data-model-id="{{ $modelId }}">
    @if($images != null)
        @foreach($images as $image)
            <div class="img_settings_container" data-field-name="{{ $row->field }}">
                <div class="adv-media-files-actions">
                    <span class="adv-media-files-crop bread-multi-image-crop icon voyager-crop" title="{{ __('voyager::media.crop') }}"></span>
                    <span
                        class="adv-media-files-remove remove-multi-image icon voyager-x"
                        title="{{ __('voyager::generic.delete') }}"
                        data-confirm-target="#confirm_delete_modal"
                        data-confirm-callback="Voyager.confirmCallbacks.mediaRemove"
                        data-confirm-name="{{ $image }}"></span>
                </div>
                <img src="{{ Voyager::image( $image ) }}?v={{ time() }}" data-file-name="{{ $image }}" data-id="{{ $modelId }}">
            </div>
        @endforeach
    @endif
</div>
<div class="clearfix"></div>
<input @if($row->required == 1 && !isset($dataTypeContent->{$row->field})) required @endif type="file" name="{{ $row->field }}[]" multiple="multiple" accept="image/*">

@php
    $cropModalId = 'bread-multi-image-crop-modal-'.$row->field;
    $cropImageId = 'bread-multi-image-crop-image-'.$row->field;
@endphp
@include('voyager::components.modal-crop', [
    'id' => $cropModalId,
    'imageId' => $cropImageId,
    'prefix' => 'bread-multi-image',
    'modalClass' => 'modal-bread-multi-image-crop',
])
