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
@stop

@section('javascript')
<script>
document.addEventListener('DOMContentLoaded', function () {
    @if ($isModelTranslatable)
        if (window.VoyagerInitMultilingual) {
            window.VoyagerInitMultilingual('.side-body');
        }
    @endif

    // Init Nestable
    var nestableContainer = document.querySelector('.dd');
    if (nestableContainer && window.VoyagerInitNestable) {
        window.VoyagerInitNestable(nestableContainer);
        
        // Listen for custom event from our wrapper (if it emits one) 
        // or use native Nestable events if available via wrapper
        // Assuming VoyagerInitNestable sets up the plugin.
        // If the wrapper doesn't expose 'change', we might need to rely on DOM mutations or the wrapper implementation.
        
        // Our 'nestable.js' component emits 'voyager.sortable.updated'
        nestableContainer.addEventListener('voyager.sortable.updated', function (e) {
             var structure = e.detail && e.detail.structure
                ? e.detail.structure
                : (window.VoyagerSerializeNestable ? window.VoyagerSerializeNestable(nestableContainer) : []);

             var params = new URLSearchParams();
             params.append('slug', '{{ $dataType->slug }}');
             params.append('order', JSON.stringify(structure));
             params.append('_token', '{{ csrf_token() }}');

             fetch('{{ route('voyager.'.$dataType->slug.'.tree-order') }}', {
                 method: 'POST',
                 body: params,
                 headers: {
                     'Content-Type': 'application/x-www-form-urlencoded',
                     'X-CSRF-TOKEN': '{{ csrf_token() }}',
                     'Accept': 'application/json'
                 }
             })
             .then(response => response.json())
             .then(data => {
                 if (data.status === 'success') {
                     toastr.success(data.message);
                 } else {
                     toastr.error(data.message || "Error updating order");
                 }
             })
             .catch(error => {
                 console.error('Error:', error);
                 toastr.error("Error updating order");
             });
        });
    }
});
</script>
@stop
