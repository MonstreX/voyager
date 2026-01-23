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
            <div class="img_settings_container" data-field-name="{{ $row->field }}" style="float:left;padding-right:15px;">
                <a
                    href="#"
                    class="voyager-x remove-multi-image"
                    data-confirm-target="#confirm_delete_modal"
                    data-confirm-callback="Voyager.confirmCallbacks.mediaRemove"
                    data-confirm-name="{{ $image }}"
                    style="position: absolute;"></a>
                <img src="{{ Voyager::image( $image ) }}" data-file-name="{{ $image }}" data-id="{{ $modelId }}" style="max-width:200px; height:auto; clear:both; display:block; padding:2px; border:1px solid #ddd; margin-bottom:5px;">
            </div>
        @endforeach
    @endif
</div>
<div class="clearfix"></div>
<input @if($row->required == 1 && !isset($dataTypeContent->{$row->field})) required @endif type="file" name="{{ $row->field }}[]" multiple="multiple" accept="image/*">
