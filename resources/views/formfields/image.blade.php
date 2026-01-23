@if(isset($dataTypeContent->{$row->field}))
    <div data-field-name="{{ $row->field }}" class="legacy-image-wrapper">
        <div class="adv-media-files-actions">
            <span class="adv-media-files-crop bread-image-crop icon voyager-crop" title="{{ __('voyager::media.crop') }}"></span>
            <span
                class="adv-media-files-remove remove-single-image icon voyager-x"
                title="{{ __('voyager::generic.delete') }}"
                data-confirm-target="#confirm_delete_modal"
                data-confirm-callback="Voyager.confirmCallbacks.mediaRemove"
                data-confirm-name="{{ $dataTypeContent->{$row->field} }}"></span>
        </div>
        <img src="@if( !filter_var($dataTypeContent->{$row->field}, FILTER_VALIDATE_URL)){{ Voyager::image( $dataTypeContent->{$row->field} ) }}@else{{ $dataTypeContent->{$row->field} }}@endif?v={{ time() }}"
          data-file-name="{{ $dataTypeContent->{$row->field} }}" data-id="{{ $dataTypeContent->getKey() }}">
    </div>
@endif
<input @if($row->required == 1 && !isset($dataTypeContent->{$row->field})) required @endif type="file" name="{{ $row->field }}" accept="image/*">

@php
    $cropModalId = 'bread-image-crop-modal-'.$row->field;
    $cropImageId = 'bread-image-crop-image-'.$row->field;
@endphp
@include('voyager::components.modal-crop', [
    'id' => $cropModalId,
    'imageId' => $cropImageId,
    'prefix' => 'bread-image',
    'modalClass' => 'modal-bread-image-crop',
])
