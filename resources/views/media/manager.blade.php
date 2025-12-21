@section('media-manager')
<div>
    <div v-if="hidden_element" :id="'dd_'+uid" class="dd">
        <ol id="files" class="dd-list">
            <li v-for="file in getSelectedFiles()" class="dd-item" :data-url="getFilePath(file)">
                <div class="file_link selected" aria-hidden="true" data-toggle="tooltip" data-placement="auto" :title="getFilePath(file)">
                    <div class="link_icon">
                        <template v-if="fileIs(file, 'image')">
                            <div class="img_icon" :style="imgIcon(file)"></div>
                        </template>
                        <template v-else-if="fileIs(file, 'video')">
                            <i class="icon voyager-video"></i>
                        </template>
                        <template v-else-if="fileIs(file, 'audio')">
                            <i class="icon voyager-music"></i>
                        </template>
                        <template v-else-if="fileIs(file, 'zip')">
                            <i class="icon voyager-archive"></i>
                        </template>
                        <template v-else-if="fileIs(file, 'folder')">
                            <i class="icon voyager-folder"></i>
                        </template>
                        <template v-else>
                            <i class="icon voyager-file-text"></i>
                        </template>
                    </div>
                    <div class="details">
                        <div class="folder" :class="getFileTypeClass(file)">
                            <h4>@{{ getFileName(file) }}</h4>
                        </div>
                    </div>
                    <i class="voyager-x dd-nodrag" v-on:click="removeSelectedFile(file)"></i>
                </div>
            </li>
        </ol>
    </div>
    <div v-if="hidden_element">
        <div class="btn btn-sm btn-default" v-on:click="isExpanded = !isExpanded;" style="width:100%">
            <div v-if="!isExpanded"><i class="voyager-double-down"></i> {{ __('voyager::generic.open') }}</div>
            <div v-if="isExpanded"><i class="voyager-double-up"></i> {{ __('voyager::generic.close') }}</div>
        </div>
    </div>
    <div id="toolbar" v-if="showToolbar" :style="isExpanded ? 'display:block' : 'display:none'">
        <div class="btn-group offset-right">
            <button type="button" class="btn btn-primary" id="upload" v-if="allowUpload" v-on:click="triggerUpload">
                <i class="voyager-upload"></i>
                {{ __('voyager::generic.upload') }}
            </button>
            <button type="button" class="btn btn-primary" v-if="allowCreateFolder" data-toggle="modal" :data-target="'#create_dir_modal_'+uid">
                <i class="voyager-folder"></i>
                {{ __('voyager::generic.add_folder') }}
            </button>
        </div>
        <button type="button" class="btn btn-default" v-on:click="getFiles()">
            <i class="voyager-refresh"></i>
        </button>
        <div class="btn-group offset-right">
            <button type="button" :disabled="selected_files.length == 0" v-if="allowUpload && hidden_element" class="btn btn-default" v-on:click="addSelectedFiles()">
                <i class="voyager-upload"></i>
                {{ __('voyager::media.add_all_selected') }}
            </button>
            <button type="button" v-if="showFolders && allowMove" class="btn btn-default" data-toggle="modal" :data-target="'#move_files_modal_'+uid">
                <i class="voyager-move"></i>
                {{ __('voyager::generic.move') }}
            </button>
            <button type="button" v-if="allowDelete" :disabled="selected_files.length == 0" class="btn btn-default" data-toggle="modal" :data-target="'#confirm_delete_modal_'+uid">
                <i class="voyager-trash"></i>
                {{ __('voyager::generic.delete') }}
            </button>
            <button v-if="allowCrop" :disabled="selected_files.length != 1 || !fileIs(selected_file, 'image')" type="button" class="btn btn-default" data-toggle="modal" :data-target="'#crop_modal_'+uid">
                <i class="voyager-crop"></i>
                {{ __('voyager::media.crop') }}
            </button>
        </div>
    </div>
    <div :id="'uploadProgress_'+uid" class="progress active progress-striped" v-if="allowUpload" style="display:none;">
        <div class="progress-bar progress-bar-success" style="width: 0"></div>
    </div>
    <input type="file" :id="'file_upload_input_'+uid" name="files[]" multiple style="display:none;" v-on:change="handleFileUpload">
    <div id="content" :style="isExpanded ? 'display:block' : 'display:none'">
        <div class="breadcrumb-container">
            <ol class="breadcrumb filemanager">
                <li class="media_breadcrumb" v-on:click="setCurrentPath(-1)">
                    <span class="arrow"></span>
                    <strong>{{ __('voyager::media.library') }}</strong>
                </li>
                <li v-for="(folder, i) in getCurrentPath()" v-on:click="setCurrentPath(i)">
                    <span class="arrow"></span>
                    @{{ folder }}
                </li>
            </ol>
        </div>
        <div class="flex">
            <div id="left">
                <ul id="files">
                <li v-for="(file) in files" v-on:click="selectFile(file, $event)" v-on:dblclick="openFile(file)">
                        <div :class="'file_link ' + (isFileSelected(file) ? 'selected' : '')">
                            <div class="link_icon">
                                <template v-if="fileIs(file, 'image')">
                                    <div class="img_icon" :style="imgIcon(file)"></div>
                                </template>
                                <template v-else-if="fileIs(file, 'video')">
                                    <i class="icon voyager-video"></i>
                                </template>
                                <template v-else-if="fileIs(file, 'audio')">
                                    <i class="icon voyager-music"></i>
                                </template>
                                <template v-else-if="fileIs(file, 'zip')">
                                    <i class="icon voyager-archive"></i>
                                </template>
                                <template v-else-if="fileIs(file, 'folder')">
                                    <i class="icon voyager-folder"></i>
                                </template>
                                <template v-else>
                                    <i class="icon voyager-file-text"></i>
                                </template>
                            </div>
                            <div class="details">
                                <div :class="file ? file.type : ''">
                                    <h4>@{{ file.name }}</h4>
                        <small v-if="!fileIs(file, 'folder') && file && file.size">
                            <span class="file_size">@{{ bytesToSize(file.size) }}</span>
                        </small>
                                </div>
                            </div>
                        </div>
                    </li>
                </ul>
                <div id="file_loader" v-if="is_loading">
                    <?php $admin_loader_img = Voyager::setting('admin.loader', ''); ?>
                    @if($admin_loader_img == '')
                    <img src="{{ voyager_asset('images/logo-icon.png') }}" alt="Voyager Loader">
                    @else
                    <img src="{{ Voyager::image($admin_loader_img) }}" alt="Voyager Loader">
                    @endif
                    <p>{{ __('voyager::media.loading') }}</p>
                </div>

                <div id="no_files" v-if="files.length == 0">
                    <h3><i class="voyager-meh"></i> {{ __('voyager::media.no_files_in_folder') }}</h3>
                </div>
            </div>
            <div id="right">
                <div class="right_details">
                    <div v-if="selected_files.length > 1" class="right_none_selected">
                        <i class="voyager-list"></i>
                        <p>@{{ selected_files.length }} {{ __('voyager::media.files_selected') }}</p>
                    </div>
                    <div v-else-if="selected_files.length == 1 && selected_file" class="right_details">
                        <div class="detail_img">
                            <div v-if="fileIs(selected_file, 'image')">
                                <img :src="resolvePreviewPath(selected_file.path, buildCacheBust(selected_file.last_modified || selected_file.lastModified))" />
                            </div>
                            <div v-else-if="fileIs(selected_file, 'video')">
                                <video width="100%" height="auto" ref="videoplayer" controls>
                                    <source :src="selected_file.path" type="video/mp4">
                                    <source :src="selected_file.path" type="video/ogg">
                                    <source :src="selected_file.path" type="video/webm">
                                    {{ __('voyager::media.browser_video_support') }}
                                </video>
                            </div>
                            <div v-else-if="fileIs(selected_file, 'audio')">
                                <i class="voyager-music"></i>
                                <audio controls style="width:100%; margin-top:5px;" ref="audioplayer">
                                    <source :src="selected_file.path" type="audio/ogg">
                                    <source :src="selected_file.path" type="audio/mpeg">
                                    {{ __('voyager::media.browser_audio_support') }}
                                </audio>
                            </div>
                            <div v-else-if="fileIs(selected_file, 'zip')">
                                <i class="voyager-archive"></i>
                            </div>
                            <div v-else-if="fileIs(selected_file, 'folder')">
                                <i class="voyager-folder"></i>
                            </div>
                            <div v-else>
                                <i class="voyager-file-text"></i>
                            </div>
                        </div>
                        <div class="detail_info">
                            <span>
                                <h4>{{ __('voyager::media.title') }}:</h4>
                                <input v-if="allowRename" type="text" class="form-control" :value="selected_file.name" @keydown.enter.prevent="renameFile">
                                <p v-else>@{{ selected_file.name }}</p>
                            </span>
                            <span>
                                <h4>{{ __('voyager::media.type') }}:</h4>
                                <p>@{{ selected_file.type }}</p>
                            </span>

                            <template v-if="!fileIs(selected_file, 'folder')">
                                <span>
                                    <h4>{{ __('voyager::media.size') }}:</h4>
                                    <p><span class="selected_file_size">@{{ bytesToSize(selected_file.size) }}</span></p>
                                </span>
                                <span>
                                    <h4>{{ __('voyager::media.public_url') }}:</h4>
                                    <p><a :href="selected_file.path" target="_blank">{{ __('voyager::generic.click_here') }}</a></p>
                                </span>
                                <span>
                                    <h4>{{ __('voyager::media.last_modified') }}:</h4>
                                    <p>@{{ dateFilter(selected_file.last_modified) }}</p>
                                </span>
                            </template>

                            <span v-if="fileIs(selected_file, 'image') && selected_file.thumbnails.length > 0">
                                <h4>Thumbnails</h4><br>
                                <ul>
                                    <li v-for="thumbnail in selected_file.thumbnails">
                                        <a :href="thumbnail.path" target="_blank">
                                            @{{ thumbnail.thumb_name }}
                                        </a>
                                    </li>
                                </ul>
                            </span>
                        </div>
                    </div>
                    <div v-else class="right_none_selected">
                        <i class="voyager-cursor"></i>
                        <p>{{ __('voyager::media.nothing_selected') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div class="modal fade" :id="'imagemodal_'+uid" v-if="selected_file && fileIs(selected_file, 'image')">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                </div>
                <div class="modal-body">
                    <img :src="resolvePreviewPath(selected_file.path, buildCacheBust(selected_file.last_modified || selected_file.lastModified))" class="img img-responsive" style="margin: 0 auto;">
                </div>

                <div class="modal-footer text-left">
                    <small class="image-title">@{{ selected_file.name }}</small>
                </div>

            </div>
        </div>
    </div>
    <!-- End Image Modal -->

    <!-- New Folder Modal -->
    <div class="modal fade modal-info" :id="'create_dir_modal_'+uid">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title"><i class="voyager-folder"></i> {{ __('voyager::media.add_new_folder') }}</h4>
                </div>

                <div class="modal-body">
                    <input name="new_folder_name" placeholder="{{ __('voyager::media.new_folder_name') }}" class="form-control" value="" v-model="modals.new_folder.name" />
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('voyager::generic.cancel') }}</button>
                    <button type="button" class="btn btn-info" v-on:click="createFolder">{{ __('voyager::media.create_new_folder') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- End New Folder Modal -->

    <!-- Delete File Modal -->
    <div v-if="allowDelete">
        @include('voyager::components.modal-confirm', [
            'bindId' => "'confirm_delete_modal_'+uid",
            'title' => __('voyager::generic.are_you_sure'),
            'message' => '<h4>'.__('voyager::media.delete_question').'</h4><ul><li v-for="file in selected_files">@{{ file.name }}</li></ul><h5 class="folder_warning"><i class="voyager-warning"></i> '.__('voyager::media.delete_folder_question').'</h5>',
            'confirmText' => __('voyager::generic.delete_confirm'),
            'confirmClass' => 'btn-danger',
            'bindConfirmButtonId' => "'confirm_delete_files_'+uid",
            'icon' => 'voyager-warning'
        ])
    </div>
    <!-- End Delete File Modal -->

    <!-- Move Files Modal -->
    <div class="modal fade modal-warning" :id="'move_files_modal_'+uid" v-if="allowMove">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"
                            aria-hidden="true">&times;</button>
                    <h4 class="modal-title"><i class="voyager-move"></i> {{ __('voyager::media.move_file_folder') }}</h4>
                </div>

                <div class="modal-body">
                    <h4>{{ __('voyager::media.destination_folder') }}</h4>
                    <select class="form-control voyager-select" v-model="modals.move_files.destination">
                        <option value="" disabled>{{ __('voyager::media.destination_folder') }}</option>
                        <option v-if="current_folder != basePath && showFolders" value="/../">../</option>
                        <option v-for="file in files" v-if="file && file.type == 'folder' && !selected_files.includes(file)" :value="current_folder+'/'+file.name">@{{ file.name }}</option>
                    </select>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('voyager::generic.cancel') }}</button>
                    <button type="button" class="btn btn-warning" v-on:click="moveFiles">{{ __('voyager::generic.move') }}</button>
                </div>
            </div>
        </div>
    </div>
    <!-- End Move File Modal -->

    <!-- Crop Image Modal -->
    <div class="modal fade modal-warning" :id="'crop_modal_'+uid" v-if="allowCrop">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title">{{ __('voyager::media.crop_image') }}</h4>
                </div>

                <div class="modal-body">
                    <div class="crop-container">
                        <img :id="'cropping-image_'+uid" v-if="selected_files.length == 1 && fileIs(selected_file, 'image')" class="img img-responsive" :src="resolvePreviewPath(selected_file.path, buildCacheBust(selected_file.last_modified || selected_file.lastModified))" />
                    </div>
                    <div class="new-image-info">
                        {{ __('voyager::media.width') }} <span :id="'new-image-width_'+uid"></span>, {{ __('voyager::media.height') }}<span :id="'new-image-height_'+uid"></span>
                    </div>
                    <div class="row" style="margin-top: 15px;">
                        <div class="col-sm-4">
                            <label>{{ __('voyager::media.aspect_ratio') }}</label>
                            <select class="form-control" v-model="modals.crop.aspect" v-on:change="setCropAspectRatio">
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
                            <input type="number" class="form-control" v-model="modals.crop.max_width" min="1" placeholder="1000">
                        </div>
                        <div class="col-sm-4">
                            <label>{{ __('voyager::media.max_height') }}</label>
                            <input type="number" class="form-control" v-model="modals.crop.max_height" min="1" placeholder="1000">
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('voyager::generic.cancel') }}</button>
                    <button type="button" class="btn btn-warning" v-on:click="crop(false)">{{ __('voyager::media.crop') }}</button>
                    <button type="button" class="btn btn-warning" v-on:click="crop(true)">{{ __('voyager::media.crop_and_create') }}</button>
                </div>
            </div>
        </div>
    </div>
    <!-- End Crop Image Modal -->
</div>
@endsection

@php
    $voyagerMediaStorageBaseUrl = rtrim(Storage::disk(config('voyager.storage.disk'))->url('/'), '/').'/';
    $voyagerMediaManagerConfig = [
        'routes' => [
            'files' => route('voyager.media.files'),
            'rename' => route('voyager.media.rename'),
            'newFolder' => route('voyager.media.new_folder'),
            'delete' => route('voyager.media.delete'),
            'move' => route('voyager.media.move'),
            'crop' => route('voyager.media.crop'),
            'upload' => route('voyager.media.upload'),
        ],
        'storageBaseUrl' => $voyagerMediaStorageBaseUrl,
        'i18n' => [
            'whoopsie' => __('voyager::generic.whoopsie'),
            'sweetSuccess' => __('voyager::generic.sweet_success'),
            'successfullyCreated' => __('voyager::generic.successfully_created'),
            'errorUploading' => __('voyager::media.error_uploading'),
            'successRenamed' => __('voyager::media.success_renamed'),
            'errorRenamingExt' => __('voyager::media.error_renaming_ext'),
            'errorCreatingDir' => __('voyager::media.error_creating_dir'),
            'errorDeletingFile' => __('voyager::media.error_deleting_file'),
            'successMoved' => __('voyager::media.success_moved'),
            'errorMoving' => __('voyager::media.error_moving'),
            'cropOverrideConfirm' => __('voyager::media.crop_override_confirm'),
        ],
        'messages' => [
            'maxFilesSelect' => [
                'singular' => trans_choice('voyager::media.max_files_select', 1),
                'plural' => trans_choice('voyager::media.max_files_select', 2),
            ],
            'minFilesSelect' => [
                'singular' => trans_choice('voyager::media.min_files_select', 1),
                'plural' => trans_choice('voyager::media.min_files_select', 2),
            ],
        ],
    ];
@endphp

<template id="voyager-media-manager-template">
    @yield('media-manager')
</template>
<script type="application/json" id="voyager-media-manager-config">@json($voyagerMediaManagerConfig)</script>

@include('voyager::components.modal-confirm', [
    'id' => 'media_crop_override_modal',
    'title' => __('voyager::generic.are_you_sure'),
    'message' => __('voyager::media.crop_override_confirm'),
    'confirmText' => __('voyager::generic.yes_please'),
    'confirmClass' => 'btn-warning',
    'icon' => 'voyager-warning',
    'modalClass' => 'modal-warning'
])
<style>
.dd-placeholder {
    flex: 1;
    width: 100%;
    min-width: 200px;
    max-width: 250px;
}
.drag-over {
    outline: 2px dashed #22a7f0;
    outline-offset: -10px;
    background-color: rgba(34, 167, 240, 0.1);
}
</style>
