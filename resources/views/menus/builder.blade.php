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


    <div class="modal modal-danger fade" tabindex="-1" id="delete_modal" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('voyager::generic.close') }}"><span
                            aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="voyager-trash"></i> {{ __('voyager::menu_builder.delete_item_question') }}</h4>
                </div>
                <div class="modal-footer">
                    <form action="{{ route('voyager.menus.item.destroy', ['menu' => $menu->id, 'id' => '__id']) }}"
                          id="delete_form"
                          method="POST">
                        {{ method_field("DELETE") }}
                        {{ csrf_field() }}
                        <input type="submit" class="btn btn-danger pull-right delete-confirm"
                               value="{{ __('voyager::menu_builder.delete_item_confirm') }}">
                    </form>
                    <button type="button" class="btn btn-default pull-right" data-dismiss="modal">{{ __('voyager::generic.cancel') }}</button>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->


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
                        <div>
                            @include('voyager::multilingual.language-selector')
                            <label for="name">{{ __('voyager::menu_builder.item_title') }}</label>
                            @include('voyager::multilingual.input-hidden', ['_field_name' => 'title', '_field_trans' => ''])
                            <input type="text" class="form-control" id="m_title" name="title" placeholder="{{ __('voyager::generic.title') }}"><br>
                        </div>
                        <label for="type">{{ __('voyager::menu_builder.link_type') }}</label>
                        <select id="m_link_type" class="form-control" name="type">
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
                        <select id="m_target" class="form-control" name="target">
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
            window.whenAppReady(function() {
                var csrfMeta = document.querySelector('meta[name="csrf-token"]');
                var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
            var addLabel = @json(__('voyager::generic.add'));
            var updateLabel = @json(__('voyager::generic.update'));
            var menuModal = document.getElementById('menu_item_modal');
            var deleteModal = document.getElementById('delete_modal');
            var menuForm = document.getElementById('m_form');
            var formMethodInput = document.getElementById('m_form_method');
            var idInput = document.getElementById('m_id');
            var titleInput = document.getElementById('m_title');
            var titleTranslationsInput = document.getElementById('title_i18n');
            var urlInput = document.getElementById('m_url');
            var routeInput = document.getElementById('m_route');
            var paramsInput = document.getElementById('m_parameters');
            var iconInput = document.getElementById('m_icon_class');
            var colorInput = document.getElementById('m_color');
            var targetSelect = document.getElementById('m_target');
            var linkTypeSelect = document.getElementById('m_link_type');
            var urlTypeContainer = document.getElementById('m_url_type');
            var routeTypeContainer = document.getElementById('m_route_type');
            var addHeading = document.getElementById('m_hd_add');
            var editHeading = document.getElementById('m_hd_edit');
            var submitButton = menuForm ? menuForm.querySelector('input[type="submit"]') : null;
            var deleteForm = document.getElementById('delete_form');
            var deleteActionTemplate = deleteForm ? deleteForm.getAttribute('action') : '';
            var menuOrderUrl = '{{ route('voyager.menus.order_item',['menu' => $menu->id]) }}';
            var modalMultilingualInstance = null;

            function initMultilingualSections() {
                if (!window.VoyagerInitMultilingual) {
                    return;
                }
                window.VoyagerInitMultilingual('.side-body', {
                    transInputs: '.dd-list input[data-i18n=true]'
                });
                var modalInstance = window.VoyagerInitMultilingual('#menu_item_modal', {
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

            function createBackdrop(modal) {
                var backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade in';
                backdrop.dataset.modalTarget = modal.id;
                return backdrop;
            }

            function openModal(modal) {
                if (!modal) {
                    return;
                }
                modal.classList.add('in');
                modal.style.display = 'block';
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('modal-open');
                document.body.appendChild(createBackdrop(modal));
            }

            function closeModal(modal) {
                if (!modal) {
                    return;
                }
                modal.classList.remove('in');
                modal.style.display = 'none';
                modal.setAttribute('aria-hidden', 'true');
                var backdrop = document.querySelector('.modal-backdrop[data-modal-target="' + modal.id + '"]');
                if (backdrop) {
                    backdrop.remove();
                }
                if (!document.querySelector('.modal.in')) {
                    document.body.classList.remove('modal-open');
                }
            }

            function bindModalDismiss(modal) {
                if (!modal) {
                    return;
                }
                modal.querySelectorAll('[data-dismiss="modal"]').forEach(function (button) {
                    button.addEventListener('click', function () {
                        closeModal(modal);
                    });
                });
                modal.addEventListener('click', function (event) {
                    if (event.target === modal) {
                        closeModal(modal);
                    }
                });
            }

            bindModalDismiss(menuModal);
            bindModalDismiss(deleteModal);

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
                    var parsed = JSON.parse(value);
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
                toggleModalHeading(true);
                setLinkType('url');
                if (targetSelect) {
                    targetSelect.value = '_self';
                }
                openModal(menuModal);
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

                var id = button.dataset.id || '';
                if (idInput) {
                    idInput.value = id;
                }
                if (titleInput) {
                    titleInput.value = button.dataset.title || '';
                }
                if (titleTranslationsInput) {
                    var translationSource = document.getElementById('title' + id + '_i18n');
                    titleTranslationsInput.value = translationSource ? translationSource.value : '';
                }
                if (modalMultilingualInstance && typeof modalMultilingualInstance.refresh === 'function') {
                    modalMultilingualInstance.refresh();
                }

                var targetValue = button.dataset.target || '_self';
                if (targetSelect) {
                    targetSelect.value = targetValue;
                }

                var routeValue = button.dataset.route || '';
                var urlValue = button.dataset.url || '';
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

                openModal(menuModal);
            }

            function openDeleteModal(button) {
                if (!deleteModal || !deleteForm || !deleteActionTemplate) {
                    return;
                }
                var id = button.dataset.id;
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
                var editButton = event.target.closest('.item_actions .edit');
                if (editButton) {
                    event.preventDefault();
                    openEditModal(editButton);
                    return;
                }
                var deleteButton = event.target.closest('.item_actions .delete');
                if (deleteButton) {
                    event.preventDefault();
                    openDeleteModal(deleteButton);
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key !== 'Escape') {
                    return;
                }
                if (menuModal && menuModal.classList.contains('in')) {
                    closeModal(menuModal);
                }
                if (deleteModal && deleteModal.classList.contains('in')) {
                    closeModal(deleteModal);
                }
            });

            var menuNestable = document.querySelector('.dd');
            if (menuNestable && window.VoyagerInitNestable) {
                window.VoyagerInitNestable(menuNestable);
                menuNestable.addEventListener('voyager.sortable.updated', function (event) {
                    var structure = event.detail && event.detail.structure
                        ? event.detail.structure
                        : (window.VoyagerSerializeNestable ? window.VoyagerSerializeNestable(menuNestable) : []);

                    var payload = new URLSearchParams();
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
            }); // end whenAppReady
        });
    </script>
@stop
