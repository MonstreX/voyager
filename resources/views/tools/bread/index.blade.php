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
                                <a href="#"
                                     class="btn btn-danger btn-sm"
                                     data-confirm-target="#delete_bread_modal"
                                     data-confirm-form="#delete_bread_form"
                                     data-confirm-form-action="{{ route('voyager.bread.delete', $table->dataTypeId) }}"
                                     data-confirm-name="{{ $table->name }}">
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
    @include('voyager::components.modal-confirm', [
        'id' => 'delete_bread_modal',
        'title' => __('voyager::bread.delete_bread_quest', ['table' => '<span class="confirm_delete_name"></span>']),
        'message' => '',
        'confirmText' => __('voyager::bread.delete_bread_conf'),
        'confirmClass' => 'btn-danger',
        'icon' => 'voyager-trash',
    ])
    <form action="#" id="delete_bread_form" method="POST" style="display:none">
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
        $voyagerToolsBreadIndexConfig = [
            'i18n' => [
                'internalError' => __('voyager::generic.internal_error'),
            ],
        ];
    @endphp

    <script type="application/json" id="voyager-tools-bread-index-config">@json($voyagerToolsBreadIndexConfig)</script>

@stop
