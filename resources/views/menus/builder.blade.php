@extends('voyager::master')

@section('page_title', __('voyager::generic.menu_builder'))

@section('page_header')
    <h1 class="page-title">
        <i class="voyager-list"></i>{{ __('voyager::generic.menu_builder') }} ({{ $menu->name }})
        <div class="btn btn-success add_item"><i class="voyager-plus"></i> {{ __('voyager::menu_builder.new_menu_item') }}</div>
    </h1>
    @include('voyager::multilingual.language-selector')
@stop

@section('content')
    @include('voyager::menus.partial.notice')

    <div class="page-content container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered">
                    <div class="panel-heading">
                        <p class="panel-title" style="color:#777">{{ __('voyager::menu_builder.drag_drop_info') }}</p>
                    </div>

                    <div class="panel-body" style="padding:30px;">
                        <div class="dd">
                            {!! menu($menu->name, 'admin', ['isModelTranslatable' => $isModelTranslatable]) !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    @include('voyager::components.modal-confirm', [
        'id' => 'delete_modal',
        'title' => __('voyager::menu_builder.delete_item_question'),
        'message' => '',
        'confirmText' => __('voyager::menu_builder.delete_item_confirm'),
        'confirmClass' => 'btn-danger delete-confirm',
        'confirmButtonId' => 'delete_confirm_button',
        'icon' => 'voyager-trash'
    ])
    <form action="{{ route('voyager.menus.item.destroy', ['menu' => $menu->id, 'id' => '__id']) }}"
          id="delete_form"
          method="POST"
          style="display:none">
        {{ method_field("DELETE") }}
        {{ csrf_field() }}
    </form>


    <div class="modal modal-info fade" tabindex="-1" id="menu_item_modal" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('voyager::generic.close') }}"><span
                                aria-hidden="true">&times;</span></button>
                    <h4 id="m_hd_add" class="modal-title hidden"><i class="voyager-plus"></i> {{ __('voyager::menu_builder.create_new_item') }}</h4>
                    <h4 id="m_hd_edit" class="modal-title hidden"><i class="voyager-edit"></i> {{ __('voyager::menu_builder.edit_item') }}</h4>
                </div>
                <form action="" id="m_form" method="POST"
                      data-action-add="{{ route('voyager.menus.item.add', ['menu' => $menu->id]) }}"
                      data-action-update="{{ route('voyager.menus.item.update', ['menu' => $menu->id]) }}">

                    <input id="m_form_method" type="hidden" name="_method" value="POST">
                    {{ csrf_field() }}
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12 form-group">
                                <label for="m_status">{{ __('voyager::menu_builder.status') }}</label><br>
                                <input type="hidden" name="status" value="0">
                                <input
                                        id="m_status"
                                        type="checkbox"
                                        name="status"
                                        class="toggleswitch"
                                        value="1"
                                        checked
                                        data-on="{{ __('voyager::menu_builder.status_active') }}"
                                        data-off="{{ __('voyager::menu_builder.status_inactive') }}"
                                        data-onstyle="primary"
                                        data-offstyle="default"
                                >
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <div>
                                    @include('voyager::multilingual.language-selector')
                                    <label for="name">{{ __('voyager::menu_builder.item_title') }}</label>
                                    @include('voyager::multilingual.input-hidden', ['_field_name' => 'title', '_field_trans' => ''])
                                    <input type="text" class="form-control" id="m_title" name="title" placeholder="{{ __('voyager::generic.title') }}"><br>
                                </div>
                            </div>
                        </div>
                        <label for="type">{{ __('voyager::menu_builder.link_type') }}</label>
                        <select id="m_link_type" class="form-control voyager-select" name="type">
                            <option value="url" selected="selected">{{ __('voyager::menu_builder.static_url') }}</option>
                            <option value="route">{{ __('voyager::menu_builder.dynamic_route') }}</option>
                        </select><br>
                        <div id="m_url_type">
                            <label for="url">{{ __('voyager::menu_builder.url') }}</label>
                            <input type="text" class="form-control" id="m_url" name="url" placeholder="{{ __('voyager::generic.url') }}"><br>
                        </div>
                        <div id="m_route_type">
                            <label for="route">{{ __('voyager::menu_builder.item_route') }}</label>
                            <input type="text" class="form-control" id="m_route" name="route" placeholder="{{ __('voyager::generic.route') }}"><br>
                            <label for="parameters">{{ __('voyager::menu_builder.route_parameter') }}</label>
                            <textarea rows="3" class="form-control" id="m_parameters" name="parameters" placeholder="{{ json_encode(['key' => 'value'], JSON_PRETTY_PRINT) }}"></textarea><br>
                        </div>
                        <label for="icon_class">{{ __('voyager::menu_builder.icon_class') }} <a
                                    href="{{ route('voyager.compass.index') }}#fonts"
                                    target="_blank">{!! __('voyager::menu_builder.icon_class2') !!}</label>
                        <input type="text" class="form-control" id="m_icon_class" name="icon_class"
                               placeholder="{{ __('voyager::menu_builder.icon_class_ph') }}"><br>
                        <label for="color">{{ __('voyager::menu_builder.color') }}</label>
                        <input type="color" class="form-control" id="m_color" name="color"
                               placeholder="{{ __('voyager::menu_builder.color_ph') }}"><br>
                        <label for="target">{{ __('voyager::menu_builder.open_in') }}</label>
                        <select id="m_target" class="form-control voyager-select" name="target">
                            <option value="_self" selected="selected">{{ __('voyager::menu_builder.open_same') }}</option>
                            <option value="_blank">{{ __('voyager::menu_builder.open_new') }}</option>
                        </select>
                        <input type="hidden" name="menu_id" value="{{ $menu->id }}">
                        <input type="hidden" name="id" id="m_id" value="">
                    </div>
                    <div class="modal-footer">
                        <input type="submit" class="btn btn-success pull-right delete-confirm__" value="{{ __('voyager::generic.update') }}">
                        <button type="button" class="btn btn-default pull-right" data-dismiss="modal">{{ __('voyager::generic.cancel') }}</button>
                    </div>
                </form>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->




@stop

@section('javascript')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const appReady = (window.Voyager && window.Voyager.ready && window.Voyager.ready.app)
                ? window.Voyager.ready.app
                : Promise.resolve();

            appReady.then(function () {
                const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

                const addLabel = @json(__('voyager::generic.add'));
                const updateLabel = @json(__('voyager::generic.update'));
                const menuModal = document.getElementById('menu_item_modal');
                const deleteModal = document.getElementById('delete_modal');
                const menuForm = document.getElementById('m_form');
                const formMethodInput = document.getElementById('m_form_method');
                const idInput = document.getElementById('m_id');
                const titleInput = document.getElementById('m_title');
                const titleTranslationsInput = document.getElementById('title_i18n');
                const urlInput = document.getElementById('m_url');
                const routeInput = document.getElementById('m_route');
                const paramsInput = document.getElementById('m_parameters');
                const iconInput = document.getElementById('m_icon_class');
                const colorInput = document.getElementById('m_color');
                const targetSelect = document.getElementById('m_target');
                const statusInput = document.getElementById('m_status');
                const linkTypeSelect = document.getElementById('m_link_type');
                const urlTypeContainer = document.getElementById('m_url_type');
                const routeTypeContainer = document.getElementById('m_route_type');
                const addHeading = document.getElementById('m_hd_add');
                const editHeading = document.getElementById('m_hd_edit');
                const submitButton = menuForm ? menuForm.querySelector('input[type="submit"]') : null;
                const deleteForm = document.getElementById('delete_form');
                const deleteActionTemplate = deleteForm ? deleteForm.getAttribute('action') : '';
                const deleteConfirmButton = document.getElementById('delete_confirm_button');
                const menuOrderUrl = '{{ route('voyager.menus.order_item',['menu' => $menu->id]) }}';
                const menuStatusUrlTemplate = '{{ route('voyager.menus.item.status', ['menu' => $menu->id, 'id' => '__id']) }}';
                let modalMultilingualInstance = null;
                let menuStatusToggleRefreshPending = false;

                const scheduleStatusToggleRefresh = () => {
                    if (!statusInput || typeof window.VoyagerRefreshToggle !== 'function') {
                        return;
                    }
                    if (menuStatusToggleRefreshPending) {
                        return;
                    }
                    menuStatusToggleRefreshPending = true;
                    requestAnimationFrame(() => {
                        requestAnimationFrame(() => {
                            menuStatusToggleRefreshPending = false;
                            window.VoyagerRefreshToggle(statusInput);
                        });
                    });
                };

            function initMultilingualSections() {
                if (!window.VoyagerInitMultilingual) {
                    return;
                }
                window.VoyagerInitMultilingual('.side-body', {
                    transInputs: '.dd-list input[data-i18n=true]'
                });
                const modalInstance = window.VoyagerInitMultilingual('#menu_item_modal', {
                    form: 'form',
                    transInputs: '#menu_item_modal input[data-i18n=true]',
                    langSelectors: '#menu_item_modal .language-selector input',
                    editing: true
                });
                modalMultilingualInstance = Array.isArray(modalInstance) ? modalInstance[0] : modalInstance;
            }

            @if ($isModelTranslatable)
                initMultilingualSections();
            @endif

            function prepareHeading(element) {
                if (!element) {
                    return;
                }
                element.classList.remove('hidden');
                element.style.display = 'none';
            }

            prepareHeading(addHeading);
            prepareHeading(editHeading);

            function toggleModalHeading(isAdd) {
                if (addHeading) {
                    addHeading.style.display = isAdd ? '' : 'none';
                }
                if (editHeading) {
                    editHeading.style.display = isAdd ? 'none' : '';
                }
            }

            function openModal(modal) {
                if (!modal) {
                    return;
                }
                if (window.VoyagerBootstrapCompat && typeof window.VoyagerBootstrapCompat.showModal === 'function') {
                    window.VoyagerBootstrapCompat.showModal(modal);
                    return;
                }
                modal.classList.add('in');
                modal.style.display = 'block';
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('modal-open');
            }

            function closeModal(modal) {
                if (!modal) {
                    return;
                }
                if (window.VoyagerBootstrapCompat && typeof window.VoyagerBootstrapCompat.hideModal === 'function') {
                    window.VoyagerBootstrapCompat.hideModal(modal);
                    return;
                }
                modal.classList.remove('in');
                modal.style.display = 'none';
                modal.setAttribute('aria-hidden', 'true');
                if (!document.querySelector('.modal.in')) {
                    document.body.classList.remove('modal-open');
                }
            }

            function setLinkType(type) {
                if (!linkTypeSelect) {
                    return;
                }
                linkTypeSelect.value = type;
                if (linkTypeSelect.value === 'route') {
                    if (urlTypeContainer) {
                        urlTypeContainer.style.display = 'none';
                    }
                    if (routeTypeContainer) {
                        routeTypeContainer.style.display = '';
                    }
                } else {
                    if (urlTypeContainer) {
                        urlTypeContainer.style.display = '';
                    }
                    if (routeTypeContainer) {
                        routeTypeContainer.style.display = 'none';
                    }
                }
            }

            if (linkTypeSelect) {
                linkTypeSelect.addEventListener('change', function () {
                    setLinkType(linkTypeSelect.value);
                });
            }

            function formatParameters(value) {
                if (!value || value === 'null') {
                    return '';
                }
                try {
                    const parsed = JSON.parse(value);
                    return JSON.stringify(parsed, null, 2);
                } catch (error) {
                    return value;
                }
            }

            function resetFormValues() {
                if (!menuForm) {
                    return;
                }
                menuForm.reset();
                if (paramsInput) {
                    paramsInput.value = '';
                }
                if (titleTranslationsInput) {
                    titleTranslationsInput.value = '';
                }
                if (modalMultilingualInstance && typeof modalMultilingualInstance.refresh === 'function') {
                    modalMultilingualInstance.refresh();
                }
            }

            function openAddModal() {
                if (!menuForm) {
                    return;
                }
                resetFormValues();
                menuForm.setAttribute('action', menuForm.dataset.actionAdd);
                if (formMethodInput) {
                    formMethodInput.value = 'POST';
                }
                if (submitButton) {
                    submitButton.value = addLabel;
                }
                if (statusInput) {
                    statusInput.checked = true;
                    statusInput.dispatchEvent(new Event('change', { bubbles: true }));
                }
                toggleModalHeading(true);
                setLinkType('url');
                if (targetSelect) {
                    targetSelect.value = '_self';
                }
                openModal(menuModal);
                scheduleStatusToggleRefresh();
            }

            function openEditModal(button) {
                if (!menuForm || !button) {
                    return;
                }
                resetFormValues();
                menuForm.setAttribute('action', menuForm.dataset.actionUpdate);
                if (formMethodInput) {
                    formMethodInput.value = 'PUT';
                }
                if (submitButton) {
                    submitButton.value = updateLabel;
                }
                toggleModalHeading(false);

                const id = button.dataset.id || '';
                if (idInput) {
                    idInput.value = id;
                }
                if (titleInput) {
                    titleInput.value = button.dataset.title || '';
                }
                if (titleTranslationsInput) {
                    const translationSource = document.getElementById('title' + id + '_i18n');
                    titleTranslationsInput.value = translationSource ? translationSource.value : '';
                }
                if (modalMultilingualInstance && typeof modalMultilingualInstance.refresh === 'function') {
                    modalMultilingualInstance.refresh();
                }

                const targetValue = button.dataset.target || '_self';
                if (targetSelect) {
                    targetSelect.value = targetValue;
                }

                const routeValue = button.dataset.route || '';
                const urlValue = button.dataset.url || '';
                setLinkType(routeValue ? 'route' : 'url');
                if (urlInput) {
                    urlInput.value = urlValue;
                }
                if (routeInput) {
                    routeInput.value = routeValue;
                }
                if (paramsInput) {
                    paramsInput.value = formatParameters(button.dataset.parameters);
                }
                if (iconInput) {
                    iconInput.value = button.dataset.icon_class || '';
                }
                if (colorInput) {
                    colorInput.value = button.dataset.color || '';
                }
                if (statusInput) {
                    statusInput.checked = String(button.dataset.status || '1') !== '0';
                    statusInput.dispatchEvent(new Event('change', { bubbles: true }));
                }

                openModal(menuModal);
                scheduleStatusToggleRefresh();
            }

            function openDeleteModal(button) {
                if (!deleteModal || !deleteForm || !deleteActionTemplate) {
                    return;
                }
                const id = button.dataset.id;
                if (id) {
                    deleteForm.setAttribute('action', deleteActionTemplate.replace('__id', id));
                }
                openModal(deleteModal);
            }

            document.querySelectorAll('.add_item').forEach(function (button) {
                button.addEventListener('click', function (event) {
                    event.preventDefault();
                    openAddModal();
                });
            });

            document.addEventListener('click', function (event) {
                const editButton = event.target.closest('.item_actions .edit');
                if (editButton) {
                    event.preventDefault();
                    openEditModal(editButton);
                    return;
                }
                const deleteButton = event.target.closest('.item_actions .delete');
                if (deleteButton) {
                    event.preventDefault();
                    openDeleteModal(deleteButton);
                }
            });

            if (deleteConfirmButton) {
                deleteConfirmButton.addEventListener('click', function () {
                    if (!deleteForm) return;
                    deleteForm.submit();
                });
            }

            if (menuModal) {
                menuModal.addEventListener('shown.bs.modal', function () {
                    scheduleStatusToggleRefresh();
                });
            }

            document.addEventListener('click', function (event) {
                const statusToggle = event.target.closest('.tree-admin-status .voyager-status-toggle');
                if (!statusToggle) {
                    return;
                }

                event.preventDefault();

                const id = statusToggle.dataset.id;
                if (!id) {
                    return;
                }

                const currentValue = parseInt(statusToggle.dataset.value || '0', 10);
                const newValue = currentValue ? 0 : 1;

                statusToggle.dataset.value = String(newValue);
                statusToggle.classList.toggle('active', !!newValue);
                statusToggle.classList.toggle('inactive', !newValue);

                const itemLi = statusToggle.closest('li.dd-item');
                if (itemLi) {
                    itemLi.classList.toggle('unpublished-record', !newValue);
                }

                const handleEl = statusToggle.closest('.dd-handle');
                const editButton = handleEl ? handleEl.querySelector('.item_actions .edit') : null;
                if (editButton) {
                    editButton.dataset.status = String(newValue);
                }

                const payload = new URLSearchParams();
                payload.append('_token', csrfToken);
                payload.append('status', String(newValue));

                fetch(menuStatusUrlTemplate.replace('__id', id), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        'Accept': 'application/json'
                    },
                    body: payload.toString()
                })
                    .then(function (response) {
                        return response.text().then(function (text) {
                            let json = null;
                            try {
                                json = text ? JSON.parse(text) : null;
                            } catch (e) {
                                json = null;
                            }

                            if (!response.ok || (json && json.status === 'error')) {
                                const message = (json && json.message) ? json.message : ('Menu status update failed with status ' + response.status);
                                throw new Error(message);
                            }

                            toastr.success("{{ __('voyager::generic.successfully_updated') }}");
                        });
                    })
                    .catch(function (error) {
                        console.error('[VoyagerMenuBuilder] status update failed', error);

                        statusToggle.dataset.value = String(currentValue);
                        statusToggle.classList.toggle('active', !!currentValue);
                        statusToggle.classList.toggle('inactive', !currentValue);

                        if (itemLi) {
                            itemLi.classList.toggle('unpublished-record', !currentValue);
                        }
                        if (editButton) {
                            editButton.dataset.status = String(currentValue);
                        }

                        toastr.error(error && error.message ? error.message : "{{ __('voyager::generic.internal_error') }}");
                    });
            });

            const menuNestable = document.querySelector('.dd');
            if (menuNestable && window.VoyagerInitNestable) {
                window.VoyagerInitNestable(menuNestable);
                menuNestable.addEventListener('voyager.sortable.updated', function (event) {
                    const structure = event.detail && event.detail.structure
                        ? event.detail.structure
                        : (window.VoyagerSerializeNestable ? window.VoyagerSerializeNestable(menuNestable) : []);

                    const payload = new URLSearchParams();
                    payload.append('order', JSON.stringify(structure));
                    payload.append('_token', csrfToken);

                    fetch(menuOrderUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                            'Accept': 'application/json'
                        },
                        body: payload.toString()
                    })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Menu order update failed with status ' + response.status);
                        }
                        toastr.success("{{ __('voyager::menu_builder.updated_order') }}");
                    })
                    .catch(function (error) {
                        console.error('[VoyagerNestable:MenuBuilder] order update failed', error);
                        toastr.error("{{ __('voyager::generic.internal_error') }}");
                    });
                });
            }
            }); // end appReady
        });
    </script>
@stop
