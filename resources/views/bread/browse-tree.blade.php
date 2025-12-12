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
    <div class="modal modal-danger fade" tabindex="-1" id="delete_modal" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('voyager::generic.close') }}"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="voyager-trash"></i> {{ __('voyager::generic.delete_question') }} {{ strtolower($dataType->getTranslatedAttribute('display_name_singular')) }}?</h4>
                </div>
                <div class="modal-footer">
                    <form action="#" id="delete_form" method="POST">
                        {{ method_field('DELETE') }}
                        {{ csrf_field() }}
                        <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('voyager::generic.cancel') }}</button>
                        <input type="submit" class="btn btn-danger delete-confirm" value="{{ __('voyager::generic.delete_confirm') }}">
                    </form>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->

    {{-- Clone record modal --}}
    <div class="modal modal-warning fade" tabindex="-1" id="clone_modal" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('voyager::generic.close') }}"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="voyager-documentation"></i> {{ __('voyager::generic.clone_confirm') }}</h4>
                </div>
                <div class="modal-footer">
                    <form action="#" id="clone_form" method="POST">
                        {{ csrf_field() }}
                        <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('voyager::generic.cancel') }}</button>
                        <input type="submit" class="btn btn-warning clone-confirm" value="{{ __('voyager::generic.yes_please') }}">
                    </form>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
@stop

@section('javascript')
<script>
document.addEventListener('DOMContentLoaded', () => {
    @if ($isModelTranslatable)
        if (window.VoyagerInitMultilingual) {
            window.VoyagerInitMultilingual('.side-body');
        }
    @endif

    // Init Nestable
    const nestableContainer = document.querySelector('.dd');
    if (nestableContainer && window.VoyagerInitNestable) {
        window.VoyagerInitNestable(nestableContainer, {
            handle: '.dd-tree-handle'
        });

        // Our 'nestable.js' component emits 'voyager.sortable.updated'
        nestableContainer.addEventListener('voyager.sortable.updated', (e) => {
            const structure = e.detail && e.detail.structure
                ? e.detail.structure
                : (window.VoyagerSerializeNestable ? window.VoyagerSerializeNestable(nestableContainer) : []);

            const params = new URLSearchParams();
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

    // Collapse/Expand Logic
    const nestableList = document.querySelector('.dd');
    if (nestableList) {
        nestableList.addEventListener('click', (e) => {
            const target = e.target;
            if (target.tagName === 'BUTTON') {
                const action = target.getAttribute('data-action');
                const li = target.closest('.dd-item');
                if (action === 'collapse') {
                    li.classList.add('dd-collapsed');
                    target.style.display = 'none';
                    const expandBtn = li.querySelector('[data-action="expand"]');
                    if (expandBtn) expandBtn.style.display = 'block';
                }
                if (action === 'expand') {
                    li.classList.remove('dd-collapsed');
                    target.style.display = 'none';
                    const collapseBtn = li.querySelector('[data-action="collapse"]');
                    if (collapseBtn) collapseBtn.style.display = 'block';
                }
            }
        });
    }

    // Delete Modal Logic
    let deleteFormAction;
    const deleteModal = document.getElementById('delete_modal');
    const deleteForm = document.getElementById('delete_form');
    const cloneModal = document.getElementById('clone_modal');
    const cloneForm = document.getElementById('clone_form');

    // Vanilla JS delegation for .delete buttons
    document.addEventListener('click', (e) => {
        const target = e.target.closest('.delete');
        if (target && document.querySelector('.dd').contains(target)) {
            e.preventDefault();
            deleteFormAction = target.getAttribute('data-action') || target.getAttribute('href');

            // If the button is a link (href), use that as action, otherwise construct standard route
            if (!deleteFormAction || deleteFormAction === 'javascript:;') {
                const id = target.getAttribute('data-id');
                // Default Voyager route: /admin/slug/id
                deleteFormAction = '{{ route('voyager.'.$dataType->slug.'.destroy', ['id' => '__id']) }}'.replace('__id', id);
            }

            deleteForm.action = deleteFormAction;

            // Show modal using Voyager's bootstrap compatibility or standard jQuery
            if (window.Voyager && window.Voyager.bootstrap && window.Voyager.bootstrap.showModal) {
                window.Voyager.bootstrap.showModal(deleteModal);
            } else {
                // Vanilla fallback if no helpers
                deleteModal.classList.add('in');
                deleteModal.style.display = 'block';
                const backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade in';
                document.body.appendChild(backdrop);
            }
        }
    });

    // Vanilla JS delegation for .clone buttons
    document.addEventListener('click', (e) => {
        const cloneBtn = e.target.closest('.clone');
        if (cloneBtn && document.querySelector('.dd').contains(cloneBtn)) {
            e.preventDefault();
            const id = cloneBtn.dataset.id;
            if (id) {
                const cloneUrl = '{{ route("voyager.".$dataType->slug.".clone", ["id" => "__id"]) }}'.replace('__id', id);
                cloneForm.setAttribute('action', cloneUrl);
            }

            // Show modal using Voyager's bootstrap compatibility or standard jQuery
            if (window.Voyager && window.Voyager.bootstrap && window.Voyager.bootstrap.showModal) {
                window.Voyager.bootstrap.showModal(cloneModal);
            } else {
                // Vanilla fallback if no helpers
                cloneModal.classList.add('in');
                cloneModal.style.display = 'block';
                const backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade in';
                document.body.appendChild(backdrop);
            }
        }
    });

    // Inline Status Toggle Logic
    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('voyager-status-toggle')) {
            const toggle = e.target;
            const id = toggle.dataset.id;
            const field = toggle.dataset.field;
            const slug = toggle.dataset.slug;
            const currentValue = parseInt(toggle.dataset.value);
            const newValue = currentValue ? 0 : 1;

            const updateUrl = '{{ route("voyager.".$dataType->slug.".update-field", ["id" => "__id"]) }}'.replace('__id', id);

            const params = new URLSearchParams();
            params.append('field', field);
            params.append('value', newValue);
            params.append('slug', slug);
            params.append('_token', '{{ csrf_token() }}');

            fetch(updateUrl, {
                method: 'POST',
                body: params,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    toastr.success(data.message);
                    toggle.dataset.value = newValue;
                    if (newValue) {
                        toggle.classList.remove('inactive');
                        toggle.classList.add('active');
                    } else {
                        toggle.classList.remove('active');
                        toggle.classList.add('inactive');
                    }
                } else {
                    toastr.error(data.message || "Error updating field");
                }
            })
            .catch(error => {
                console.error('Error:', error);
                toastr.error("Error updating field");
            });
        }
    });
});
</script>
@stop
