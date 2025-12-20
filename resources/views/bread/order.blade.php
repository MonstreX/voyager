@extends('voyager::master')

@section('page_title', $dataType->getTranslatedAttribute('display_name_plural') . ' ' . __('voyager::bread.order'))

@section('page_header')
<h1 class="page-title">
    <i class="voyager-list"></i>{{ $dataType->getTranslatedAttribute('display_name_plural') }} {{ __('voyager::bread.order') }}
</h1>
@stop

@section('content')
<div class="page-content container-fluid bread-order">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-bordered">
                <div class="panel-heading">
                    <p class="panel-title" style="color:#777">{{ __('voyager::generic.drag_drop_info') }}</p>
                </div>

                <div class="panel-body" style="padding:30px;">
                    <div class="dd" data-voyager-bread-order-root>
                        <ol class="dd-list">
                            @foreach ($results as $result)
                            <li class="dd-item" data-id="{{ $result->getKey() }}">
                                <div class="dd-tree-handle">
                                    <div class="dd-tree-move">
                                        <span></span>
                                        <span></span>
                                        <span></span>
                                    </div>
                                </div>

                                <div class="dd-handle">
                                    @if (isset($dataRow->details->view_order))
                                        @include($dataRow->details->view_order, ['row' => $dataRow, 'dataType' => $dataType, 'dataTypeContent' => $result, 'view' => 'order', 'content' => $result->{$display_column}])
                                    @elseif (isset($dataRow->details->view))
                                        @include($dataRow->details->view, ['row' => $dataRow, 'dataType' => $dataType, 'dataTypeContent' => $result, 'content' => $result->{$display_column}, 'action' => 'order'])
                                    @elseif($dataRow->type == 'image')
                                        <span>
                                            <img src="@if( !filter_var($result->{$display_column}, FILTER_VALIDATE_URL)){{ Voyager::image( $result->{$display_column} ) }}@else{{ $result->{$display_column} }}@endif" style="height:100px">
                                        </span>
                                    @else
                                        <span>{{ $result->{$display_column} }}</span>
                                    @endif
                                </div>
                            </li>
                            @endforeach
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@stop

@section('javascript')
@php
    $breadOrderConfig = [
        'orderUrl' => route('voyager.'.$dataType->slug.'.order'),
        'i18n' => [
            'updatedOrder' => __('voyager::bread.updated_order'),
            'internalError' => __('voyager::generic.internal_error'),
        ],
    ];
@endphp

<script type="application/json" id="voyager-bread-order-config">@json($breadOrderConfig)</script>
@stop
