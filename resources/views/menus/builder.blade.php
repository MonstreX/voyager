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


    @include('voyager::components.modal-confirm', [
        'id' => 'delete_modal',
        'title' => __('voyager::menu_builder.delete_item_question'),
        'message' => '',
        'confirmText' => __('voyager::menu_builder.delete_item_confirm'),
        'confirmClass' => 'btn-danger delete-confirm',
        'confirmButtonId' => 'delete_confirm_button',
        'icon' => 'voyager-trash'
    ])
    <form action="{{ route('voyager.menus.item.destroy', ['menu' => $menu->id, 'id' => '__id']) }}"
          id="delete_form"
          method="POST"
          style="display:none">
        {{ method_field("DELETE") }}
        {{ csrf_field() }}
    </form>

    @include('voyager::menus.partials.item-modal')




@stop

@section('javascript')
    @php
        $voyagerMenuBuilderConfig = [
            'urls' => [
                'order' => route('voyager.menus.order_item', ['menu' => $menu->id]),
                'statusTemplate' => route('voyager.menus.item.status', ['menu' => $menu->id, 'id' => '__id']),
            ],
            'labels' => [
                'add' => __('voyager::generic.add'),
                'update' => __('voyager::generic.update'),
            ],
            'i18n' => [
                'updatedOrder' => __('voyager::menu_builder.updated_order'),
                'successfullyUpdated' => __('voyager::generic.successfully_updated'),
                'internalError' => __('voyager::generic.internal_error'),
            ],
            'isModelTranslatable' => (bool) $isModelTranslatable,
        ];
    @endphp

    <script type="application/json" id="voyager-menu-builder-config">@json($voyagerMenuBuilderConfig)</script>
@stop
