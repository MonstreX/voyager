@extends('voyager::master')

@section('page_title', __('voyager::generic.viewing').' '.__('voyager::generic.database'))

@section('page_header')
    <h1 class="page-title">
        <i class="voyager-data"></i> {{ __('voyager::generic.database') }}
        <a href="{{ route('voyager.database.create') }}" class="btn btn-success"><i class="voyager-plus"></i>
            {{ __('voyager::database.create_new_table') }}</a>
    </h1>
@stop

@section('content')

    <div class="page-content container-fluid">
        @include('voyager::alerts')
        <div class="row">
            <div class="col-md-12">

                <table class="table table-striped database-tables">
                    <thead>
                        <tr>
                            <th>{{ __('voyager::database.table_name') }}</th>
                            <th style="text-align:right" colspan="2">{{ __('voyager::database.table_actions') }}</th>
                        </tr>
                    </thead>

                @foreach($tables as $table)
                    @continue(in_array($table->name, config('voyager.database.tables.hidden', [])))
                    <tr>
                        <td>
                            <p class="name">
                                <a href="{{ route('voyager.database.show', $table->prefix.$table->name) }}"
                                   data-name="{{ $table->prefix.$table->name }}" class="desctable">
                                   {{ $table->name }}
                                </a>
                            </p>
                        </td>

                        <td>
                            <div class="bread_actions">
                            @if($table->dataTypeId)
                                <a href="{{ route('voyager.' . $table->slug . '.index') }}"
                                   class="btn-sm btn-warning browse_bread">
                                    <i class="voyager-plus"></i> {{ __('voyager::database.browse_bread') }}
                                </a>
                                <a href="{{ route('voyager.bread.edit', $table->name) }}"
                                   class="btn-sm btn-default edit">
                                   {{ __('voyager::bread.edit_bread') }}
                                </a>
                                <a data-id="{{ $table->dataTypeId }}" data-name="{{ $table->name }}"
                                     class="btn-sm btn-danger delete">
                                     {{ __('voyager::bread.delete_bread') }}
                                </a>
                            @else
                                <a href="{{ route('voyager.bread.create', $table->name) }}"
                                   class="btn-sm btn-default">
                                    <i class="voyager-plus"></i> {{ __('voyager::bread.add_bread') }}
                                </a>
                            @endif
                            </div>
                        </td>

                        <td class="actions">
                            <a class="btn btn-danger btn-sm pull-right delete_table @if($table->dataTypeId) remove-bread-warning @endif"
                               data-table="{{ $table->prefix.$table->name }}">
                               <i class="voyager-trash"></i> {{ __('voyager::generic.delete') }}
                            </a>
                            <a href="{{ route('voyager.database.edit', $table->prefix.$table->name) }}"
                               class="btn btn-sm btn-primary pull-right" style="display:inline; margin-right:10px;">
                               <i class="voyager-edit"></i> {{ __('voyager::generic.edit') }}
                            </a>
                            <a href="{{ route('voyager.database.show', $table->prefix.$table->name) }}"
                               data-name="{{ $table->name }}"
                               class="btn btn-sm btn-warning pull-right desctable" style="display:inline; margin-right:10px;">
                               <i class="voyager-eye"></i> {{ __('voyager::generic.view') }}
                            </a>
                        </td>
                    </tr>
                @endforeach
                </table>
            </div>
        </div>
    </div>

    {{-- Delete BREAD Modal --}}
    <div class="modal modal-danger fade" tabindex="-1" id="delete_bread_modal" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('voyager::generic.close') }}"><span
                                aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="voyager-trash"></i>  {!! __('voyager::bread.delete_bread_quest', ['table' => '<span id="delete_bread_name"></span>']) !!}</h4>
                </div>
                <div class="modal-footer">
                    <form action="#" id="delete_bread_form" method="POST">
                        {{ method_field('DELETE') }}
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <input type="submit" class="btn btn-danger" value="{{ __('voyager::bread.delete_bread_conf') }}">
                    </form>
                    <button type="button" class="btn btn-outline pull-right" data-dismiss="modal">{{ __('voyager::generic.cancel') }}</button>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->

    <div class="modal modal-danger fade" tabindex="-1" id="delete_modal" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('voyager::generic.close') }}"><span
                                aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="voyager-trash"></i> {!! __('voyager::database.delete_table_question', ['table' => '<span id="delete_table_name"></span>']) !!}</h4>
                </div>
                <div class="modal-footer">
                    <form action="#" id="delete_table_form" method="POST">
                        {{ method_field('DELETE') }}
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <input type="submit" class="btn btn-danger pull-right" value="{{ __('voyager::database.delete_table_confirm') }}">
                        <button type="button" class="btn btn-outline pull-right" style="margin-right:10px;"
                                data-dismiss="modal">{{ __('voyager::generic.cancel') }}
                        </button>
                    </form>

                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->

    <div class="modal modal-info fade" tabindex="-1" id="table_info" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('voyager::generic.close') }}"><span
                                aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="voyager-data"></i> @{{ table.name }}</h4>
                </div>
                <div class="modal-body" style="overflow:scroll">
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th>{{ __('voyager::database.field') }}</th>
                            <th>{{ __('voyager::database.type') }}</th>
                            <th>{{ __('voyager::database.null') }}</th>
                            <th>{{ __('voyager::database.key') }}</th>
                            <th>{{ __('voyager::database.default') }}</th>
                            <th>{{ __('voyager::database.extra') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="row in table.rows">
                            <td><strong>@{{ row.Field }}</strong></td>
                            <td>@{{ row.Type }}</td>
                            <td>@{{ row.Null }}</td>
                            <td>@{{ row.Key }}</td>
                            <td>@{{ row.Default }}</td>
                            <td>@{{ row.Extra }}</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline pull-right" data-dismiss="modal">{{ __('voyager::generic.close') }}</button>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->

@stop

@section('javascript')

    <script>

        var table = {
            name: '',
            rows: []
        };

        (function bootTableInfoModal() {
            if (!window.Voyager || typeof window.Voyager.withVue !== 'function') {
                return;
            }
            window.Voyager.withVue(function(Vue) {
                const app = Vue.createApp({
                    data: function () {
                        return {
                            table: table,
                        };
                    },
                }).mount('#table_info');
                table = app.table;
            });
        })();

        document.addEventListener('DOMContentLoaded', function () {
            const bootstrapCompat = window.VoyagerBootstrapCompat;
            const tableInfoModal = document.getElementById('table_info');
            const deleteTableModal = document.getElementById('delete_modal');
            const deleteTableForm = document.getElementById('delete_table_form');
            const deleteTableName = document.getElementById('delete_table_name');
            const deleteTableActionTemplate = '{{ route('voyager.database.destroy', ['database' => '__database']) }}';
            const deleteBreadModal = document.getElementById('delete_bread_modal');
            const deleteBreadForm = document.getElementById('delete_bread_form');
            const deleteBreadName = document.getElementById('delete_bread_name');
            const deleteBreadActionTemplate = '{{ route('voyager.bread.delete', '__id') }}';

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

            document.querySelectorAll('.database-tables .desctable').forEach((link) => {
                link.addEventListener('click', function (event) {
                    event.preventDefault();
                    const href = link.getAttribute('href');
                    if (!href) {
                        return;
                    }
                    table.name = link.dataset.name || '';
                    table.rows = [];
                    fetch(href, { headers: { 'Accept': 'application/json' } })
                        .then((response) => {
                            if (!response.ok) {
                                throw new Error('Failed to fetch table info');
                            }
                            return response.json();
                        })
                        .then((data) => {
                            table.rows = Object.keys(data || {}).map((key) => {
                                const val = data[key] || {};
                                return {
                                    Field: val.field,
                                    Type: val.type,
                                    Null: val.null,
                                    Key: val.key,
                                    Default: val.default,
                                    Extra: val.extra,
                                };
                            });
                            showModal(tableInfoModal);
                        })
                        .catch((error) => {
                            console.error('Voyager table info fetch failed', error);
                            toastr.error("{{ __('voyager::generic.internal_error') }}");
                        });
                });
            });

            document.querySelectorAll('td.actions .delete_table').forEach((button) => {
                button.addEventListener('click', function (event) {
                    event.preventDefault();
                    const tableName = button.dataset.table || '';
                    if (button.classList.contains('remove-bread-warning')) {
                        toastr.warning('{{ __('voyager::database.delete_bread_before_table') }}');
                        return;
                    }
                    if (deleteTableName) {
                        deleteTableName.textContent = tableName;
                    }
                    if (deleteTableForm) {
                        deleteTableForm.action = deleteTableActionTemplate.replace('__database', tableName);
                    }
                    showModal(deleteTableModal);
                });
            });

            document.querySelectorAll('table .bread_actions .delete').forEach((button) => {
                button.addEventListener('click', function (event) {
                    event.preventDefault();
                    const id = button.dataset.id || '';
                    const name = button.dataset.name || '';
                    if (deleteBreadName) {
                        deleteBreadName.textContent = name;
                    }
                    if (deleteBreadForm) {
                        deleteBreadForm.action = deleteBreadActionTemplate.replace('__id', id);
                    }
                    showModal(deleteBreadModal);
                });
            });
        });
    </script>

@stop
