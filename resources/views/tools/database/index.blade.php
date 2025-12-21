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
                                <a href="#"
                                     class="btn-sm btn-danger delete"
                                     data-confirm-target="#delete_bread_modal"
                                     data-confirm-form="#delete_bread_form"
                                     data-confirm-form-action="{{ route('voyager.bread.delete', $table->dataTypeId) }}"
                                     data-confirm-name="{{ $table->name }}">
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
                            <a href="#"
                               class="btn btn-danger btn-sm pull-right delete_table @if($table->dataTypeId) remove-bread-warning @endif"
                               data-table="{{ $table->prefix.$table->name }}"
                               @if(!$table->dataTypeId)
                                   data-confirm-target="#delete_modal"
                                   data-confirm-form="#delete_table_form"
                                   data-confirm-form-action="{{ route('voyager.database.destroy', $table->prefix.$table->name) }}"
                                   data-confirm-name="{{ $table->prefix.$table->name }}"
                               @endif>
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
    @include('voyager::components.modal-confirm', [
        'id' => 'delete_bread_modal',
        'title' => __('voyager::bread.delete_bread_quest', ['table' => '<span class="confirm_delete_name"></span>']),
        'message' => '',
        'confirmText' => __('voyager::bread.delete_bread_conf'),
        'confirmClass' => 'btn-danger',
        'icon' => 'voyager-trash'
    ])
    <form action="#" id="delete_bread_form" method="POST" style="display:none">
        {{ method_field('DELETE') }}
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
    </form>

    @include('voyager::components.modal-confirm', [
        'id' => 'delete_modal',
        'title' => __('voyager::database.delete_table_question', ['table' => '<span class="confirm_delete_name"></span>']),
        'message' => '',
        'confirmText' => __('voyager::database.delete_table_confirm'),
        'confirmClass' => 'btn-danger',
        'icon' => 'voyager-trash'
    ])
    <form action="#" id="delete_table_form" method="POST" style="display:none">
        {{ method_field('DELETE') }}
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
    </form>

    <div class="modal modal-info fade" tabindex="-1" id="table_info" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('voyager::generic.close') }}"><span
                                aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="voyager-data"></i> <span id="table_info_title"></span></h4>
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
                        <tbody id="table_info_rows"></tbody>
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
    @php
        $voyagerToolsDatabaseIndexConfig = [
            'i18n' => [
                'internalError' => __('voyager::generic.internal_error'),
                'deleteBreadBeforeTable' => __('voyager::database.delete_bread_before_table'),
            ],
        ];
    @endphp

    <script type="application/json" id="voyager-tools-database-index-config">@json($voyagerToolsDatabaseIndexConfig)</script>

@stop
