@section('page_title', __('voyager::generic.viewing').' '.$dataType->getTranslatedAttribute('display_name_plural'))

@section('page_header')
    <div class="container-fluid">
        <h1 class="page-title">
            <i class="{{ $dataType->icon }}"></i> {{ $dataType->getTranslatedAttribute('display_name_plural') }}
        </h1>
        @can('add', app($dataType->model_name))
            <a href="{{ route('voyager.'.$dataType->slug.'.create') }}" class="btn btn-success btn-add-new">
                <i class="voyager-plus"></i> <span>{{ __('voyager::generic.add_new') }}</span>
            </a>
        @endcan
        @can('delete', app($dataType->model_name))
            @include('voyager::partials.bulk-delete')
        @endcan
        @can('edit', app($dataType->model_name))
            @if(!empty($dataType->order_column) && !empty($dataType->order_display_column))
                <a href="{{ route('voyager.'.$dataType->slug.'.order') }}" class="btn btn-primary btn-add-new">
                    <i class="voyager-list"></i> <span>{{ __('voyager::bread.order') }}</span>
                </a>
            @endif
        @endcan
        @can('delete', app($dataType->model_name))
            @if($usesSoftDeletes)
                <input type="checkbox" @if ($showSoftDeleted) checked @endif id="show_soft_deletes" data-toggle="toggle" data-on="{{ __('voyager::bread.soft_deletes_off') }}" data-off="{{ __('voyager::bread.soft_deletes_on') }}">
            @endif
        @endcan
        @foreach($actions as $action)
            @if (method_exists($action, 'massAction'))
                @include('voyager::bread.partials.actions', ['action' => $action, 'data' => null])
            @endif
        @endforeach
        @include('voyager::multilingual.language-selector')
    </div>
@stop

@php
    // Set edit rights
    $canEdit = isset($dataTypeContent[0])? request()->user()->can('edit', $dataTypeContent[0]) : false;

    // Sort columns by browse_order (per-field option)
    $dataType->browseRows = $dataType->browseRows->sortBy(function ($row, $key) {
        return isset($row->details->browse_order) ? $row->details->browse_order : PHP_INT_MAX;
    });
@endphp
@section('content')
    <div class="page-content browse container-fluid">
        @include('voyager::alerts')
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered">
                    <div class="panel-body">
                        @if ($isServerSide)
                            <form method="get" class="form-search">
                                <div id="search-input">
                                    <div class="col-1 no-padding">
                                        <select id="search_key" class="form-control" name="key">
                                            @foreach($searchNames as $key => $name)
                                                <option value="{{ $key }}" @if($search->key == $key || (empty($search->key) && $key == $defaultSearchKey)) selected @endif>{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-1 no-padding">
                                        <select id="filter" class="form-control" name="filter">
                                            <option value="contains" @if($search->filter == "contains") selected @endif>{{ __('voyager::generic.contains') }}</option>
                                            <option value="equals" @if($search->filter == "equals") selected @endif>=</option>
                                        </select>
                                    </div>
                                    <div class="input-group col-md-10 no-padding">
                                        <input type="text" class="form-control" placeholder="{{ __('voyager::generic.search') }}" name="s" value="{{ $search->value }}">
                                        <span class="input-group-btn">
                                            <button class="btn btn-info btn-lg" type="submit">
                                                <i class="voyager-search"></i>
                                            </button>
                                        </span>
                                    </div>
                                </div>
                                @if (Request::has('sort_order') && Request::has('order_by'))
                                    <input type="hidden" name="sort_order" value="{{ Request::get('sort_order') }}">
                                    <input type="hidden" name="order_by" value="{{ Request::get('order_by') }}">
                                @endif
                            </form>
                        @endif
                        @php
                            $simpleTableConfig = [
                                'perPage' => config('voyager.dashboard.data_tables.per_page', 25),
                                'searchPlaceholder' => __('voyager::generic.search'),
                                'order' => count($orderColumn) ? $orderColumn[0] : null,
                            ];
                        @endphp
                        <div class="table-responsive">
                            <table
                                id="dataTable"
                                class="table table-hover"
                                @unless($dataType->server_side)
                                    data-simple-table='@json($simpleTableConfig, JSON_HEX_APOS | JSON_HEX_QUOT)'
                                @endunless
                            >
                                <thead>
                                    <tr>
                                        @if($showCheckboxColumn)
                                            <th class="dt-not-orderable">
                                                <input type="checkbox" class="select_all">
                                            </th>
                                        @endif
                                        @foreach($dataType->browseRows as $row)
                                        <th class="@if(isset($row->details->browse_align)){{ $row->details->browse_align }}@endif"
                                            @if(isset($row->details->browse_width)) style="width:{{ $row->details->browse_width }}"@endif>
                                            @if ($isServerSide && in_array($row->field, $sortableColumns))
                                                <a href="{{ $row->sortByUrl($orderBy, $sortOrder) }}">
                                            @endif
                                            @if(isset($row->details->browse_title))
                                                {{ $row->details->browse_title }}
                                            @else
                                                {{ $row->getTranslatedAttribute('display_name') }}
                                            @endif
                                            @if ($isServerSide)
                                                @if ($row->isCurrentSortField($orderBy))
                                                    @if ($sortOrder == 'asc')
                                                        <i class="voyager-angle-up pull-right"></i>
                                                    @else
                                                        <i class="voyager-angle-down pull-right"></i>
                                                    @endif
                                                @endif
                                                @if ($isServerSide && in_array($row->field, $sortableColumns))
                                                    </a>
                                                @endif
                                            @endif
                                        </th>
                                        @endforeach
                                        <th class="actions text-right dt-not-orderable">{{ __('voyager::generic.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($dataTypeContent as $data)
                                    <tr data-record-id="{{$data->getKey()}}"
                                        data-slug="{{$dataType->slug}}"
                                        class="{{ isset($data->status) && (int)$data->status === 0? 'unpublished-record' : '' }} @if($dataType->server_side){{ $loop->index % 2 === 0? 'odd' : 'even' }}@endif">
                                        @if($showCheckboxColumn)
                                            <td>
                                                <input type="checkbox" name="row_id" id="checkbox_{{ $data->getKey() }}" value="{{ $data->getKey() }}">
                                            </td>
                                        @endif
                                        @foreach($dataType->browseRows as $row)
                                            @php
                                            if ($data->{$row->field.'_browse'}) {
                                                $data->{$row->field} = $data->{$row->field.'_browse'};
                                            }
                                            @endphp
                                            <td class="field-{{ $row->type  }} @if(isset($row->details->browse_align)){{ $row->details->browse_align }}@endif"
                                                @if(isset($row->details->browse_font_size)) style="font-size:{{ $row->details->browse_font_size }}"@endif>
                                                @if (isset($row->details->view_browse))
                                                    @include($row->details->view_browse, ['row' => $row, 'dataType' => $dataType, 'dataTypeContent' => $dataTypeContent, 'content' => $data->{$row->field}, 'view' => 'browse', 'options' => $row->details])
                                                @elseif (isset($row->details->view))
                                                    @include($row->details->view, ['row' => $row, 'dataType' => $dataType, 'dataTypeContent' => $dataTypeContent, 'content' => $data->{$row->field}, 'action' => 'browse', 'view' => 'browse', 'options' => $row->details])
                                                @elseif($row->type == 'image')
                                                    @php
                                                        $imageStyle = 'width:100px';
                                                        if ($row->details && property_exists($row->details, 'browse_image_max_height') && $row->details->browse_image_max_height) {
                                                            $imageStyle = 'width:auto;max-height:' . $row->details->browse_image_max_height;
                                                        }
                                                    @endphp
                                                    <img src="@if( !filter_var($data->{$row->field}, FILTER_VALIDATE_URL)){{ Voyager::image( $data->{$row->field} ) }}@else{{ $data->{$row->field} }}@endif" style="{{ $imageStyle }}">
                                                @elseif($row->type == 'relationship')
                                                    @include('voyager::formfields.relationship', ['view' => 'browse','options' => $row->details])
                                                @elseif($row->type == 'select_multiple')
                                                    @if(property_exists($row->details, 'relationship'))

                                                        @foreach($data->{$row->field} as $item)
                                                            {{ $item->{$row->field} }}
                                                        @endforeach

                                                    @elseif(property_exists($row->details, 'options'))
                                                        @php
                                                            $fieldValue = $data->{$row->field};
                                                            if (!is_array($fieldValue)) {
                                                                $decoded = is_string($fieldValue) ? json_decode($fieldValue, true) : null;
                                                                if (is_array($decoded)) {
                                                                    $fieldValue = $decoded;
                                                                }
                                                            }
                                                            if (!is_array($fieldValue)) {
                                                                $fieldValue = array_filter([$fieldValue], static function ($value) {
                                                                    return $value !== null && $value !== '';
                                                                });
                                                            }
                                                        @endphp
                                                        @if (!empty($fieldValue))
                                                            @foreach($fieldValue as $item)
                                                                @if (@$row->details->options->{$item})
                                                                    {{ $row->details->options->{$item} . (!$loop->last ? ', ' : '') }}
                                                                @endif
                                                            @endforeach
                                                        @else
                                                            {{ __('voyager::generic.none') }}
                                                        @endif
                                                    @endif

                                                    @elseif($row->type == 'multiple_checkbox' && property_exists($row->details, 'options'))
                                                        @php
                                                            $fieldValue = $data->{$row->field};
                                                            if (!is_array($fieldValue)) {
                                                                $decoded = is_string($fieldValue) ? json_decode($fieldValue, true) : null;
                                                                if (is_array($decoded)) {
                                                                    $fieldValue = $decoded;
                                                                }
                                                            }
                                                            if (!is_array($fieldValue)) {
                                                                $fieldValue = array_filter([$fieldValue], static function ($value) {
                                                                    return $value !== null && $value !== '';
                                                                });
                                                            }
                                                        @endphp
                                                        @if (!empty($fieldValue))
                                                            @foreach($fieldValue as $item)
                                                                @if (@$row->details->options->{$item})
                                                                    {{ $row->details->options->{$item} . (!$loop->last ? ', ' : '') }}
                                                                @endif
                                                            @endforeach
                                                        @else
                                                            {{ __('voyager::generic.none') }}
                                                        @endif

                                                @elseif(($row->type == 'select_dropdown' || $row->type == 'radio_btn') && property_exists($row->details, 'options'))

                                                    {!! $row->details->options->{$data->{$row->field}} ?? '' !!}

                                                @elseif($row->type == 'date' || $row->type == 'timestamp')
                                                    @if ( property_exists($row->details, 'format') && !is_null($data->{$row->field}) )
                                                        {{ \Carbon\Carbon::parse($data->{$row->field})->formatLocalized($row->details->format) }}
                                                    @else
                                                        {{ $data->{$row->field} }}
                                                    @endif
                                                {{-- FIELD CHECKBOX TYPE --}}
                                                @elseif($row->type == 'checkbox')
                                                    @if(property_exists($row->details, 'on') && property_exists($row->details, 'off'))
                                                        @if(property_exists($row->details, 'browse_inline_checkbox'))
                                                            <div 
                                                                class="voyager-status-toggle {{ ($data->{$row->field}) ? 'active' : 'inactive' }}"
                                                                data-id="{{ $data->getKey() }}"
                                                                data-field="{{ $row->field }}"
                                                                data-value="{{ ($data->{$row->field}) ? 1 : 0 }}"
                                                                data-slug="{{ $dataType->slug }}"
                                                             ></div>
                                                        @else
                                                            @if($data->{$row->field})
                                                                <span class="label label-info">{{ $row->details->on }}</span>
                                                            @else
                                                                <span class="label label-primary">{{ $row->details->off }}</span>
                                                            @endif
                                                        @endif
                                                    @else
                                                    {{ $data->{$row->field} }}
                                                    @endif
                                                @elseif($row->type == 'color')
                                                    <span class="badge badge-lg" style="background-color: {{ $data->{$row->field} }}">{{ $data->{$row->field} }}</span>
                                                @elseif($row->type == 'text' || $row->type == 'number')
                                                    @include('voyager::multilingual.input-hidden-bread-browse')
                                                    <div class="text-field-holder">
                                                        @if(isset($row->details->browse_inline_editor) && $canEdit)
                                                        <div class="browse-inline-editor">
                                                            <input class="browse-inline-input" data-id="{{ $data->getKey() }}" @if($row->type == 'number') type="number" @else type="text" @endif name="{{$row->field}}" value="{{ $data->{$row->field} }}">
                                                            <button class="text-inline-save" type="button" title="{{ __('voyager::generic.save') }}"><i class="voyager-check"></i></button>
                                                            <button class="text-inline-cancel" type="button" title="{{ __('voyager::generic.cancel') }}"><i class="voyager-x"></i></button>
                                                        </div>
                                                        @endif
                                                        <div class="browse-text-holder">
                                                            @if(isset($row->details->url))
                                                                <a href="{{ route('voyager.'.$dataType->slug.'.'.$row->details->url, $data->getKey()) }}">
                                                            @elseif(isset($row->details->route) && isset($row->details->route->name) && isset($row->details->route->param_field))
                                                                <a href="{{ route($row->details->route->name, $data->{$row->details->route->param_field}) }}">
                                                            @endif
                                                                <div>{{ mb_strlen( ($data->{$row->field})?? '' ) > 200 ? mb_substr($data->{$row->field}, 0, 200) . ' ...' : $data->{$row->field} }}</div>
                                                            @if(isset($row->details->url) || (isset($row->details->route) && isset($row->details->route->name) && isset($row->details->route->param_field)))
                                                                </a>
                                                            @endif
                                                            @if(isset($row->details->browse_inline_editor) && $canEdit)
                                                                <button class="text-inline-edit" type="button" title="{{ __('voyager::generic.edit') }}"><i class="voyager-edit"></i></button>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @elseif($row->type == 'text_area')
                                                    @include('voyager::multilingual.input-hidden-bread-browse')
                                                    <div>{{ mb_strlen( $data->{$row->field} ) > 200 ? mb_substr($data->{$row->field}, 0, 200) . ' ...' : $data->{$row->field} }}</div>
                                                @elseif($row->type == 'file' && !empty($data->{$row->field}) )
                                                    @include('voyager::multilingual.input-hidden-bread-browse')
                                                    @if(json_decode($data->{$row->field}) !== null)
                                                        @foreach(json_decode($data->{$row->field}) as $file)
                                                            <a href="{{ Storage::disk(config('voyager.storage.disk'))->url($file->download_link) ?: '' }}" target="_blank">
                                                                {{ $file->original_name ?: '' }}
                                                            </a>
                                                            <br/>
                                                        @endforeach
                                                    @else
                                                        <a href="{{ Storage::disk(config('voyager.storage.disk'))->url($data->{$row->field}) }}" target="_blank">
                                                            {{ __('voyager::generic.download') }}
                                                        </a>
                                                    @endif
                                                @elseif($row->type == 'rich_text_box')
                                                    @include('voyager::multilingual.input-hidden-bread-browse')
                                                    <div>{{ mb_strlen( strip_tags($data->{$row->field}, '<b><i><u>') ) > 200 ? mb_substr(strip_tags($data->{$row->field}, '<b><i><u>'), 0, 200) . ' ...' : strip_tags($data->{$row->field}, '<b><i><u>') }}</div>
                                                @elseif($row->type == 'coordinates')
                                                    @include('voyager::partials.coordinates-static-image')
                                                @elseif($row->type == 'multiple_images')
                                                    @php $images = json_decode($data->{$row->field}); @endphp
                                                    @if($images)
                                                        @php $images = array_slice($images, 0, 3); @endphp
                                                        @foreach($images as $image)
                                                            <img src="@if( !filter_var($image, FILTER_VALIDATE_URL)){{ Voyager::image( $image ) }}@else{{ $image }}@endif" style="width:50px">
                                                        @endforeach
                                                    @endif
                                                @elseif($row->type == 'media_picker')
                                                    @php
                                                        if (is_array($data->{$row->field})) {
                                                            $files = $data->{$row->field};
                                                        } else {
                                                            $files = json_decode($data->{$row->field});
                                                        }
                                                    @endphp
                                                    @if ($files)
                                                        @if (property_exists($row->details, 'show_as_images') && $row->details->show_as_images)
                                                            @foreach (array_slice($files, 0, 3) as $file)
                                                            <img src="@if( !filter_var($file, FILTER_VALIDATE_URL)){{ Voyager::image( $file ) }}@else{{ $file }}@endif" style="width:50px">
                                                            @endforeach
                                                        @else
                                                            <ul>
                                                            @foreach (array_slice($files, 0, 3) as $file)
                                                                <li>{{ $file }}</li>
                                                            @endforeach
                                                            </ul>
                                                        @endif
                                                        @if (count($files) > 3)
                                                            {{ __('voyager::media.files_more', ['count' => (count($files) - 3)]) }}
                                                        @endif
                                                    @elseif (is_array($files) && count($files) == 0)
                                                        {{ trans_choice('voyager::media.files', 0) }}
                                                    @elseif ($data->{$row->field} != '')
                                                        @if (property_exists($row->details, 'show_as_images') && $row->details->show_as_images)
                                                            <img src="@if( !filter_var($data->{$row->field}, FILTER_VALIDATE_URL)){{ Voyager::image( $data->{$row->field} ) }}@else{{ $data->{$row->field} }}@endif" style="width:50px">
                                                        @else
                                                            {{ $data->{$row->field} }}
                                                        @endif
                                                    @else
                                                        {{ trans_choice('voyager::media.files', 0) }}
                                                    @endif
                                                @else
                                                    @include('voyager::multilingual.input-hidden-bread-browse')
                                                    <span>{{ $data->{$row->field} }}</span>
                                                @endif
                                            </td>
                                        @endforeach
                                        <td class="no-sort no-click">
                                            <div class="bread-actions">
                                                @foreach($actions as $action)
                                                    @if (!method_exists($action, 'massAction'))
                                                        @include('voyager::bread.partials.actions', ['action' => $action])
                                                    @endif
                                                @endforeach
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if ($isServerSide)
                            <div class="pull-left">
                                <div role="status" class="show-res" aria-live="polite">{{ trans_choice(
                                    'voyager::generic.showing_entries', $dataTypeContent->total(), [
                                        'from' => $dataTypeContent->firstItem(),
                                        'to' => $dataTypeContent->lastItem(),
                                        'all' => $dataTypeContent->total()
                                    ]) }}</div>
                            </div>
                            <div class="pull-right">
                                {{ $dataTypeContent->appends([
                                    's' => $search->value,
                                    'filter' => $search->filter,
                                    'key' => $search->key,
                                    'order_by' => $orderBy,
                                    'sort_order' => $sortOrder,
                                    'showSoftDeleted' => $showSoftDeleted,
                                ])->links() }}
                            </div>
                        @endif
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

@section('css')

@stop

@section('javascript')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            @if ($dataType->server_side)
                document.querySelectorAll('#search-input select').forEach((select) => {
                    select.dataset.voyagerDisableSearch = 'true';
                    if (window.VoyagerSelectRefresh) {
                        window.VoyagerSelectRefresh(select);
                    }
                });
            @endif

            @if ($isModelTranslatable)
                if (window.VoyagerInitMultilingual) {
                    window.VoyagerInitMultilingual('.side-body');
                }
            @endif

            const selectAllToggle = document.querySelector('.select_all');
            if (selectAllToggle) {
                selectAllToggle.addEventListener('click', (event) => {
                    const checked = event.currentTarget.checked;
                    document.querySelectorAll('input[name="row_id"]').forEach((checkbox) => {
                        checkbox.checked = checked;
                        checkbox.dispatchEvent(new Event('change'));
                    });
                });
            }

            const deleteModal = document.getElementById('delete_modal');
            const deleteForm = document.getElementById('delete_form');
            const deleteActionTemplate = '{{ route("voyager.".$dataType->slug.".destroy", ["id" => "__id"]) }}';
            const bootstrapCompat = window.VoyagerBootstrapCompat;

            const openDeleteModal = (button) => {
                if (!deleteModal || !deleteForm || !deleteActionTemplate) {
                    return;
                }
                const id = button.dataset.id;
                if (id) {
                    deleteForm.setAttribute('action', deleteActionTemplate.replace('__id', id));
                }
                showModal(deleteModal);
            };

            const showModal = (modal) => {
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

            // Clone Record Handler - defined before delete to ensure showModal is accessible
            const cloneModal = document.getElementById('clone_modal');
            const cloneForm = document.getElementById('clone_form');

            const openCloneModal = (button) => {
                if (!cloneModal || !cloneForm) {
                    return;
                }
                const id = button.dataset.id;
                if (id) {
                    const cloneUrl = '{{ route("voyager.".$dataType->slug.".clone", ["id" => "__id"]) }}'.replace('__id', id);
                    cloneForm.setAttribute('action', cloneUrl);
                }
                showModal(cloneModal);
            };

            document.querySelectorAll('td .clone').forEach((button) => {
                button.addEventListener('click', (event) => {
                    event.preventDefault();
                    openCloneModal(event.currentTarget);
                });
            });

            document.querySelectorAll('td .delete').forEach((button) => {
                button.addEventListener('click', (event) => {
                    event.preventDefault();
                    openDeleteModal(event.currentTarget);
                });
            });

            @if($usesSoftDeletes)
                const softDeleteToggle = document.getElementById('show_soft_deletes');
                if (softDeleteToggle) {
                    softDeleteToggle.addEventListener('change', (event) => {
                        const checked = event.currentTarget.checked;
                        const targetUrl = checked
                            ? "{{ route('voyager.'.$dataType->slug.'.index', array_merge($params, ['showSoftDeleted' => 1]), true) }}"
                            : "{{ route('voyager.'.$dataType->slug.'.index', array_merge($params, ['showSoftDeleted' => 0]), true) }}";
                        window.location.href = targetUrl;
                    });
                }
            @endif

            const selectedInput = document.querySelector('.selected_ids');
            if (selectedInput) {
                document.querySelectorAll('input[name="row_id"]').forEach((checkbox) => {
                    checkbox.addEventListener('change', () => {
                        const ids = [];
                        document.querySelectorAll('input[name="row_id"]:checked').forEach((checkedBox) => {
                            ids.push(checkedBox.value);
                        });
                        selectedInput.value = ids.join(',');
                    });
                });
            }

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

            // Inline Edit button
            document.addEventListener('click', (e) => {
                if (e.target.closest('.text-inline-edit')) {
                    const editButton = e.target.closest('.text-inline-edit');
                    const textHolder = editButton.closest('.browse-text-holder');
                    const fieldHolder = editButton.closest('.text-field-holder');
                    const editorHolder = fieldHolder.querySelector('.browse-inline-editor');

                    if (textHolder) textHolder.style.display = 'none';
                    if (editorHolder) {
                        editorHolder.style.display = 'flex';
                        const input = editorHolder.querySelector('.browse-inline-input');
                        if (input) input.focus();
                    }
                }
            });

            // Inline Cancel button
            document.addEventListener('click', (e) => {
                if (e.target.closest('.text-inline-cancel')) {
                    const cancelButton = e.target.closest('.text-inline-cancel');
                    const editorHolder = cancelButton.closest('.browse-inline-editor');
                    const fieldHolder = cancelButton.closest('.text-field-holder');
                    const textHolder = fieldHolder.querySelector('.browse-text-holder');

                    if (editorHolder) editorHolder.style.display = 'none';
                    if (textHolder) textHolder.style.display = 'flex';
                }
            });

            // Inline press Enter
            document.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' && e.target.classList.contains('browse-inline-input')) {
                    e.target.closest('.browse-inline-editor').querySelector('.text-inline-save').click();
                }
            });

            // Inline Save button
            document.addEventListener('click', (e) => {
                if (e.target.closest('.text-inline-save')) {
                    const saveButton = e.target.closest('.text-inline-save');
                    const editorHolder = saveButton.closest('.browse-inline-editor');
                    const input = editorHolder.querySelector('.browse-inline-input');
                    const fieldHolder = editorHolder.closest('.text-field-holder');
                    const textHolder = fieldHolder.querySelector('.browse-text-holder');

                    const parentRow = saveButton.closest('tr');
                    const dataTypeSlug = parentRow ? parentRow.dataset.slug : '';
                    const recordId = input.dataset.id;
                    const fieldName = input.name;
                    const newValue = input.value;

                    // Hide editor, show text holder, update displayed text
                    if (editorHolder) editorHolder.style.display = 'none';
                    if (textHolder) {
                        textHolder.style.display = 'flex';
                        const displayedText = textHolder.querySelector('div');
                        if (displayedText) displayedText.textContent = newValue;
                    }

                    // Send AJAX request
                    const updateUrl = '{{ route("voyager.".$dataType->slug.".update-field", ["id" => "__id"]) }}'.replace('__id', recordId);

                    const params = new URLSearchParams();
                    params.append('field', fieldName);
                    params.append('value', newValue);
                    params.append('slug', dataTypeSlug);
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