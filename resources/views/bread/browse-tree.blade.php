@section('page_title', $dataType->getTranslatedAttribute('display_name_plural') . ' ' . __('voyager::bread.tree_list'))

@section('page_header')
    <div class="container-fluid">
        <h1 class="page-title">
            <i class="voyager-list"></i>{{ $dataType->getTranslatedAttribute('display_name_plural') }}
        </h1>
        @can('add', app($dataType->model_name))
            <a href="{{ route('voyager.'.$dataType->slug.'.create') }}" class="btn btn-success btn-add-new">
                <i class="voyager-plus"></i> <span>{{ __('voyager::generic.add_new') }}</span>
            </a>
        @endcan
        @include('voyager::multilingual.language-selector')
    </div>
@stop

@section('content')
<div class="page-content container-fluid browse-tree">
    @include('voyager::alerts')
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-bordered">
                <div class="panel-heading">
                    <p class="panel-title" style="color:#777">{{ __('voyager::generic.drag_drop_info') }}</p>
                </div>
                <div class="panel-body tree-items-list" style="padding:30px;">
                    <div class="dd">
                        @php
                            $treeItems = [];
                            // Handle Paginator
                            if ($dataTypeContent instanceof \Illuminate\Pagination\LengthAwarePaginator || $dataTypeContent instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator) {
                                $flatArray = $dataTypeContent->items();
                                $flatArray = array_map(function($item) {
                                    return $item->toArray();
                                }, $flatArray);
                            } elseif($dataTypeContent instanceof \Illuminate\Support\Collection) {
                                $flatArray = $dataTypeContent->toArray();
                            } elseif (is_array($dataTypeContent)) {
                                $flatArray = $dataTypeContent;
                            } else {
                                $flatArray = [];
                            }

                            if (count($flatArray) > 0) {
                                $treeItems = voyager_tree_build($flatArray);
                            }
                        @endphp
                        
                        @include('voyager::bread.partials.tree-list', ['items' => $treeItems])
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
    {{-- Single delete modal --}}
    @include('voyager::components.modal-confirm', [
        'id' => 'delete_modal',
        'title' => __('voyager::generic.delete_question').' '.strtolower($dataType->getTranslatedAttribute('display_name_singular')).'?',
        'message' => '',
        'confirmText' => __('voyager::generic.delete_confirm'),
        'confirmClass' => 'btn-danger delete-confirm',
        'icon' => 'voyager-trash'
    ])
    <form action="#" id="delete_form" method="POST" style="display:none">
        {{ method_field('DELETE') }}
        {{ csrf_field() }}
    </form>

    {{-- Clone record modal --}}
    @include('voyager::components.modal-confirm', [
        'id' => 'clone_modal',
        'title' => __('voyager::generic.clone_confirm'),
        'message' => '',
        'confirmText' => __('voyager::generic.yes_please'),
        'confirmClass' => 'btn-warning clone-confirm',
        'icon' => 'voyager-documentation',
        'modalClass' => 'modal-warning'
    ])
    <form action="#" id="clone_form" method="POST" style="display:none">
        {{ csrf_field() }}
    </form>
@stop

@section('javascript')
    @php
        $browseTreeConfig = [
            'slug' => $dataType->slug,
            'isModelTranslatable' => (bool) $isModelTranslatable,
            'treeOrderUrl' => route('voyager.'.$dataType->slug.'.tree-order'),
            'updateFieldUrlTemplate' => route('voyager.'.$dataType->slug.'.update-field', ['id' => '__id']),
        ];
    @endphp
    <script type="application/json" id="voyager-browse-tree-config">@json($browseTreeConfig)</script>
@stop
