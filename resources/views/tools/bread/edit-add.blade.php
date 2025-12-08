@extends('voyager::master')

@if (isset($dataType->id))
    @section('page_title', __('voyager::bread.edit_bread_for_table', ['table' => $dataType->name]))
    @php
        $display_name = $dataType->getTranslatedAttribute('display_name_singular');
        $display_name_plural = $dataType->getTranslatedAttribute('display_name_plural');
    @endphp
@else
    @section('page_title', __('voyager::bread.create_bread_for_table', ['table' => $table]))
@endif

@section('page_header')
    <div class="page-title">
        <i class="voyager-data"></i>
        @if (isset($dataType->id))
            {{ __('voyager::bread.edit_bread_for_table', ['table' => $dataType->name]) }}
        @else
            {{ __('voyager::bread.create_bread_for_table', ['table' => $table]) }}
        @endif
    </div>
    @php
        $isModelTranslatable = (!isset($isModelTranslatable) || !isset($dataType)) ? false : $isModelTranslatable;
        if (isset($dataType->name)) {
            $table = $dataType->name;
        }
    @endphp
    @include('voyager::multilingual.language-selector')
@stop

@section('breadcrumbs')
<ol class="breadcrumb hidden-xs">
    <li class="active">
        <a href="{{ route('voyager.dashboard')}}"><i class="voyager-boat"></i> {{ __('voyager::generic.dashboard') }}</a>
    </li>
    <li class="active">
        <a href="{{ route('voyager.bread.index') }}">
            {{ __('voyager::generic.bread') }}
        </a>
    </li>
    <li class="active">
        @if(isset($dataType->id))
        <a href="{{ route('voyager.bread.edit', $table) }}">
            {{ $display_name }}
        </a>
        @else
        <a href="{{ route('voyager.bread.create', $table) }}">
            {{ $display_name }}
        </a>
        @endif
    </li>
    <li>
        {{ isset($dataType->id) ? __('voyager::generic.edit') : __('voyager::generic.add') }}
    </li>
</ol>
@endsection

@section('content')
    <div class="page-content container-fluid" id="voyagerBreadEditAdd">
        <div class="row">
            <div class="col-md-12">

                <form action="@if(isset($dataType->id)){{ route('voyager.bread.update', $dataType->id) }}@else{{ route('voyager.bread.store') }}@endif"
                      method="POST" role="form">
                @if(isset($dataType->id))
                    <input type="hidden" value="{{ $dataType->id }}" name="id">
                    {{ method_field("PUT") }}
                @endif
                    <!-- CSRF TOKEN -->
                    {{ csrf_field() }}

                    <div class="panel panel-primary panel-bordered">

                        <div class="panel-heading">
                            <h3 class="panel-title panel-icon"><i class="voyager-bread"></i> {{ ucfirst($table) }} {{ __('voyager::bread.bread_info') }}</h3>
                            <div class="panel-actions">
                                <a class="panel-action voyager-angle-up" data-toggle="panel-collapse" aria-hidden="true"></a>
                            </div>
                        </div>

                        <div class="panel-body">
                            <div class="row clearfix">
                                <div class="col-md-6 form-group">
                                    <label for="name">{{ __('voyager::database.table_name') }}</label>
                                    <input type="text" class="form-control" readonly name="name" placeholder="{{ __('generic_name') }}"
                                           value="{{ $dataType->name ?? $table }}">
                                </div>
                            </div>
                            <div class="row clearfix">
                                <div class="col-md-6 form-group">
                                    <label for="display_name_singular">{{ __('voyager::bread.display_name_singular') }}</label>
                                    @if($isModelTranslatable)
                                        @include('voyager::multilingual.input-hidden', [
                                            'isModelTranslatable' => true,
                                            '_field_name'         => 'display_name_singular',
                                            '_field_trans' => get_field_translations($dataType, 'display_name_singular')
                                        ])
                                    @endif
                                    <input type="text" class="form-control"
                                           name="display_name_singular"
                                           id="display_name_singular"
                                           placeholder="{{ __('voyager::bread.display_name_singular') }}"
                                           value="{{ $display_name }}">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label for="display_name_plural">{{ __('voyager::bread.display_name_plural') }}</label>
                                    @if($isModelTranslatable)
                                        @include('voyager::multilingual.input-hidden', [
                                            'isModelTranslatable' => true,
                                            '_field_name'         => 'display_name_plural',
                                            '_field_trans' => get_field_translations($dataType, 'display_name_plural')
                                        ])
                                    @endif
                                    <input type="text" class="form-control"
                                           name="display_name_plural"
                                           id="display_name_plural"
                                           placeholder="{{ __('voyager::bread.display_name_plural') }}"
                                           value="{{ $display_name_plural }}">
                                </div>
                            </div>
                            <div class="row clearfix">
                                <div class="col-md-6 form-group">
                                    <label for="slug">{{ __('voyager::bread.url_slug') }}</label>
                                    <input type="text" class="form-control" name="slug" placeholder="{{ __('voyager::bread.url_slug_ph') }}"
                                           value="{{ $dataType->slug ?? $slug }}">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label for="icon">{{ __('voyager::bread.icon_hint') }} <a
                                                href="{{ route('voyager.compass.index') }}#fonts"
                                                target="_blank">{{ __('voyager::bread.icon_hint2') }}</a></label>
                                    <input type="text" class="form-control" name="icon"
                                           placeholder="{{ __('voyager::bread.icon_class') }}"
                                           value="{{ $dataType->icon ?? '' }}">
                                </div>
                            </div>
                            <div class="row clearfix">
                                <div class="col-md-6 form-group">
                                    <label for="model_name">{{ __('voyager::bread.model_name') }}</label>
                                    <span class="voyager-question"
                                        aria-hidden="true"
                                        data-toggle="tooltip"
                                        data-placement="right"
                                        title="{{ __('voyager::bread.model_name_ph') }}"></span>
                                    <input type="text" class="form-control" name="model_name" placeholder="{{ __('voyager::bread.model_class') }}"
                                           value="{{ $dataType->model_name ?? $model_name }}">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label for="controller">{{ __('voyager::bread.controller_name') }}</label>
                                    <span class="voyager-question"
                                        aria-hidden="true"
                                        data-toggle="tooltip"
                                        data-placement="right"
                                        title="{{ __('voyager::bread.controller_name_hint') }}"></span>
                                    <input type="text" class="form-control" name="controller" placeholder="{{ __('voyager::bread.controller_name') }}"
                                           value="{{ $dataType->controller ?? '' }}">
                                </div>
                            </div>
                            <div class="row clearfix">
                                <div class="col-md-6 form-group">
                                    <label for="policy_name">{{ __('voyager::bread.policy_name') }}</label>
                                    <span class="voyager-question"
                                          aria-hidden="true"
                                          data-toggle="tooltip"
                                          data-placement="right"
                                          title="{{ __('voyager::bread.policy_name_ph') }}"></span>
                                    <input type="text" class="form-control" name="policy_name" placeholder="{{ __('voyager::bread.policy_class') }}"
                                           value="{{ $dataType->policy_name ?? '' }}">
                                </div>
                                <div class="col-md-3 form-group">
                                    <label for="generate_permissions">{{ __('voyager::bread.generate_permissions') }}</label><br>
                                    <?php $checked = (isset($dataType->generate_permissions) && $dataType->generate_permissions == 1) || (isset($generate_permissions) && $generate_permissions); ?>
                                    <input type="checkbox"
                                           name="generate_permissions"
                                           class="toggleswitch"
                                           data-on="{{ __('voyager::generic.yes') }}"
                                           data-off="{{ __('voyager::generic.no') }}"
                                           @if($checked) checked @endif >
                                </div>
                                <div class="col-md-3 form-group">
                                    <label for="server_side">{{ __('voyager::bread.server_pagination') }}</label><br>
                                    <?php $checked = (isset($dataType->server_side) && $dataType->server_side == 1) || (isset($server_side) && $server_side); ?>
                                    <input type="checkbox"
                                           name="server_side"
                                           class="toggleswitch"
                                           data-on="{{ __('voyager::generic.yes') }}"
                                           data-off="{{ __('voyager::generic.no') }}"
                                           @if($checked) checked @endif >
                                </div>
                            </div>
                            <div class="row clearfix">
                                <div class="col-md-3 form-group">
                                    <label for="order_column">{{ __('voyager::bread.order_column') }}</label>
                                    <span class="voyager-question"
                                          aria-hidden="true"
                                          data-toggle="tooltip"
                                          data-placement="right"
                                          title="{{ __('voyager::bread.order_column_ph') }}"></span>
                                    <select name="order_column" class="select2 form-control">
                                        <option value="">-- {{ __('voyager::generic.none') }} --</option>
                                        @foreach($fieldOptions as $tbl)
                                        <option value="{{ $tbl['field'] }}"
                                                @if(isset($dataType) && $dataType->order_column == $tbl['field']) selected @endif
                                        >{{ $tbl['field'] }}</option>
                                        @endforeach
                                      </select>
                                </div>
                                <div class="col-md-3 form-group">
                                    <label for="order_display_column">{{ __('voyager::bread.order_ident_column') }}</label>
                                    <span class="voyager-question"
                                          aria-hidden="true"
                                          data-toggle="tooltip"
                                          data-placement="right"
                                          title="{{ __('voyager::bread.order_ident_column_ph') }}"></span>
                                    <select name="order_display_column" class="select2 form-control">
                                        <option value="">-- {{ __('voyager::generic.none') }} --</option>
                                        @foreach($fieldOptions as $tbl)
                                        <option value="{{ $tbl['field'] }}"
                                                @if(isset($dataType) && $dataType->order_display_column == $tbl['field']) selected @endif
                                        >{{ $tbl['field'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3 form-group">
                                    <label for="order_direction">{{ __('voyager::bread.order_direction') }}</label>
                                    <select name="order_direction" class="select2 form-control">
                                        <option value="asc" @if(isset($dataType) && $dataType->order_direction == 'asc') selected @endif>
                                            {{ __('voyager::generic.ascending') }}
                                        </option>
                                        <option value="desc" @if(isset($dataType) && $dataType->order_direction == 'desc') selected @endif>
                                            {{ __('voyager::generic.descending') }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-3 form-group">
                                    <label for="default_search_key">{{ __('voyager::bread.default_search_key') }}</label>
                                    <span class="voyager-question"
                                          aria-hidden="true"
                                          data-toggle="tooltip"
                                          data-placement="right"
                                          title="{{ __('voyager::bread.default_search_key_ph') }}"></span>
                                    <select name="default_search_key" class="select2 form-control">
                                        <option value="">-- {{ __('voyager::generic.none') }} --</option>
                                        @foreach($fieldOptions as $tbl)
                                        <option value="{{ $tbl['field'] }}"
                                                @if(isset($dataType) && $dataType->default_search_key == $tbl['field']) selected @endif
                                        >{{ $tbl['field'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row clearfix">
                                @if (isset($scopes) && isset($dataType))
                                    <div class="col-md-3 form-group">
                                        <label for="scope">{{ __('voyager::bread.scope') }}</label>
                                        <select name="scope" class="select2 form-control">
                                            <option value="">-- {{ __('voyager::generic.none') }} --</option>
                                            @foreach($scopes as $scope)
                                            <option value="{{ $scope }}"
                                                    @if($dataType->scope == $scope) selected @endif
                                            >{{ $scope }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                                <div class="col-md-9 form-group">
                                    <label for="description">{{ __('voyager::bread.description') }}</label>
                                    <textarea class="form-control"
                                              name="description"
                                              placeholder="{{ __('voyager::bread.description') }}"
                                    >{{ $dataType->description ?? '' }}</textarea>
                                </div>
                            </div>
                        </div><!-- .panel-body -->
                    </div><!-- .panel -->


                    <div class="panel panel-primary panel-bordered">
                        <div class="panel-heading">
                            <h3 class="panel-title panel-icon"><i class="voyager-window-list"></i> {{ __('voyager::bread.edit_rows', ['table' => $table]) }}:</h3>
                            <div class="panel-actions">
                                <a class="panel-action voyager-angle-up" data-toggle="panel-collapse" aria-hidden="true"></a>
                            </div>
                        </div>

                        <div class="panel-body">
                            <div class="row fake-table-hd">
                                <div class="col-xs-2">{{ __('voyager::database.field') }}</div>
                                <div class="col-xs-2">{{ __('voyager::database.visibility') }}</div>
                                <div class="col-xs-2">{{ __('voyager::database.input_type') }}</div>
                                <div class="col-xs-2">{{ __('voyager::bread.display_name') }}</div>
                                <div class="col-xs-4">{{ __('voyager::database.optional_details') }}</div>
                            </div>

                            <div id="bread-items">
                            @php
                                $r_order = 0;
                            @endphp
                            @foreach($fieldOptions as $data)
                                @php
                                    $r_order += 1;
                                @endphp

                                @if(isset($dataType->id))
                                    <?php $dataRow = Voyager::model('DataRow')->where('data_type_id', '=', $dataType->id)->where('field', '=', $data['field'])->first(); ?>
                                @endif

                                <div class="row row-dd">
                                    <div class="col-xs-2">
                                        <h4><strong>{{ $data['field'] }}</strong></h4>
                                        <strong>{{ __('voyager::database.type') }}:</strong> <span>{{ $data['type'] }}</span><br/>
                                        <strong>{{ __('voyager::database.key') }}:</strong> <span>{{ $data['key'] }}</span><br/>
                                        <strong>{{ __('voyager::generic.required') }}:</strong>
                                        @if($data['null'] == "NO")
                                            <span>{{ __('voyager::generic.yes') }}</span>
                                            <input type="hidden" value="1" name="field_required_{{ $data['field'] }}" checked="checked">
                                        @else
                                            <span>{{ __('voyager::generic.no') }}</span>
                                            <input type="hidden" value="0" name="field_required_{{ $data['field'] }}">
                                        @endif
                                        <div class="handler voyager-handle"></div>
                                        <input class="row_order" type="hidden" value="{{ $dataRow->order ?? $r_order }}" name="field_order_{{ $data['field'] }}">
                                    </div>
                                    <div class="col-xs-2">
                                        <input type="checkbox"
                                               id="field_browse_{{ $data['field'] }}"
                                               name="field_browse_{{ $data['field'] }}"
                                               @if(isset($dataRow->browse) && $dataRow->browse)
                                                   checked="checked"
                                               @elseif($data['key'] == 'PRI')
                                               @elseif($data['type'] == 'timestamp' && $data['field'] == 'updated_at')
                                               @elseif(!isset($dataRow->browse))
                                                   checked="checked"
                                               @endif>
                                        <label for="field_browse_{{ $data['field'] }}">{{ __('voyager::generic.browse') }}</label><br/>
                                        <input type="checkbox"
                                               id="field_read_{{ $data['field'] }}"
                                               name="field_read_{{ $data['field'] }}" @if(isset($dataRow->read) && $dataRow->read) checked="checked" @elseif($data['key'] == 'PRI')@elseif($data['type'] == 'timestamp' && $data['field'] == 'updated_at')@elseif(!isset($dataRow->read)) checked="checked" @endif>
                                        <label for="field_read_{{ $data['field'] }}">{{ __('voyager::generic.read') }}</label><br/>
                                        <input type="checkbox"
                                               id="field_edit_{{ $data['field'] }}"
                                               name="field_edit_{{ $data['field'] }}" @if(isset($dataRow->edit) && $dataRow->edit) checked="checked" @elseif($data['key'] == 'PRI')@elseif($data['type'] == 'timestamp' && $data['field'] == 'updated_at')@elseif(!isset($dataRow->edit)) checked="checked" @endif>
                                        <label for="field_edit_{{ $data['field'] }}">{{ __('voyager::generic.edit') }}</label><br/>
                                        <input type="checkbox"
                                               id="field_add_{{ $data['field'] }}"
                                               name="field_add_{{ $data['field'] }}" @if(isset($dataRow->add) && $dataRow->add) checked="checked" @elseif($data['key'] == 'PRI')@elseif($data['type'] == 'timestamp' && $data['field'] == 'created_at')@elseif($data['type'] == 'timestamp' && $data['field'] == 'updated_at')@elseif(!isset($dataRow->add)) checked="checked" @endif>
                                            <label for="field_add_{{ $data['field'] }}">{{ __('voyager::generic.add') }}</label><br/>
                                        <input type="checkbox"
                                               id="field_delete_{{ $data['field'] }}"
                                               name="field_delete_{{ $data['field'] }}" @if(isset($dataRow->delete) && $dataRow->delete) checked="checked" @elseif($data['key'] == 'PRI')@elseif($data['type'] == 'timestamp' && $data['field'] == 'updated_at')@elseif(!isset($dataRow->delete)) checked="checked" @endif>
                                                <label for="field_delete_{{ $data['field'] }}">{{ __('voyager::generic.delete') }}</label><br/>
                                    </div>
                                    <div class="col-xs-2">
                                        <input type="hidden" name="field_{{ $data['field'] }}" value="{{ $data['field'] }}">
                                        @if($data['type'] == 'timestamp')
                                            <p>{{ __('voyager::generic.timestamp') }}</p>
                                            <input type="hidden" value="timestamp"
                                                   name="field_input_type_{{ $data['field'] }}">
                                        @else
                                            <select name="field_input_type_{{ $data['field'] }}" class="form-control voyager-select">
                                                @foreach (Voyager::formFields() as $formField)
                                                    @php
                                                    $selected = (isset($dataRow->type) && $formField->getCodename() == $dataRow->type) || (!isset($dataRow->type) && $formField->getCodename() == 'text');
                                                    @endphp
                                                    <option value="{{ $formField->getCodename() }}" {{ $selected ? 'selected' : '' }}>
                                                        {{ $formField->getName() }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @endif
                                    </div>
                                    <div class="col-xs-2">
                                        @if($isModelTranslatable)
                                            @include('voyager::multilingual.input-hidden', [
                                                'isModelTranslatable' => true,
                                                '_field_name'         => 'field_display_name_' . $data['field'],
                                                '_field_trans' => $dataRow ? get_field_translations($dataRow, 'display_name') : json_encode([config('voyager.multilingual.default') => ucwords(str_replace('_', ' ', $data['field']))]),
                                            ])
                                        @endif
                                        <input type="text" class="form-control"
                                               value="{{ $dataRow->display_name ?? ucwords(str_replace('_', ' ', $data['field'])) }}"
                                               name="field_display_name_{{ $data['field'] }}">
                                    </div>
                                    <div class="col-xs-4">
                                        <div class="alert alert-danger validation-error">
                                            {{ __('voyager::json.invalid') }}
                                        </div>
                                        <textarea id="json-input-{{ $data['field'] }}"
                                                  class="resizable-editor"
                                                  data-editor="json"
                                                  name="field_details_{{ $data['field'] }}">
                                            {{ json_encode(isset($dataRow->details) ? $dataRow->details : new class{}) }}
                                        </textarea>
                                    </div>
                                </div>



                            @endforeach

                            @if(isset($dataTypeRelationships))
                                @foreach($dataTypeRelationships as $relationship)
                                    @include('voyager::tools.bread.relationship-partial', $relationship)
                                @endforeach
                            @endif

                            </div>

                        </div><!-- .panel-body -->
                        <div class="panel-footer">
                             <div class="btn btn-new-relationship"><i class="voyager-heart"></i> <span>
                             {{ __('voyager::database.relationship.create') }}</span></div>
                        </div>
                    </div><!-- .panel -->

                    <button type="submit" class="btn pull-right btn-primary">{{ __('voyager::generic.submit') }}</button>

                </form>
            </div><!-- .col-md-12 -->
        </div><!-- .row -->
    </div><!-- .page-content -->

@include('voyager::tools.bread.relationship-new-modal')

@stop

@include('voyager::partials.editors-assets')

@section('javascript')
    <script>
        window.invalidEditors = [];
        let validationAlerts = [];

        document.addEventListener('DOMContentLoaded', function () {
            validationAlerts = Array.from(document.querySelectorAll('.validation-error'));
            hideValidationAlerts();

            @if ($isModelTranslatable)
                if (typeof window.VoyagerInitMultilingual === 'function') {
                    window.VoyagerInitMultilingual(document.querySelectorAll('.side-body'), {
                        form: 'form',
                        editing: true
                    });
                }
            @endif

            initBreadItemsSortable();

            // Init Ace editors when editors bundle is ready
            window.whenEditorsReady(function() {
                initBreadAceEditors();
            });

            initRelationshipControls();

            if (typeof window.VoyagerInitToggles === 'function') {
                window.VoyagerInitToggles();
            }
            if (typeof window.VoyagerInitTooltips === 'function') {
                window.VoyagerInitTooltips(document.querySelectorAll('[data-toggle="tooltip"]'));
            }
        });

        function hideValidationAlerts() {
            validationAlerts.forEach(function (alert) {
                alert.style.display = 'none';
            });
        }

        function showValidationForTextarea(textarea, visible) {
            if (!textarea) {
                return;
            }
            const container = textarea.parentElement;
            const validationError = container ? container.querySelector('.validation-error') : null;
            if (validationError) {
                validationError.style.display = visible ? 'block' : 'none';
            }
        }

        function initBreadAceEditors() {
            document.querySelectorAll('textarea[data-editor]').forEach(function (textarea) {
                const mode = textarea.dataset.editor || 'json';
                const editDiv = document.createElement('div');
                textarea.parentNode.insertBefore(editDiv, textarea);
                const editor = ace.edit(editDiv);
                const session = editor.getSession();
                let isValid = false;
                textarea.style.display = 'none';

                session.on('changeAnnotation', function () {
                    isValid = session.getAnnotations().length === 0;
                    const textareaId = textarea.id;
                    if (!textareaId) {
                        return;
                    }
                    if (!isValid && window.invalidEditors.indexOf(textareaId) === -1) {
                        window.invalidEditors.push(textareaId);
                    } else if (isValid) {
                        window.invalidEditors = window.invalidEditors.filter(function (id) {
                            return id !== textareaId;
                        });
                    }
                });

                editor.on('focus', function () {
                    // Worker disabled globally to avoid CORS errors
                });
                editor.on('blur', function () {
                    if (isValid) {
                        showValidationForTextarea(textarea, false);
                    } else {
                        showValidationForTextarea(textarea, true);
                    }
                });

                session.setUseWorker(false);
                editor.setAutoScrollEditorIntoView(true);
                editor.$blockScrolling = Infinity;
                editor.setOption('maxLines', 30);
                editor.setOption('minLines', 4);
                editor.setOption('showLineNumbers', false);
                editor.setTheme('ace/theme/github');
                session.setMode('ace/mode/json');
                if (textarea.value) {
                    try {
                        session.setValue(JSON.stringify(JSON.parse(textarea.value), null, 4));
                    } catch (error) {
                        console.warn('Voyager BREAD editor: failed to parse textarea JSON', error);
                        session.setValue(textarea.value);
                    }
                }
                session.setMode('ace/mode/' + mode);

                const form = textarea.closest('form');
                if (!form) {
                    return;
                }
                form.addEventListener('submit', function (event) {
                    if (window.invalidEditors.length) {
                        event.preventDefault();
                        event.stopPropagation();
                        hideValidationAlerts();
                        window.invalidEditors.forEach(function (id) {
                            const invalidTextarea = document.getElementById(id);
                            showValidationForTextarea(invalidTextarea, true);
                        });
                        toastr.error('{{ __('voyager::json.invalid_message') }}', '{{ __('voyager::json.validation_errors') }}', {
                            preventDuplicates: true,
                            preventOpenDuplicates: true
                        });
                        return;
                    }
                    const value = session.getValue();
                    if (value) {
                        try {
                            textarea.value = JSON.stringify(JSON.parse(value));
                        } catch (error) {
                            textarea.value = value;
                        }
                    } else {
                        textarea.value = '';
                    }
                });
            });
        }

        function initBreadItemsSortable() {
            const container = document.getElementById('bread-items');
            if (!container || typeof window.Sortable === 'undefined') {
                return;
            }
            container.style.userSelect = 'none';
            container.setAttribute('unselectable', 'on');

            window.Sortable.create(container, {
                handle: '.handler',
                animation: 150,
                ghostClass: 'bread-sortable-ghost',
                dragClass: 'bread-sortable-drag',
                onEnd: function () {
                    updateRowOrders();
                }
            });

            sortBreadItems();
            updateRowOrders();
        }

        function updateRowOrders() {
            document.querySelectorAll('#bread-items .row_order').forEach(function (input, index) {
                input.value = index + 1;
            });
        }

        function sortBreadItems() {
            const container = document.getElementById('bread-items');
            if (!container) {
                return;
            }
            const items = Array.from(container.querySelectorAll('div.row.row-dd'));
            items.sort(function (a, b) {
                const aInput = a.querySelector('.row_order');
                const bInput = b.querySelector('.row_order');
                const aValue = parseInt(aInput ? aInput.value : '0', 10);
                const bValue = parseInt(bInput ? bInput.value : '0', 10);
                return aValue > bValue ? 1 : -1;
            });
            items.forEach(function (item) {
                container.appendChild(item);
            });
        }

        function initRelationshipControls() {
            document.querySelectorAll('.relationship_type').forEach(function (select) {
                select.addEventListener('change', function () {
                    handleRelationshipTypeChange(select);
                });
                handleRelationshipTypeChange(select);
            });

            document.querySelectorAll('.btn-new-relationship').forEach(function (button) {
                button.addEventListener('click', function (event) {
                    event.preventDefault();
                    document.querySelectorAll('#new_relationship_modal .relationship_table').forEach(function (dropdown) {
                        dropdown.dispatchEvent(new Event('change', { bubbles: true }));
                    });
                    showModalById('new_relationship_modal');
                });
            });

            document.querySelectorAll('.relationship_table').forEach(function (dropdown) {
                dropdown.addEventListener('change', function () {
                    populateRowsFromTable(dropdown);
                    updateRelationshipTableLabel(dropdown);
                });
                updateRelationshipTableLabel(dropdown);
            });

            document.querySelectorAll('.relationship_display_name').forEach(function (input) {
                input.addEventListener('input', function () {
                    updateRelationshipDisplayName(input);
                });
                updateRelationshipDisplayName(input);
            });

            document.querySelectorAll('.voyager-relationship-details-btn').forEach(function (button) {
                button.addEventListener('click', function (event) {
                    event.preventDefault();
                    button.classList.toggle('open');
                    const wrapper = button.parentElement ? button.parentElement.parentElement : null;
                    const details = wrapper ? wrapper.querySelector('.voyager-relationship-details') : null;
                    if (details) {
                        details.style.display = button.classList.contains('open') ? 'block' : 'none';
                    }
                    if (button.classList.contains('open')) {
                        const dropdown = wrapper ? wrapper.querySelector('select.relationship_table') : null;
                        if (dropdown) {
                            populateRowsFromTable(dropdown);
                        }
                    }
                });
            });
        }

        function handleRelationshipTypeChange(select) {
            const fieldWrapper = select.closest('.voyager-relationship-details') || select.closest('.modal-body');
            if (!fieldWrapper) {
                return;
            }
            fieldWrapper.querySelectorAll('.belongsToManyShow, .belongsToShow, .hasOneShow, .hasManyShow').forEach(function (section) {
                section.style.display = 'none';
            });
            const target = fieldWrapper.querySelector('.' + select.value + 'Show');
            if (target) {
                target.style.display = '';
            }
            const hasOneSelect = fieldWrapper.querySelector('.hasOneShow select');
            const belongsToSelect = fieldWrapper.querySelector('.belongsToShow select');
            if (hasOneSelect) {
                hasOneSelect.disabled = true;
            }
            if (belongsToSelect) {
                belongsToSelect.disabled = false;
            }

            const relationshipField = fieldWrapper.querySelector('.relationshipField');
            const relationshipPivot = fieldWrapper.querySelector('.relationshipPivot');
            const relationshipTaggable = fieldWrapper.querySelector('.relationship_taggable');
            const hasOneMany = fieldWrapper.querySelector('.hasOneMany');
            const belongsTo = fieldWrapper.querySelector('.belongsTo');

            if (select.value === 'belongsTo') {
                if (relationshipField) relationshipField.style.display = '';
                if (relationshipPivot) relationshipPivot.style.display = 'none';
                if (relationshipTaggable) relationshipTaggable.style.display = 'none';
                if (hasOneMany) hasOneMany.classList.remove('flexed');
                if (belongsTo) belongsTo.classList.add('flexed');
            } else if (select.value === 'hasOne' || select.value === 'hasMany') {
                if (relationshipField) relationshipField.style.display = '';
                if (relationshipPivot) relationshipPivot.style.display = 'none';
                if (relationshipTaggable) relationshipTaggable.style.display = 'none';
                if (hasOneMany) hasOneMany.classList.add('flexed');
                if (belongsTo) belongsTo.classList.remove('flexed');
                if (hasOneSelect) hasOneSelect.disabled = false;
                if (belongsToSelect) belongsToSelect.disabled = true;
            } else {
                if (relationshipField) relationshipField.style.display = 'none';
                if (relationshipPivot) relationshipPivot.style.display = 'flex';
                if (relationshipTaggable) relationshipTaggable.style.display = '';
            }
        }

        function showModalById(id) {
            const modal = document.getElementById(id);
            if (modal && window.VoyagerBootstrapCompat) {
                window.VoyagerBootstrapCompat.showModal(modal);
            }
        }

        function populateRowsFromTable(dropdown) {
            if (!dropdown || !dropdown.value) {
                return;
            }
            const tbl = dropdown.value;
            const container = dropdown.closest('.voyager-relationship-details') || dropdown.closest('.modal-body');
            if (!container) {
                return;
            }
            const endpoint = '{{ route('voyager.database.index') }}/' + encodeURIComponent(tbl);
            fetch(endpoint, { headers: { 'Accept': 'application/json' } })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Failed to fetch table columns');
                    }
                    return response.json();
                })
                .then(function (data) {
                    const options = [];
                    for (const key in data) {
                        if (Object.prototype.hasOwnProperty.call(data, key)) {
                            const col = data[key];
                            // If row has 'field', use it. Otherwise use the key (e.g. for additional attributes or if key-based)
                            const value = (col && col.field) ? col.field : key;
                            options.push({ id: value, text: value });
                        }
                    }
                    container.querySelectorAll('.rowDrop').forEach(function (select) {
                        const selectedValue = select.dataset.selected || '';
                        if (window.VoyagerSelectSetOptions) {
                            window.VoyagerSelectSetOptions(select, options, selectedValue);
                            if (selectedValue) {
                                select.dispatchEvent(new Event('change', { bubbles: true }));
                            }
                        } else {
                            select.innerHTML = '';
                            options.forEach(function (option) {
                                const optionEl = document.createElement('option');
                                optionEl.value = option.id;
                                optionEl.text = option.text;
                                select.appendChild(optionEl);
                            });
                            select.value = selectedValue || (select.options[0] ? select.options[0].value : '');
                            select.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    });
                })
                .catch(function (error) {
                    console.error('Voyager BREAD: populateRowsFromTable failed', error);
                });
        }

        function updateRelationshipDisplayName(input) {
            const wrapper = input.closest('.row-dd-relationship');
            const label = wrapper ? wrapper.querySelector('h4 strong') : null;
            if (label) {
                label.textContent = input.value || '';
            }
        }

        function updateRelationshipTableLabel(dropdown) {
            const wrapper = dropdown.closest('.voyager-relationship-details') || dropdown.closest('.modal-body');
            const label = wrapper ? wrapper.querySelector('.label_table_name') : null;
            if (label) {
                const selectedOption = dropdown.options[dropdown.selectedIndex];
                label.textContent = selectedOption ? selectedOption.text : '';
            }
        }
        /********** End Relationship Functionality **********/
    </script>
@stop
