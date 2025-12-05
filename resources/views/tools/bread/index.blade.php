@extends('voyager::master')

@section('page_title', __('voyager::generic.viewing').' '.__('voyager::generic.bread'))

@section('page_header')
    <h1 class="page-title">
        <i class="voyager-bread"></i> {{ __('voyager::generic.bread') }}
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
                            <th style="text-align:right">{{ __('voyager::bread.bread_crud_actions') }}</th>
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
                                <i class="voyager-data"
                                   style="font-size:25px; position:absolute; margin-left:10px; margin-top:-3px;"></i>
                            </p>
                        </td>

                        <td class="actions text-right">
                            @if($table->dataTypeId)
                                <a href="{{ route('voyager.' . $table->slug . '.index') }}"
                                   class="btn btn-warning btn-sm browse_bread" style="margin-right: 0;">
                                    <i class="voyager-plus"></i> {{ __('voyager::generic.browse') }}
                                </a>
                                <a href="{{ route('voyager.bread.edit', $table->name) }}"
                                   class="btn btn-primary btn-sm edit">
                                    <i class="voyager-edit"></i> {{ __('voyager::generic.edit') }}
                                </a>
                                <a href="#delete-bread" data-id="{{ $table->dataTypeId }}" data-name="{{ $table->name }}"
                                     class="btn btn-danger btn-sm delete">
                                    <i class="voyager-trash"></i> {{ __('voyager::generic.delete') }}
                                </a>
                            @else
                                <a href="{{ route('voyager.bread.create', $table->name) }}"
                                   class="_btn btn-default btn-sm pull-right">
                                    <i class="voyager-plus"></i> {{ __('voyager::bread.add_bread') }}
                                </a>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </table>
            </div>
        </div>
    </div>
    {{-- Delete BREAD Modal --}}
    <div class="modal modal-danger fade" tabindex="-1" id="delete_builder_modal" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('voyager::generic.close') }}"><span
                                aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="voyager-trash"></i>  {!! __('voyager::bread.delete_bread_quest', ['table' => '<span id="delete_builder_name"></span>']) !!}</h4>
                </div>
                <div class="modal-footer">
                    <form action="#" id="delete_builder_form" method="POST">
                        {{ method_field('DELETE') }}
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <input type="submit" class="btn btn-danger" value="{{ __('voyager::bread.delete_bread_conf') }}">
                    </form>
                    <button type="button" class="btn btn-outline pull-right" data-dismiss="modal">{{ __('voyager::generic.cancel') }}</button>
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

        window.whenVueReady(function() {
            window.createVueApp({
                data() {
                    return {
                        table: table,
                    };
                },
            }).mount('#table_info');
        });

        document.addEventListener('DOMContentLoaded', function () {
            const bootstrapCompat = window.VoyagerBootstrapCompat;
            const deleteModal = document.getElementById('delete_builder_modal');
            const deleteForm = document.getElementById('delete_builder_form');
            const deleteTitle = document.getElementById('delete_builder_name');
            const deleteActionTemplate = '{{ route('voyager.bread.delete', ['__id']) }}';
            const tableInfoModal = document.getElementById('table_info');

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

            document.querySelectorAll('table .actions .delete').forEach((button) => {
                button.addEventListener('click', function (event) {
                    event.preventDefault();
                    const id = button.dataset.id || '';
                    const name = button.dataset.name || '';
                    if (deleteTitle) {
                        deleteTitle.textContent = name;
                    }
                    if (deleteForm) {
                        deleteForm.action = deleteActionTemplate.replace('__id', id);
                    }
                    showModal(deleteModal);
                });
            });

            document.querySelectorAll('.database-tables .desctable').forEach((link) => {
                link.addEventListener('click', function (event) {
                    event.preventDefault();
                    const href = link.getAttribute('href');
                    if (!href) {
                        return;
                    }
                    table.name = link.dataset.name || '';
                    table.rows = [];
                    fetch(href, {
                        headers: {
                            'Accept': 'application/json',
                        },
                    })
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
        });
    </script>

@stop
