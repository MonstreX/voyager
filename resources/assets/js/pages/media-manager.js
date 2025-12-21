import { getCsrfToken } from '../modules/csrf';
import { showModal, hideModal } from '../core/bootstrap-compat';
import { postJson } from '../core/http';
import { confirmAction } from '../modules/confirm-modal';
import { getToastr } from '../core/toastr';

let componentRegistered = false;

const parseJsonConfig = (elementId) => {
    if (typeof document === 'undefined') return null;
    const el = document.getElementById(elementId);
    if (!el) return null;
    try {
        return JSON.parse(el.textContent || '{}');
    } catch (error) {
        console.error(`[Voyager] Failed to parse ${elementId}`, error);
        return null;
    }
};

const getTemplateHtml = (elementId) => {
    if (typeof document === 'undefined') return '';
    const el = document.getElementById(elementId);
    return el ? (el.innerHTML || '').trim() : '';
};

const normalizeBaseUrl = (url) => {
    if (!url) return '/';
    return url.endsWith('/') ? url : url + '/';
};

const voyagerMediaRequest = (url, payload = {}) => {
    if (window.Voyager && window.Voyager.http && typeof window.Voyager.http.postJson === 'function') {
        return window.Voyager.http.postJson(url, payload);
    }
    return postJson(url, payload);
};

const sendRequest = (url, payload = {}) => {
    return voyagerMediaRequest(url, payload).then((response) => {
        if (!response.ok) {
            throw new Error('Voyager media request failed with status ' + response.status);
        }
        return response.json();
    });
};

const onReady = (callback) => {
    if (typeof document === 'undefined') return;
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', callback, { once: true });
        return;
    }
    callback();
};

const findMountRoots = () => {
    if (typeof document === 'undefined') return [];

    const roots = Array.from(document.querySelectorAll('[data-voyager-media-manager-root]'));
    if (roots.length) return roots;

    const fallback = [];
    const filemanager = document.getElementById('filemanager');
    if (filemanager && filemanager.querySelector('media-manager')) {
        fallback.push(filemanager);
    }
    document.querySelectorAll('[id^="media_picker_"]').forEach((el) => {
        if (el.querySelector('media-manager')) {
            fallback.push(el);
        }
    });
    return fallback;
};

const mountRoots = (Vue) => {
    const roots = findMountRoots();
    roots.forEach((root) => {
        if (root.dataset.voyagerVueMounted === '1') return;
        root.dataset.voyagerVueMounted = '1';
        Vue.createApp({}).mount(root);
    });
};

const registerComponent = (Vue, templateHtml, config) => {
    if (componentRegistered) return;
    if (!templateHtml) {
        console.warn('[Voyager] Media manager template is missing.');
        return;
    }

    const routes = (config && config.routes) || {};
    const i18n = (config && config.i18n) || {};
    const messages = (config && config.messages) || {};
    const storageBaseUrl = normalizeBaseUrl(config && config.storageBaseUrl);

    Vue.registerComponent('media-manager', {
        template: templateHtml,
        props: {
            basePath: { type: String, default: '/' },
            filename: { type: String, default: null },
            allowMultiSelect: { type: Boolean, default: true },
            maxSelectedFiles: { type: Number, default: 0 },
            minSelectedFiles: { type: Number, default: 0 },
            showFolders: { type: Boolean, default: true },
            showToolbar: { type: Boolean, default: true },
            allowUpload: { type: Boolean, default: true },
            allowMove: { type: Boolean, default: true },
            allowDelete: { type: Boolean, default: true },
            allowCreateFolder: { type: Boolean, default: true },
            allowRename: { type: Boolean, default: true },
            allowCrop: { type: Boolean, default: true },
            allowedTypes: { type: Array, default: () => [] },
            preSelect: { type: Boolean, default: true },
            element: { type: String, default: '' },
            details: { type: Object, default: () => ({}) },
            expanded: { type: Boolean, default: true }
        },
        data: function () {
            return {
                uid: Math.random().toString(36).slice(2),
                previewBust: 0,
                current_folder: this.basePath,
                selected_files: [],
                files: [],
                is_loading: true,
                hidden_element: null,
                isExpanded: this.expanded,
                croppedData: {},
                _cropperInstance: null,
                modals: {
                    new_folder: { name: '' },
                    move_files: { destination: '' },
                    crop: {
                        aspect: 'free',
                        max_width: '',
                        max_height: ''
                    }
                }
            };
        },
        computed: {
            selected_file: function () {
                if (this.selected_files.length === 0) {
                    return {
                        name: '',
                        path: '',
                        type: '',
                        size: 0,
                        thumbnails: [],
                        last_modified: null
                    };
                }

                return this.selected_files[0];
            }
        },
        methods: {
            toggleModalVisibility: function (modalId, shouldShow) {
                if (typeof document === 'undefined') return;
                const modal = document.getElementById(modalId);
                if (!modal) return;
                if (shouldShow) {
                    showModal(modal);
                } else {
                    hideModal(modal);
                }
            },
            bindDeleteConfirmButton: function () {
                const btnId = 'confirm_delete_files_' + this.uid;
                const btn = document.getElementById(btnId);
                if (btn) {
                    btn.onclick = this.deleteFiles;
                }
            },
            sendRequest: function (url, payload = {}) {
                return sendRequest(url, payload);
            },
            getFiles: function () {
                const vm = this;
                vm.is_loading = true;
                this.sendRequest(routes.files, {
                    folder: vm.current_folder,
                    details: vm.details
                })
                    .then(function (data) {
                        vm.files = [];
                        for (let i = 0, file; (file = data[i]); i++) {
                            if (vm.filter(file)) {
                                vm.files.push(file);
                            }
                        }
                        vm.selected_files = [];
                        if (vm.preSelect && data.length > 0) {
                            vm.selected_files.push(data[0]);
                        }
                    })
                    .catch(function (error) {
                        console.error('Voyager media: getFiles failed', error);
                        const toastr = getToastr();
                        toastr && toastr.error(i18n.errorUploading || 'Error', i18n.whoopsie || 'Whoops');
                    })
                    .finally(function () {
                        vm.is_loading = false;
                        vm.$nextTick(vm.bindDeleteConfirmButton);
                    });
            },
            selectFile: function (file, e) {
                if ((!e.ctrlKey && !e.metaKey && !e.shiftKey) || !this.allowMultiSelect) {
                    this.selected_files = [];
                }

                if (e.shiftKey && this.allowMultiSelect && this.selected_files.length == 1) {
                    let index = null;
                    let start = 0;
                    for (let i = 0, cfile; (cfile = this.files[i]); i++) {
                        if (cfile === this.selected_file) {
                            start = i;
                            break;
                        }
                    }

                    let end = 0;
                    for (let i = 0, cfile; (cfile = this.files[i]); i++) {
                        if (cfile === file) {
                            end = i;
                            break;
                        }
                    }

                    for (let i = start; i < end; i++) {
                        index = this.selected_files.indexOf(this.files[i]);
                        if (index === -1) {
                            this.selected_files.push(this.files[i]);
                        }
                    }
                }

                let index = this.selected_files.indexOf(file);
                if (index === -1) {
                    this.selected_files.push(file);
                }

                if (this.selected_files.length == 1) {
                    const vm = this;
                    this.$nextTick(function () {
                        if (vm.fileIs(vm.selected_file, 'video')) {
                            vm.$refs.videoplayer && vm.$refs.videoplayer.load();
                        } else if (vm.fileIs(vm.selected_file, 'audio')) {
                            vm.$refs.audioplayer && vm.$refs.audioplayer.load();
                        }
                    });
                }
            },
            openFile: function (file) {
                if (file.type == 'folder') {
                    this.current_folder += file.name + '/';
                    this.getFiles();
                } else if (this.hidden_element) {
                    this.addFileToInput(file);
                } else if (this.fileIs(this.selected_file, 'image')) {
                    this.toggleModalVisibility('imagemodal_' + this.uid, true);
                }
            },
            isFileSelected: function (file) {
                return this.selected_files.includes(file);
            },
            fileIs: function (file, type) {
                if (!file || !type) {
                    return false;
                }

                const candidate = this.normalizeSelectionEntry(file);
                if (!candidate) {
                    return false;
                }

                const normalizedType = candidate.type || this.detectTypeFromPath(candidate.path || candidate.relative_path);
                if (normalizedType && normalizedType.indexOf(type) !== -1) {
                    return true;
                }

                const path = (candidate.path || candidate.relative_path || '').toLowerCase();
                if (type === 'image') {
                    return this.endsWithAny(['jpg', 'jpeg', 'png', 'bmp', 'gif', 'webp', 'svg'], path);
                }

                if (type === 'video') {
                    return this.endsWithAny(['mp4', 'mov', 'ogg', 'webm'], path);
                }

                if (type === 'audio') {
                    return this.endsWithAny(['mp3', 'wav', 'ogg'], path);
                }

                if (type === 'zip') {
                    return this.endsWithAny(['zip', 'rar', '7z', 'tar', 'gz'], path);
                }

                if (type === 'folder') {
                    return normalizedType === 'folder';
                }

                return false;
            },
            getCurrentPath: function () {
                return this.current_folder
                    .replace(this.basePath, '')
                    .split('/')
                    .filter(function (el) {
                        return el != '';
                    });
            },
            setCurrentPath: function (i) {
                if (i == -1) {
                    this.current_folder = this.basePath;
                } else {
                    const path = this.getCurrentPath();
                    path.length = i + 1;
                    this.current_folder = this.basePath + path.join('/') + '/';
                }

                this.getFiles();
            },
            filter: function (file) {
                if (!file || typeof file !== 'object') {
                    console.warn('[media-manager] filter received invalid file reference', file);
                    return false;
                }
                if (this.allowedTypes.length > 0) {
                    if (file.type != 'folder') {
                        for (let i = 0, type; (type = this.allowedTypes[i]); i++) {
                            if (file.type.includes(type)) {
                                return true;
                            }
                        }
                    }
                }

                if (file.type == 'folder' && this.showFolders) {
                    return true;
                } else if (file.type == 'folder' && !this.showFolders) {
                    return false;
                }
                if (this.allowedTypes.length == 0) {
                    return true;
                }

                return false;
            },
            addFileToInput: function (file) {
                if (!this.hidden_element || !file || file.type === 'folder') {
                    return;
                }

                const path = file.relative_path || file.path || file.full_path || file.name || '';
                if (!path) {
                    return;
                }

                if (!this.allowMultiSelect) {
                    this.hidden_element.value = path;
                    this.$forceUpdate();
                    return;
                }

                const content = this.getRawSelectedValues();
                if (content.indexOf(path) !== -1) {
                    return;
                }

                if (content.length >= this.maxSelectedFiles && this.maxSelectedFiles > 0) {
                    const msgSing = messages.maxFilesSelect && messages.maxFilesSelect.singular;
                    const msgPlur = messages.maxFilesSelect && messages.maxFilesSelect.plural;
                    const toastr = getToastr();
                    if (this.maxSelectedFiles == 1) {
                        toastr && toastr.error(msgSing || '');
                    } else {
                        toastr && toastr.error((msgPlur || '').replace('2', this.maxSelectedFiles));
                    }
                    return;
                }

                content.push(path);
                this.hidden_element.value = JSON.stringify(content);
                this.$forceUpdate();
            },
            removeSelectedFile: function (file) {
                if (!this.hidden_element) {
                    return;
                }

                const targetPath = this.getFilePath(file);
                if (!targetPath) {
                    return;
                }

                if (!this.allowMultiSelect) {
                    this.hidden_element.value = '';
                } else {
                    const entries = this.getRawSelectedValues().filter(function (entry) {
                        return entry !== targetPath;
                    });
                    this.hidden_element.value = JSON.stringify(entries);
                }

                this.$forceUpdate();
            },
            removeFileFromInput: function (path) {
                this.removeSelectedFile(path);
            },
            getRawSelectedValues: function () {
                if (!this.hidden_element) {
                    return [];
                }

                if (!this.allowMultiSelect) {
                    return this.hidden_element.value ? [this.hidden_element.value] : [];
                }

                if (!this.hidden_element.value) {
                    return [];
                }

                try {
                    return JSON.parse(this.hidden_element.value);
                } catch (error) {
                    console.error('[media-manager] failed to parse selected files from hidden input', error);
                    return [];
                }
            },
            getSelectedFiles: function () {
                const rawEntries = this.getRawSelectedValues();

                return rawEntries
                    .map(this.normalizeSelectionEntry.bind(this))
                    .filter(function (entry) {
                        return entry !== null;
                    });
            },
            normalizeSelectionEntry: function (entry) {
                if (!entry) {
                    return null;
                }

                if (typeof entry === 'object') {
                    if (!entry.name) {
                        entry.name = this.extractNameFromPath(entry.path || entry.relative_path || '');
                    }

                    if (!entry.type) {
                        entry.type = this.detectTypeFromPath(entry.path || entry.relative_path || '');
                    }

                    return entry;
                }

                const path = entry.toString();
                return {
                    path: path,
                    relative_path: path,
                    name: this.extractNameFromPath(path),
                    type: this.detectTypeFromPath(path),
                    size: null,
                    thumbnails: [],
                    last_modified: null
                };
            },
            getFilePath: function (file) {
                if (!file) {
                    return '';
                }

                if (typeof file === 'string') {
                    return file;
                }

                return file.path || file.relative_path || '';
            },
            getFileTypeClass: function (file) {
                if (file && typeof file === 'object' && file.type) {
                    return file.type;
                }

                return '';
            },
            renameFile: function (object) {
                const vm = this;
                if (!this.allowRename || vm.selected_file.name == object.target.value) {
                    return;
                }
                this.sendRequest(routes.rename, {
                    folder_location: vm.current_folder,
                    filename: vm.selected_file.name,
                    new_filename: object.target.value
                })
                    .then(function (data) {
                        const toastr = getToastr();
                        if (data.success == true) {
                            toastr && toastr.success(i18n.successRenamed || '', i18n.sweetSuccess || '');
                            vm.getFiles();
                        } else {
                            toastr && toastr.error(data.error, i18n.whoopsie || '');
                        }
                    })
                    .catch(function (error) {
                        console.error('Voyager media: inline rename failed', error);
                        const toastr = getToastr();
                        toastr && toastr.error(i18n.errorRenamingExt || '', i18n.whoopsie || '');
                    });
            },
            createFolder: function () {
                if (!this.allowCreateFolder) {
                    return;
                }
                const vm = this;
                const name = this.modals.new_folder.name;
                this.sendRequest(routes.newFolder, { new_folder: vm.current_folder + '/' + name })
                    .then(function (data) {
                        const toastr = getToastr();
                        if (data.success == true) {
                            toastr && toastr.success((i18n.successfullyCreated || '') + ' ' + name, i18n.sweetSuccess || '');
                            vm.getFiles();
                        } else {
                            toastr && toastr.error(data.error, i18n.whoopsie || '');
                        }
                        vm.modals.new_folder.name = '';
                    })
                    .catch(function (error) {
                        console.error('Voyager media: createFolder failed', error);
                        const toastr = getToastr();
                        toastr && toastr.error(i18n.errorCreatingDir || '', i18n.whoopsie || '');
                    })
                    .finally(function () {
                        vm.toggleModalVisibility('create_dir_modal_' + vm.uid, false);
                    });
            },
            deleteFiles: function () {
                if (!this.allowDelete) {
                    return;
                }
                const vm = this;
                this.sendRequest(routes.delete, {
                    path: vm.current_folder,
                    files: vm.selected_files
                })
                    .then(function (data) {
                        const toastr = getToastr();
                        if (data.success == true) {
                            toastr && toastr.success('', i18n.sweetSuccess || '');
                            vm.getFiles();
                        } else {
                            toastr && toastr.error(data.error, i18n.whoopsie || '');
                            vm.getFiles();
                        }
                    })
                    .catch(function (error) {
                        console.error('Voyager media: deleteFiles failed', error);
                        const toastr = getToastr();
                        toastr && toastr.error(i18n.errorDeletingFile || '', i18n.whoopsie || '');
                    })
                    .finally(function () {
                        vm.toggleModalVisibility('confirm_delete_modal_' + vm.uid, false);
                    });
            },
            moveFiles: function () {
                if (!this.allowMove) {
                    return;
                }
                const vm = this;
                const destination = this.modals.move_files.destination;
                if (destination === '') {
                    return;
                }
                vm.toggleModalVisibility('move_files_modal_' + vm.uid, false);
                this.sendRequest(routes.move, {
                    path: vm.current_folder,
                    files: vm.selected_files,
                    destination: destination
                })
                    .then(function (data) {
                        const toastr = getToastr();
                        if (data.success == true) {
                            toastr && toastr.success(i18n.successMoved || '', i18n.sweetSuccess || '');
                            vm.getFiles();
                        } else {
                            toastr && toastr.error(data.error, i18n.whoopsie || '');
                        }

                        vm.modals.move_files.destination = '';
                    })
                    .catch(function (error) {
                        console.error('Voyager media: moveFiles failed', error);
                        const toastr = getToastr();
                        toastr && toastr.error(i18n.errorMoving || '', i18n.whoopsie || '');
                    });
            },
            crop: async function (mode) {
                if (!this.allowCrop) {
                    return;
                }
                // overwritten crop needs explicit confirmation
                if (!mode) {
                    const modal = document.getElementById('media_crop_override_modal');
                    const confirmed = await confirmAction(modal);
                    if (!confirmed) {
                        return;
                    }
                }

                const vm = this;
                const postData = Object.assign({}, vm.croppedData || {}, {
                    originImageName: vm.selected_file.name,
                    upload_path: vm.current_folder,
                    createMode: mode
                });

                if (vm.modals && vm.modals.crop) {
                    const maxWidth = parseInt(vm.modals.crop.max_width, 10);
                    const maxHeight = parseInt(vm.modals.crop.max_height, 10);
                    if (Number.isFinite(maxWidth) && maxWidth > 0) {
                        postData.max_width = maxWidth;
                    }
                    if (Number.isFinite(maxHeight) && maxHeight > 0) {
                        postData.max_height = maxHeight;
                    }
                }

                this.sendRequest(routes.crop, postData)
                    .then(function (data) {
                        const toastr = getToastr();
                        if (data.success) {
                            toastr && toastr.success(data.message);
                            vm.previewBust = Date.now();
                            vm.getFiles();
                        } else {
                            toastr && toastr.error(data.error, i18n.whoopsie || '');
                        }
                    })
                    .catch(function (error) {
                        console.error('Voyager media: crop failed', error);
                        const toastr = getToastr();
                        toastr && toastr.error(i18n.errorUploading || '', i18n.whoopsie || '');
                    })
                    .finally(function () {
                        vm.toggleModalVisibility('crop_modal_' + vm.uid, false);
                    });
            },
            setCropAspectRatio: function () {
                if (!this._cropperInstance) {
                    return;
                }
                const raw = this.modals && this.modals.crop ? this.modals.crop.aspect : 'free';
                if (raw === 'free') {
                    this._cropperInstance.setAspectRatio(NaN);
                    return;
                }
                const ratio = parseFloat(raw);
                this._cropperInstance.setAspectRatio(Number.isFinite(ratio) ? ratio : NaN);
            },
            addSelectedFiles: function () {
                const vm = this;
                for (let i = 0; i < vm.selected_files.length; i++) {
                    vm.openFile(vm.selected_files[i]);
                }
            },
            bytesToSize: function (bytes) {
                const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
                if (bytes == 0) return '0 Bytes';
                const i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
                return Math.round(bytes / Math.pow(1024, i), 2) + ' ' + sizes[i];
            },
            getFileName: function (file) {
                if (!file) {
                    return '';
                }

                if (typeof file === 'object' && file.name) {
                    return file.name;
                }

                return this.extractNameFromPath(this.getFilePath(file));
            },
            imgIcon: function (fileOrPath) {
                if (!fileOrPath) {
                    return '';
                }

                let path = typeof fileOrPath === 'object' ? this.getFilePath(fileOrPath) : fileOrPath;
                const lastModified =
                    typeof fileOrPath === 'object'
                        ? fileOrPath.last_modified || fileOrPath.lastModified || null
                        : null;

                path = this.resolvePreviewPath(path, this.buildCacheBust(lastModified));
                return (
                    'background-size: cover; background-image: url("' +
                    path +
                    '"); background-repeat:no-repeat; background-position:center center;display:inline-block; width:100%; height:100%;'
                );
            },
            buildCacheBust: function (lastModified) {
                const parts = [];
                if (lastModified) {
                    parts.push('v=' + encodeURIComponent(lastModified));
                }
                if (this.previewBust) {
                    parts.push('b=' + encodeURIComponent(this.previewBust));
                }
                return parts.join('&');
            },
            resolvePreviewPath: function (path, cacheBust) {
                if (!path) {
                    return '';
                }

                path = path.replace(/\\/g, '/');
                if (path.startsWith('http://') || path.startsWith('https://') || path.startsWith('data:')) {
                    if (cacheBust) {
                        return path + (path.indexOf('?') >= 0 ? '&' : '?') + cacheBust;
                    }
                    return path;
                }

                path = path.replace(/^\/+/, '');
                let fullPath = storageBaseUrl + path;
                if (cacheBust) {
                    fullPath = fullPath + (fullPath.indexOf('?') >= 0 ? '&' : '?') + cacheBust;
                }
                return fullPath;
            },
            extractNameFromPath: function (path) {
                if (!path) {
                    return '';
                }

                path = path.replace(/\\/g, '/');
                const segments = path.split('/');
                return segments[segments.length - 1];
            },
            detectTypeFromPath: function (path) {
                if (!path) {
                    return '';
                }

                const lower = path.toLowerCase();
                if (this.endsWithAny(['jpg', 'jpeg', 'png', 'bmp', 'gif', 'webp', 'svg'], lower)) {
                    return 'image';
                }
                if (this.endsWithAny(['mp4', 'mov', 'ogg', 'webm'], lower)) {
                    return 'video';
                }
                if (this.endsWithAny(['mp3', 'wav', 'ogg'], lower)) {
                    return 'audio';
                }
                if (this.endsWithAny(['zip', 'rar', '7z', 'tar', 'gz'], lower)) {
                    return 'zip';
                }

                return '';
            },
            dateFilter: function (date) {
                if (!date) {
                    return null;
                }
                const parsed = new Date(date * 1000);

                const month = '0' + (parsed.getMonth() + 1);
                const minutes = '0' + parsed.getMinutes();
                const seconds = '0' + parsed.getSeconds();

                return (
                    parsed.getFullYear() +
                    '-' +
                    month.substr(-2) +
                    '-' +
                    parsed.getDate() +
                    ' ' +
                    parsed.getHours() +
                    ':' +
                    minutes.substr(-2) +
                    ':' +
                    seconds.substr(-2)
                );
            },
            endsWithAny: function (suffixes, string) {
                return suffixes.some(function (suffix) {
                    return string.endsWith(suffix);
                });
            },
            triggerUpload: function () {
                const input = document.getElementById('file_upload_input_' + this.uid);
                if (input) {
                    input.click();
                }
            },
            handleFileUpload: function (e) {
                const files = e.target.files || e.dataTransfer.files;
                if (!files.length) {
                    return;
                }
                this.uploadFiles(files);
            },
            uploadFiles: function (files) {
                const vm = this;
                const totalFiles = files.length;
                let uploadedFiles = 0;
                const progressContainer = document.getElementById('uploadProgress_' + vm.uid);
                const progressBar = progressContainer ? progressContainer.querySelector('.progress-bar') : null;

                if (progressContainer) {
                    progressContainer.style.display = 'block';
                    progressContainer.style.opacity = '1';
                }

                Array.from(files).forEach(function (file) {
                    const formData = new FormData();
                    formData.append('file', file);
                    formData.append('upload_path', vm.current_folder);
                    formData.append('filename', vm.filename ? vm.filename : 'null');
                    formData.append('details', JSON.stringify(vm.details));
                    formData.append('_token', getCsrfToken());

                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', routes.upload, true);
                    xhr.setRequestHeader('X-CSRF-TOKEN', getCsrfToken());
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

                    xhr.upload.onprogress = function (e) {
                        if (e.lengthComputable && totalFiles === 1) {
                            const percentComplete = (e.loaded / e.total) * 100;
                            if (progressBar) {
                                progressBar.style.width = percentComplete + '%';
                            }
                        }
                    };

                    xhr.onload = function () {
                        uploadedFiles++;
                        const toastr = getToastr();
                        if (xhr.status === 200) {
                            try {
                                const res = JSON.parse(xhr.responseText);
                                if (res.success) {
                                    toastr && toastr.success(res.message);
                                } else {
                                    toastr && toastr.error(res.message);
                                }
                            } catch (e) {
                                toastr && toastr.error(i18n.whoopsie || 'Whoops');
                            }
                        } else {
                            toastr && toastr.error(i18n.errorUploading || 'Error uploading');
                        }

                        if (uploadedFiles === totalFiles) {
                            if (progressBar) {
                                progressBar.style.width = '100%';
                            }
                            setTimeout(function () {
                                if (progressContainer) {
                                    progressContainer.style.opacity = '0';
                                    setTimeout(function () {
                                        progressContainer.style.display = 'none';
                                        if (progressBar) {
                                            progressBar.style.width = '0%';
                                        }
                                    }, 500);
                                }
                                vm.getFiles();
                            }, 1000);
                        }
                    };

                    xhr.onerror = function () {
                        uploadedFiles++;
                        const toastr = getToastr();
                        toastr && toastr.error(i18n.errorUploading || 'Error uploading');
                    };

                    xhr.send(formData);
                });
            },
        },
        mounted: function () {
            this.getFiles();
            const vm = this;

            if (this.allowUpload) {
                const dropZone = this.$el;
                dropZone.addEventListener('dragover', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropZone.classList.add('drag-over');
                });

                dropZone.addEventListener('dragleave', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropZone.classList.remove('drag-over');
                });

                dropZone.addEventListener('drop', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropZone.classList.remove('drag-over');

                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        vm.uploadFiles(files);
                    }
                });
            }

            if (this.element != '') {
                this.hidden_element = document.querySelector(this.element);
                if (!this.hidden_element) {
                    console.error('Element "' + this.element + '" could not be found.');
                } else if (this.maxSelectedFiles > 1 && this.hidden_element.value == '') {
                    this.hidden_element.value = '[]';
                }
            }

            this.onkeydown = function (evt) {
                evt = evt || window.event;
                if (evt.keyCode == 39) {
                    evt.preventDefault();
                    for (let i = 0, file; (file = vm.files[i]); i++) {
                        if (file === vm.selected_file) {
                            i = i + 1;
                            i = i % vm.files.length;
                            vm.selectFile(vm.files[i], evt);
                            break;
                        }
                    }
                } else if (evt.keyCode == 37) {
                    evt.preventDefault();
                    for (let i = 0, file; (file = vm.files[i]); i++) {
                        if (file === vm.selected_file) {
                            if (i === 0) {
                                i = vm.files.length;
                            }
                            i = i - 1;
                            vm.selectFile(vm.files[i], evt);
                            break;
                        }
                    }
                } else if (evt.keyCode == 13) {
                    evt.preventDefault();
                    if (evt.target.tagName != 'INPUT') {
                        vm.openFile(vm.selected_file, null);
                    }
                }
            };

            if (this.allowCrop) {
                const cropperModal = document.getElementById('crop_modal_' + vm.uid);
                let cropperInstance = null;

                if (cropperModal) {
                    cropperModal.addEventListener('shown.bs.modal', function () {
                        if (cropperInstance && typeof cropperInstance.destroy === 'function') {
                            cropperInstance.destroy();
                            cropperInstance = null;
                        }
                        const croppingImage = document.getElementById('cropping-image_' + vm.uid);
                        if (!croppingImage) {
                            console.warn('Voyager media: cropping image element not found');
                            return;
                        }
                        const Cropper = window.Voyager && window.Voyager.Cropper;
                        if (typeof Cropper === 'undefined') {
                            console.error('Voyager media: Cropper library is not available');
                            return;
                        }
                        cropperInstance = new Cropper(croppingImage, {
                            viewMode: 1,
                            responsive: true,
                            background: false,
                            crop: function (e) {
                                const widthEl = document.getElementById('new-image-width_' + vm.uid);
                                const heightEl = document.getElementById('new-image-height_' + vm.uid);
                                if (widthEl) widthEl.innerText = Math.round(e.detail.width) + 'px';
                                if (heightEl) heightEl.innerText = Math.round(e.detail.height) + 'px';
                                vm.croppedData = {
                                    x: Math.round(e.detail.x),
                                    y: Math.round(e.detail.y),
                                    height: Math.round(e.detail.height),
                                    width: Math.round(e.detail.width)
                                };
                            }
                        });
                        vm._cropperInstance = cropperInstance;
                        vm.setCropAspectRatio();
                    });

                    cropperModal.addEventListener('hidden.bs.modal', function () {
                        if (cropperInstance && typeof cropperInstance.destroy === 'function') {
                            cropperInstance.destroy();
                            cropperInstance = null;
                        }
                        vm._cropperInstance = null;
                    });
                }
            }

            onReady(function () {
                document.querySelectorAll('.form-edit-add').forEach(function (form) {
                    if (form.dataset.voyagerMediaValidated === '1') return;
                    form.dataset.voyagerMediaValidated = '1';
                    form.addEventListener('submit', function (e) {
                        if (!vm.hidden_element) {
                            return;
                        }
                        const toastr = getToastr();
                        if (vm.maxSelectedFiles > 1) {
                            const content = JSON.parse(vm.hidden_element.value || '[]');
                            if (content.length < vm.minSelectedFiles) {
                                e.preventDefault();
                                const msgSing = messages.minFilesSelect && messages.minFilesSelect.singular;
                                const msgPlur = messages.minFilesSelect && messages.minFilesSelect.plural;
                                if (vm.minSelectedFiles == 1) {
                                    toastr && toastr.error(msgSing || '');
                                } else {
                                    toastr && toastr.error((msgPlur || '').replace('2', vm.minSelectedFiles));
                                }
                            }
                        } else if (vm.minSelectedFiles > 0 && vm.hidden_element.value === '') {
                            e.preventDefault();
                            const msg = messages.minFilesSelect && messages.minFilesSelect.singular;
                            toastr && toastr.error(msg || '');
                        }
                    });
                });

                const ddContainer = document.getElementById('dd_' + vm.uid);
                const Sortable = window.Voyager && window.Voyager.Sortable;
                if (ddContainer && Sortable) {
                    const ddList = ddContainer.querySelector('.dd-list') || ddContainer;
                    Sortable.create(ddList, {
                        handle: '.file_link',
                        draggable: '.dd-item',
                        animation: 150,
                        onEnd: function () {
                            if (!vm.allowMultiSelect) {
                                return;
                            }
                            const newContent = [];
                            ddList.querySelectorAll('.dd-item').forEach(function (item) {
                                if (item.dataset && item.dataset.url) {
                                    newContent.push(item.dataset.url);
                                }
                            });
                            vm.hidden_element.value = JSON.stringify(newContent);
                        }
                    });
                }

                const createDirModal = document.getElementById('create_dir_modal_' + vm.uid);
                if (createDirModal) {
                    createDirModal.addEventListener('hidden.bs.modal', function () {
                        vm.modals.new_folder.name = '';
                    });
                }

                const moveFilesModal = document.getElementById('move_files_modal_' + vm.uid);
                if (moveFilesModal) {
                    moveFilesModal.addEventListener('hidden.bs.modal', function () {
                        vm.modals.move_files.destination = '';
                    });
                }
            });
        }
    });

    componentRegistered = true;
};

export const initMediaManager = () => {
    if (typeof document === 'undefined') return;

    const roots = findMountRoots();
    if (!roots.length) return;

    const config = parseJsonConfig('voyager-media-manager-config');
    const templateHtml = getTemplateHtml('voyager-media-manager-template');
    if (!config || !templateHtml) return;

    window.Voyager.withVue(function (Vue) {
        registerComponent(Vue, templateHtml, config);
        mountRoots(Vue);
    });
};

export const subscribeToEvents = (events) => {
    if (!events || typeof events.on !== 'function') return;
    events.on('dom:updated', () => initMediaManager());
};
