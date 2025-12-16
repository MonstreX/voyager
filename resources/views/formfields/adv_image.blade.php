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
            <div class="adv-image-actions">
                <button type="button" class="btn btn-sm btn-warning single-adv-image-crop" data-media-id="{{ $media->id }}" data-field="{{ $row->field }}">
                    <i class="voyager-crop"></i> {{ __('voyager::media.crop') }}
                </button>
                <button type="button" class="btn btn-sm btn-danger single-adv-image-remove" data-media-id="{{ $media->id }}" data-field="{{ $row->field }}" data-delete-url-template="{{ $deleteUrlTemplate }}">
                    <i class="voyager-trash"></i> Delete
                </button>
            </div>
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

@php
    $cropModalId = 'adv-image-crop-modal-'.$row->field;
    $cropImageId = 'adv-image-crop-image-'.$row->field;
@endphp
<div class="modal fade modal-warning modal-voyager-crop" tabindex="-1" role="dialog" id="{{ $cropModalId }}">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">{{ __('voyager::media.crop_image') }}</h4>
            </div>
            <div class="modal-body">
                <div class="crop-container">
                    <img id="{{ $cropImageId }}" class="img img-responsive" src="" alt="">
                </div>
                <div class="clearfix"></div>
                <div style="margin-top:10px;">
                    <span>{{ __('voyager::media.width') }} <strong class="adv-image-crop-width">0px</strong></span>
                    <span style="margin-left: 15px;">{{ __('voyager::media.height') }} <strong class="adv-image-crop-height">0px</strong></span>
                </div>
                <div class="row" style="margin-top: 15px;">
                    <div class="col-sm-4">
                        <label>{{ __('voyager::media.aspect_ratio') }}</label>
                        <select class="form-control adv-image-crop-aspect">
                            <option value="free">{{ __('voyager::media.aspect_free') }}</option>
                            <option value="1">1:1</option>
                            <option value="1.3333333333">4:3</option>
                            <option value="1.5">3:2</option>
                            <option value="1.7777777778">16:9</option>
                            <option value="0.75">3:4</option>
                            <option value="0.6666666667">2:3</option>
                            <option value="0.5625">9:16</option>
                        </select>
                    </div>
                    <div class="col-sm-4">
                        <label>{{ __('voyager::media.max_width') }}</label>
                        <input type="number" class="form-control adv-image-crop-max-width" min="1" placeholder="1000">
                    </div>
                    <div class="col-sm-4">
                        <label>{{ __('voyager::media.max_height') }}</label>
                        <input type="number" class="form-control adv-image-crop-max-height" min="1" placeholder="1000">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('voyager::generic.cancel') }}</button>
                <button type="button" class="btn btn-warning adv-image-crop-confirm">{{ __('voyager::media.crop') }}</button>
            </div>
        </div>
    </div>
</div>
