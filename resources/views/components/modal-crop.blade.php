@php
    $modalId = $id ?? 'voyager-crop-'.uniqid();
    $imageId = $imageId ?? 'voyager-crop-image-'.uniqid();
    $prefix = $prefix ?? 'voyager';
    $modalClass = trim('modal-warning '.($modalClass ?? ''));

    $widthClass = $prefix.'-crop-width';
    $heightClass = $prefix.'-crop-height';
    $aspectClass = $prefix.'-crop-aspect';
    $maxWidthClass = $prefix.'-crop-max-width';
    $maxHeightClass = $prefix.'-crop-max-height';
    $confirmClass = $prefix.'-crop-confirm';
@endphp

<div class="modal fade {{ $modalClass }}" tabindex="-1" role="dialog" id="{{ $modalId }}">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">{{ __('voyager::media.crop_image') }}</h4>
            </div>
            <div class="modal-body">
                <div class="crop-container">
                    <img id="{{ $imageId }}" class="img img-responsive" src="" alt="">
                </div>
                <div class="clearfix"></div>
                <div style="margin-top:10px;">
                    <span>{{ __('voyager::media.width') }} <strong class="{{ $widthClass }}">0px</strong></span>
                    <span style="margin-left: 15px;">{{ __('voyager::media.height') }} <strong class="{{ $heightClass }}">0px</strong></span>
                </div>
                <div class="row" style="margin-top: 15px;">
                    <div class="col-sm-4">
                        <label>{{ __('voyager::media.aspect_ratio') }}</label>
                        <select class="form-control {{ $aspectClass }}">
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
                        <input type="number" class="form-control {{ $maxWidthClass }}" min="1" placeholder="1000">
                    </div>
                    <div class="col-sm-4">
                        <label>{{ __('voyager::media.max_height') }}</label>
                        <input type="number" class="form-control {{ $maxHeightClass }}" min="1" placeholder="1000">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('voyager::generic.cancel') }}</button>
                <button type="button" class="btn btn-warning {{ $confirmClass }}">{{ __('voyager::media.crop') }}</button>
            </div>
        </div>
    </div>
</div>

