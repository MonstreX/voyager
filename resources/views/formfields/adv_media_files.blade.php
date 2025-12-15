@php
    $collectionName = $options->collection_name ?? $row->field;
    $mediaItems = ($dataTypeContent && method_exists($dataTypeContent, 'getMedia'))
        ? $dataTypeContent->getMedia($collectionName)
        : collect();
    $extraFields = $row->details->extra_fields ?? [];
    $deleteUrlTemplate = route('voyager.media-api.delete', ['media' => '__MEDIA_ID__']);
    $reorderUrl = route('voyager.media-api.reorder');
@endphp

<div class="adv-media-files-holder">
    @if($mediaItems->count() > 0)
        <div id="{{ $row->field }}"
             class="adv-media-files-list sortable-files-field-{{ $row->field }}"
             data-bunch-adv-remove-holder="bunch-adv-remove-{{ $row->field }}"
             data-extra-fields="{{ !empty($extraFields) ? 'true' : 'false' }}"
             data-type="adv_media_files"
             data-model="{{ $dataType->model_name }}"
             data-model-type="{{ $dataType->model_name }}"
             data-slug="{{ $dataType->slug }}"
             data-field-name="{{ $row->field }}"
             data-id="{{ $dataTypeContent->id ?? '' }}"
             data-model-id="{{ $dataTypeContent->id ?? '' }}"
             data-delete-url-template="{{ $deleteUrlTemplate }}"
             data-reorder-url="{{ $reorderUrl }}"
             data-collection-name="{{ $collectionName }}"
             data-input-id="adv-media-files-input-{{ $row->field }}">
            @foreach($mediaItems as $index => $media)
                <div class="adv-media-files-item">
                    <div class="adv-media-files-item-holder"
                         data-type="adv_media_files"
                         data-model="{{ $dataType->model_name }}"
                         data-slug="{{ $dataType->slug }}"
                         data-field-name="{{ $row->field }}"
                         data-file-name="{{ $media->file_name }}"
                         data-id="{{ $dataTypeContent->id ?? '' }}"
                         data-file-id="{{ $media->id }}"
                         data-is-image="{{ $media->isImage() ? '1' : '0' }}">

                        <div class="adv-media-files-order">
                            {{ $index + 1 }}
                        </div>

                        <div class="adv-media-files-actions">
                            <span class="adv-media-files-change icon voyager-refresh" title="Change file"></span>
                            <span class="adv-media-files-edit icon voyager-edit" title="Edit meta"></span>
                            @if($media->isImage())
                                <span class="adv-media-files-crop icon voyager-crop" title="{{ __('voyager::media.crop') }}"></span>
                            @endif
                            <span class="adv-media-files-remove icon voyager-x" title="Delete file"></span>
                        </div>

                        <div class="adv-media-files-file">
                            @if($media->isImage())
                                <img src="{{ $media->fullUrl() }}">
                            @else
                                <img class="file-type" src="{{ asset('vendor/tcg/voyager/assets/icons/files/default.png') }}">
                            @endif
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
            @endforeach
        </div>

        <div class="bunch-adv-select-all" data-files-gallery-list="{{ $row->field }}">
            <span id="bunch-adv-remove-{{ $row->field }}"
                  class="bunch-adv-remove-holder hidden"
                  data-files-gallery-list="{{ $row->field }}">
                <button type="button"
                        title="{{ __('voyager::generic.delete') }}"
                        class="btn btn-sm btn-danger bunch-adv-media-files-remove">
                    <i class="voyager-trash"></i> <span>{{ __('voyager::generic.delete') }}</span>
                </button>
            </span>

            <button type="button"
                    title="{{ __('voyager::generic.select_all') }}"
                    class="bunch-adv-media-files-select-all btn btn-sm btn-success"
                    data-files-gallery-list="{{ $row->field }}">
                <span>{{ __('voyager::generic.select_all') }}</span>
            </button>

            <button type="button"
                    title="{{ __('voyager::generic.deselect_all') }}"
                    class="bunch-adv-media-files-unmark btn btn-sm btn-info hidden"
                    data-files-gallery-list="{{ $row->field }}">
                <span>{{ __('voyager::generic.deselect_all') }}</span>
            </button>
        </div>
    @endif

    <div class="adv-media-files-file-upload">
        <input @if($row->required == 1) required @endif
               id="adv-media-files-input-{{ $row->field }}"
               type="file"
               name="{{ $row->field }}[]"
               multiple="multiple"
               accept="{{ $row->details->input_accept ?? 'image/*' }}">
    </div>
</div>

@include('voyager::components.modal-confirm', [
    'id' => 'adv-media-delete-modal-'.$row->field,
    'title' => __('voyager::generic.are_you_sure_delete'),
    'message' => __('voyager::generic.delete_confirm'),
    'confirmText' => __('voyager::generic.delete_confirm'),
    'confirmClass' => 'btn-danger',
    'confirmButtonClass' => 'adv-media-delete-confirm',
    'icon' => 'voyager-trash'
])

@php
    $extraModalId = 'adv-media-props-modal-'.$row->field;
@endphp
<div class="modal fade modal-adv-media-props" tabindex="-1" role="dialog" id="{{ $extraModalId }}">
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
                @if(!empty($extraFields))
                    @foreach($extraFields as $key => $field)
                        <div class="form-group @if(isset($field->class)) {{ $field->class }} @endif">
                            <label>{{ $field->title ?? $key }}</label>
                            @php $fieldType = $field->type ?? 'text'; @endphp
                            @if($fieldType === 'ace')
                                @php
                                    $aceId = 'adv_media_files_ace_'.$row->field.'_'.$key;
                                @endphp
                                <div
                                    id="{{ $aceId }}"
                                    class="ace_editor modal-prop-ace"
                                    data-extra-key="{{ $key }}"
                                    data-language="html"
                                    data-theme="monokai"
                                    data-min-lines="8"
                                ></div>
                                <textarea
                                    id="{{ $aceId }}_textarea"
                                    class="modal-prop-ace-textarea"
                                    data-extra-key="{{ $key }}"
                                    style="display:none;"
                                ></textarea>
                            @elseif($fieldType === 'textarea')
                                <textarea class="form-control modal-prop-extra"
                                          data-extra-key="{{ $key }}"
                                          rows="4"></textarea>
                            @else
                                <input type="text"
                                       class="form-control modal-prop-extra"
                                       data-extra-key="{{ $key }}">
                            @endif
                        </div>
                    @endforeach
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('voyager::generic.cancel') }}</button>
                <button type="button" class="btn btn-primary modal-prop-save">{{ __('voyager::generic.save') }}</button>
            </div>
        </div>
    </div>
</div>

@php
    $cropModalId = 'adv-media-crop-modal-'.$row->field;
    $cropImageId = 'adv-media-crop-image-'.$row->field;
@endphp
<div class="modal fade modal-warning modal-adv-media-crop" tabindex="-1" role="dialog" id="{{ $cropModalId }}">
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
                    <span>{{ __('voyager::media.width') }} <strong class="adv-media-crop-width">0px</strong></span>
                    <span style="margin-left: 15px;">{{ __('voyager::media.height') }} <strong class="adv-media-crop-height">0px</strong></span>
                </div>
                <div class="row" style="margin-top: 15px;">
                    <div class="col-sm-4">
                        <label>{{ __('voyager::media.aspect_ratio') }}</label>
                        <select class="form-control adv-media-crop-aspect">
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
                        <input type="number" class="form-control adv-media-crop-max-width" min="1" placeholder="1000">
                    </div>
                    <div class="col-sm-4">
                        <label>{{ __('voyager::media.max_height') }}</label>
                        <input type="number" class="form-control adv-media-crop-max-height" min="1" placeholder="1000">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('voyager::generic.cancel') }}</button>
                <button type="button" class="btn btn-warning adv-media-crop-confirm">{{ __('voyager::media.crop') }}</button>
            </div>
        </div>
    </div>
</div>
