@php
    $edit = !is_null($dataTypeContent->getKey());
    $add  = is_null($dataTypeContent->getKey());
    $stickyPanelConfig = config('voyager.bread.sticky_action_panel', []);
    $stickyPanelEnabled = (bool) ($stickyPanelConfig['enabled'] ?? false);
    $stickyPanelAutohide = (bool) ($stickyPanelConfig['autohide'] ?? false);
@endphp

@extends('voyager::master')

@section('css')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@stop

@section('page_title', __('voyager::generic.'.($edit ? 'edit' : 'add')).' '.$dataType->getTranslatedAttribute('display_name_singular'))

@section('page_header')
    <h1 class="page-title">
        <i class="{{ $dataType->icon }}"></i>
        {{ __('voyager::generic.'.($edit ? 'edit' : 'add')).' '.$dataType->getTranslatedAttribute('display_name_singular') }}
    </h1>
    @include('voyager::multilingual.language-selector')
@stop

@section('content')
    <div class="page-content edit-add container-fluid">
        <div class="row">
            <div class="col-md-12">

                <div class="panel panel-bordered">
                    <!-- form start -->
                    <form role="form"
                            id="form-edit-add"
                            class="form-edit-add"
                            data-edit="{{ $edit ? 'true' : 'false' }}"
                            data-url="{{ url()->current() }}"
                            data-url-create="{{ route('voyager.'.$dataType->slug.'.create') }}"
                            action="{{ $edit ? route('voyager.'.$dataType->slug.'.update', $dataTypeContent->getKey()) : route('voyager.'.$dataType->slug.'.store') }}"
                            method="POST" enctype="multipart/form-data">
                        <!-- PUT Method if we are editing -->
                        @if($edit)
                            {{ method_field("PUT") }}
                        @endif

                        <!-- CSRF TOKEN -->
                        {{ csrf_field() }}

                        <input id="redirect-to" type="hidden" name="redirect_to" value="">
                        <input type="hidden" name="model_name" value="{{ $dataType->model_name }}">
                        <input type="hidden" name="model_id" value="{{ optional($dataTypeContent)->id }}">

                        <div class="panel-body">

                            @if (count($errors) > 0)
                                <div class="alert alert-danger">
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <!-- Adding / Editing -->
                            @php
                                $dataTypeRows = $dataType->{($edit ? 'editRows' : 'addRows' )};
                            @endphp

                            @foreach($dataTypeRows as $row)
                                <!-- GET THE DISPLAY OPTIONS -->
                                @php
                                    $display_options = $row->details->display ?? NULL;
                                    if ($dataTypeContent->{$row->field.'_'.($edit ? 'edit' : 'add')}) {
                                        $dataTypeContent->{$row->field} = $dataTypeContent->{$row->field.'_'.($edit ? 'edit' : 'add')};
                                    }
                                @endphp
                                @if (isset($row->details->legend) && isset($row->details->legend->text))
                                    <legend class="text-{{ $row->details->legend->align ?? 'center' }}" style="background-color: {{ $row->details->legend->bgcolor ?? '#f0f0f0' }};padding: 5px;">{{ $row->details->legend->text }}</legend>
                                @endif

                                <div class="form-group @if($row->type == 'hidden') hidden @endif col-md-{{ $display_options->width ?? 12 }} {{ $errors->has($row->field) ? 'has-error' : '' }}" @if(isset($display_options->id)){{ "id=$display_options->id" }}@endif>
                                    {{ $row->slugify }}
                                    <label class="control-label" for="name">{{ $row->getTranslatedAttribute('display_name') }}</label>
                                    @include('voyager::multilingual.input-hidden-bread-edit-add')
                                    @if ($add && isset($row->details->view_add))
                                        @include($row->details->view_add, ['row' => $row, 'dataType' => $dataType, 'dataTypeContent' => $dataTypeContent, 'content' => $dataTypeContent->{$row->field}, 'view' => 'add', 'options' => $row->details])
                                    @elseif ($edit && isset($row->details->view_edit))
                                        @include($row->details->view_edit, ['row' => $row, 'dataType' => $dataType, 'dataTypeContent' => $dataTypeContent, 'content' => $dataTypeContent->{$row->field}, 'view' => 'edit', 'options' => $row->details])
                                    @elseif (isset($row->details->view))
                                        @include($row->details->view, ['row' => $row, 'dataType' => $dataType, 'dataTypeContent' => $dataTypeContent, 'content' => $dataTypeContent->{$row->field}, 'action' => ($edit ? 'edit' : 'add'), 'view' => ($edit ? 'edit' : 'add'), 'options' => $row->details])
                                    @elseif ($row->type == 'relationship')
                                        @include('voyager::formfields.relationship', ['options' => $row->details])
                                    @else
                                        {!! app('voyager')->formField($row, $dataType, $dataTypeContent) !!}
                                    @endif

                                    @foreach (app('voyager')->afterFormFields($row, $dataType, $dataTypeContent) as $after)
                                        {!! $after->handle($row, $dataType, $dataTypeContent) !!}
                                    @endforeach
                                    @if ($errors->has($row->field))
                                        @foreach ($errors->get($row->field) as $error)
                                            <span class="help-block">{{ $error }}</span>
                                        @endforeach
                                    @endif
                                </div>
                            @endforeach

                        </div><!-- panel-body -->

                        @section('submit-buttons')
                            <button type="submit" class="btn btn-primary save">{{ __('voyager::generic.save') }}</button>
                        @stop

                        @if(!$stickyPanelEnabled)
                            <div class="panel-footer">
                                @yield('submit-buttons')
                            </div>
                        @else
                            <div class="float-action-panel float-action-edit{{ $stickyPanelAutohide ? '' : ' locked' }}" data-autohide="{{ $stickyPanelAutohide ? 'true' : 'false' }}">
                                @yield('submit-buttons')
                                @if ($edit)
                                    <button type="button" class="btn btn-success btn-save-and-continue">{{ __('voyager::generic.save_and_continue') }}</button>
                                @endif
                                <button type="button" class="btn btn-warning btn-save-and-create">{{ __('voyager::generic.save_and_create') }}</button>
                            </div>
                        @endif
                    </form>

                    <div style="display:none">
                        <input type="hidden" id="upload_url" value="{{ route('voyager.upload') }}">
                        <input type="hidden" id="upload_type_slug" value="{{ $dataType->slug }}">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade modal-danger" id="confirm_delete_modal">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"
                            aria-hidden="true">&times;</button>
                    <h4 class="modal-title"><i class="voyager-warning"></i> {{ __('voyager::generic.are_you_sure') }}</h4>
                </div>

                <div class="modal-body">
                    <h4>{{ __('voyager::generic.are_you_sure_delete') }} '<span class="confirm_delete_name"></span>'</h4>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('voyager::generic.cancel') }}</button>
                    <button type="button" class="btn btn-danger" id="confirm_delete">{{ __('voyager::generic.delete_confirm') }}</button>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete File Modal -->
@stop

@section('javascript')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
            const confirmDeleteModal = document.getElementById('confirm_delete_modal');
            const confirmDeleteButton = document.getElementById('confirm_delete');
            const confirmDeleteName = confirmDeleteModal ? confirmDeleteModal.querySelector('.confirm_delete_name') : null;
            const mediaRemoveUrl = '{{ route('voyager.'.$dataType->slug.'.media.remove') }}';
            const bootstrapCompat = window.VoyagerBootstrapCompat;
            const deleteState = {
                params: {},
                wrapper: null,
            };

            const showModal = (modal) => {
                if (!modal) {
                    return;
                }
                if (bootstrapCompat && typeof bootstrapCompat.showModal === 'function') {
                    bootstrapCompat.showModal(modal);
                    return;
                }
                modal.classList.add('in');
                modal.style.display = 'block';
                modal.setAttribute('aria-hidden', 'false');
                const backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade in';
                backdrop.dataset.modalTarget = modal.id;
                document.body.appendChild(backdrop);
                document.body.classList.add('modal-open');
            };

            const hideModal = (modal) => {
                if (!modal) {
                    return;
                }
                if (bootstrapCompat && typeof bootstrapCompat.hideModal === 'function') {
                    bootstrapCompat.hideModal(modal);
                    return;
                }
                modal.classList.remove('in');
                modal.style.display = 'none';
                modal.setAttribute('aria-hidden', 'true');
                const backdrop = document.querySelector(`.modal-backdrop[data-modal-target="${modal.id}"]`);
                if (backdrop) {
                    backdrop.remove();
                }
                document.body.classList.remove('modal-open');
            };

            const assignDefaults = () => {
                if (typeof window.VoyagerInitToggles === 'function') {
                    window.VoyagerInitToggles();
                }
                if (typeof window.VoyagerInitDatePickers === 'function') {
                    window.VoyagerInitDatePickers();
                }
                @if ($isModelTranslatable)
                    if (typeof window.VoyagerInitMultilingual === 'function') {
                        window.VoyagerInitMultilingual(document.querySelectorAll('.side-body'), { editing: true });
                    }
                @endif
                if (typeof window.VoyagerInitSlugify === 'function') {
                    window.VoyagerInitSlugify(document.querySelectorAll('.side-body input[data-slug-origin]'));
                }
                if (typeof window.VoyagerInitTooltips === 'function') {
                    window.VoyagerInitTooltips(document.querySelectorAll('[data-toggle="tooltip"]'));
                }
            };

            const findSibling = (container, selector) => {
                if (!container) {
                    return null;
                }
                return Array.from(container.children).find((child) => child.matches(selector)) || null;
            };

            const startDeleteFlow = (trigger, selector, isMulti) => {
                const container = trigger.parentElement;
                const fileNode = findSibling(container, selector);
                if (!container || !fileNode) {
                    return;
                }

                deleteState.params = {
                    slug: '{{ $dataType->slug }}',
                    filename: fileNode.dataset.fileName || '',
                    id: fileNode.dataset.id || '',
                    field: container.dataset.fieldName || '',
                    multi: isMulti,
                    _token: '{{ csrf_token() }}',
                };
                deleteState.wrapper = container;

                if (confirmDeleteName) {
                    confirmDeleteName.textContent = deleteState.params.filename || '';
                }
                showModal(confirmDeleteModal);
            };

            const registerRemovalHandler = (selector, targetTag, isMulti) => {
                document.addEventListener('click', (event) => {
                    const trigger = event.target.closest(selector);
                    if (!trigger) {
                        return;
                    }
                    event.preventDefault();
                    startDeleteFlow(trigger, targetTag, isMulti);
                });
            };

            [
                { selector: '.remove-multi-image', tag: 'img', multi: true },
                { selector: '.remove-single-image', tag: 'img', multi: false },
                { selector: '.remove-multi-file', tag: 'a', multi: true },
                { selector: '.remove-single-file', tag: 'a', multi: false },
            ].forEach(({ selector, tag, multi }) => {
                registerRemovalHandler(selector, tag, multi);
            });

            if (confirmDeleteButton) {
                confirmDeleteButton.addEventListener('click', function () {
                    const formData = new URLSearchParams();
                    Object.keys(deleteState.params).forEach(function (key) {
                        const value = deleteState.params[key];
                        formData.append(key, typeof value === 'boolean' ? Number(value).toString() : value);
                    });

                    fetch(mediaRemoveUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: formData.toString(),
                    })
                        .then(function (response) {
                            if (!response.ok) {
                                throw new Error('Media remove request failed with status ' + response.status);
                            }
                            return response.json();
                        })
                        .then(function (response) {
                            if (response && response.data && response.data.status && response.data.status == 200) {
                                toastr.success(response.data.message);
                                const wrapper = deleteState.wrapper;
                                if (wrapper) {
                                    wrapper.style.transition = 'opacity 0.3s ease';
                                    wrapper.style.opacity = '0';
                                    setTimeout(function () {
                                        wrapper.remove();
                                    }, 300);
                                }
                            } else {
                                toastr.error("Error removing file.");
                            }
                        })
                        .catch(function (error) {
                            console.error('Voyager media remove failed', error);
                            toastr.error("Error removing file.");
                        })
                        .finally(function () {
                            hideModal(confirmDeleteModal);
                        });
                });
            }

            assignDefaults();
        });
    </script>
@stop
