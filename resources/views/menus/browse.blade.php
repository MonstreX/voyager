@extends('voyager::master')

@section('page_title', __('voyager::generic.viewing').' '.$dataType->getTranslatedAttribute('display_name_plural'))

@section('page_header')
    <h1 class="page-title">
        <i class="voyager-list-add"></i> {{ $dataType->getTranslatedAttribute('display_name_plural') }}
        @can('add',app($dataType->model_name))
            <a href="{{ route('voyager.'.$dataType->slug.'.create') }}" class="btn btn-success">
                <i class="voyager-plus"></i> {{ __('voyager::generic.add_new') }}
            </a>
        @endcan
    </h1>
@stop

@section('content')
    @include('voyager::menus.partial.notice')

    <div class="page-content container-fluid">
        @include('voyager::alerts')
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered">
                    <div class="panel-body">
                        @php
                            $menuTableConfig = [
                                'perPage' => 15,
                                'searchPlaceholder' => __('voyager::generic.search'),
                                'order' => [0, 'asc'],
                            ];
                        @endphp
                        <table
                            id="dataTable"
                            class="table table-hover"
                            data-simple-table='@json($menuTableConfig, JSON_HEX_APOS | JSON_HEX_QUOT)'
                        >
                            <thead>
                            <tr>
                                @foreach($dataType->browseRows as $row)
                                <th>{{ $row->display_name }}</th>
                                @endforeach
                                <th class="actions text-right">{{ __('voyager::generic.actions') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                                @foreach($dataTypeContent as $data)
                                <tr>
                                    @foreach($dataType->browseRows as $row)
                                    <td>
                                        @if($row->type == 'image')
                                            <img src="@if( strpos($data->{$row->field}, 'http://') === false && strpos($data->{$row->field}, 'https://') === false){{ Voyager::image( $data->{$row->field} ) }}@else{{ $data->{$row->field} }}@endif" style="width:100px">
                                        @else
                                            {{ $data->{$row->field} }}
                                        @endif
                                    </td>
                                    @endforeach
                                    <td class="no-sort no-click">
                                        <div class="bread-actions">
                                            @can('edit', $data)
                                                <a href="{{ route('voyager.'.$dataType->slug.'.builder', $data->{$data->getKeyName()}) }}" class="btn btn-sm btn-success pull-right" title="{{ __('voyager::generic.builder') }}">
                                                    <i class="voyager-list"></i>
                                                </a>
                                            @endcan
                                            @can('edit', $data)
                                                <a href="{{ route('voyager.'.$dataType->slug.'.edit', $data->{$data->getKeyName()}) }}" class="btn btn-sm btn-primary pull-right edit" title="{{ __('voyager::generic.edit') }}">
                                                    <i class="voyager-edit"></i>
                                                </a>
                                            @endcan
                                            @can('delete', $data)
                                                <a href="#"
                                                   class="btn btn-sm btn-danger"
                                                   data-confirm-target="#delete_menu_modal"
                                                   data-confirm-form="#delete_menu_form"
                                                   data-confirm-form-action="{{ route('voyager.'.$dataType->slug.'.destroy', $data->{$data->getKeyName()}) }}"
                                                   title="{{ __('voyager::generic.delete') }}">
                                                    <i class="voyager-trash"></i>
                                                </a>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('voyager::components.modal-confirm', [
        'id' => 'delete_menu_modal',
        'title' => __('voyager::generic.delete_question').' '.$dataType->getTranslatedAttribute('display_name_singular').'?'.'',
        'message' => '',
        'confirmText' => __('voyager::generic.delete_this_confirm').' '.$dataType->getTranslatedAttribute('display_name_singular'),
        'confirmClass' => 'btn-danger',
        'icon' => 'voyager-trash'
    ])
    <form action="#" id="delete_menu_form" method="POST" style="display:none">
        {{ method_field("DELETE") }}
        {{ csrf_field() }}
    </form>
@stop
