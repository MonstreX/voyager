@extends('voyager::master')

@section('page_title', $dataType->getTranslatedAttribute('display_name_plural') . ' ' . __('voyager::bread.order'))

@section('page_header')
<h1 class="page-title">
    <i class="voyager-list"></i>{{ $dataType->getTranslatedAttribute('display_name_plural') }} {{ __('voyager::bread.order') }}
</h1>
@stop

@section('content')
<div class="page-content container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-bordered">
                <div class="panel-heading">
                    <p class="panel-title" style="color:#777">{{ __('voyager::generic.drag_drop_info') }}</p>
                </div>

                <div class="panel-body" style="padding:30px;">
                    <div class="dd">
                        <ol class="dd-list">
                            @foreach ($results as $result)
                            <li class="dd-item" data-id="{{ $result->getKey() }}">
                                <div class="dd-handle" style="height:inherit">
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

<script>
$(document).ready(function () {
    var breadNestable = document.querySelector('.dd');
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    var orderUrl = '{{ route('voyager.'.$dataType->slug.'.order') }}';

    if (breadNestable && window.VoyagerInitNestable) {
        console.debug('[VoyagerNestable:BreadOrder] init', breadNestable);
        window.VoyagerInitNestable(breadNestable);
        breadNestable.addEventListener('voyager.sortable.updated', function (event) {
            console.debug('[VoyagerNestable:BreadOrder] voyager.sortable.updated', event);
            var structure = event.detail && event.detail.structure
                ? event.detail.structure
                : (window.VoyagerSerializeNestable ? window.VoyagerSerializeNestable(breadNestable) : []);
            console.debug('[VoyagerNestable:BreadOrder] serialize result', structure);

            var payload = new URLSearchParams();
            payload.append('order', JSON.stringify(structure));
            payload.append('_token', csrfToken);

            fetch(orderUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'Accept': 'application/json'
                },
                body: payload.toString()
            })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Order update failed with status ' + response.status);
                }
                console.debug('[VoyagerNestable:BreadOrder] order saved');
                toastr.success("{{ __('voyager::bread.updated_order') }}");
            })
            .catch(function (error) {
                console.error('[VoyagerNestable:BreadOrder] order update failed', error);
                toastr.error("{{ __('voyager::generic.internal_error') }}");
            });
        });
    }
});
</script>
@stop
