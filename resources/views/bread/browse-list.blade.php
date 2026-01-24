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
                        @php
                            // Build filters array from BREAD configuration
                            $model_filters = [];
                            foreach($dataType->browseRows as $row) {
                                if(isset($row->details->browse_filter) && $row->details->browse_filter) {
                                    if (isset($row->details->relationship)) {
                                        // Relationship-based filter
                                        $model_filters[] = [
                                            'filter_items' => build_flat_from_tree(flat_to_tree(app($row->details->relationship->model)->get()->toArray())),
                                            'filter_title' => $row->details->relationship->filter_label ?? $row->details->display_name ?? $row->display_name,
                                            'filter_column' => $row->details->relationship->ref_field,
                                            'filter_key' => $row->details->relationship->key,
                                            'filter_label' => $row->details->relationship->label,
                                        ];
                                    }
                                }
                            }
                        @endphp

                        @if(count($model_filters) > 0)
                            <div class="browse-filters-holder" data-url="{{ Request::url() }}">
                                @foreach($model_filters as $key => $filter)
                                    <span class="filter-selector">
                                        <label for="filter-selector-{{ $key }}">{{ $filter['filter_title'] }}:</label>
                                        <select id="filter-selector-{{ $key }}"
                                                name="filter-selector[]"
                                                data-column="{{ $filter['filter_column'] }}"
                                                class="filter-select select2">
                                            <option value="">{{ __('voyager::generic.all') }}</option>

                                            @php
                                                $val = null;
                                                if (isset($filters) && $filters) {
                                                    foreach ($filters['field'] as $idx => $field) {
                                                        if ($field === $filter['filter_column']) {
                                                            $val = $filters['value'][$idx];
                                                        }
                                                    }
                                                }
                                            @endphp

                                            @foreach($filter['filter_items'] as $item)
                                                <option value="{{ $item[$filter['filter_key']] }}"
                                                        @if ($val && $item[$filter['filter_key']] == $val) selected @endif>
                                                    @if(isset($item['level']) && $item['level'] > 0){{ str_repeat("--", $item['level']) }} @endif
                                                    {{ $item[$filter['filter_label']] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </span>
                                @endforeach
                            </div>
                        @endif
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
                                                @elseif($row->type == 'adv_select_dropdown_tree')
                                                    @include('voyager::formfields.adv_select_dropdown_tree', ['view' => 'browse','options' => $row->details])
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
                                                        {{ voyager_format_datetime($data->{$row->field}, $row->details->format) }}
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
                                                @elseif($row->type == 'adv_fields_group')
                                                    @php
                                                        $group = json_decode($data->{$row->field});
                                                        if (!isset($group->fields) && isset($row->details->fields)) {
                                                            $fields = $row->details->fields;
                                                        } else {
                                                            $fields = $group->fields ?? null;
                                                        }
                                                    @endphp
                                                    <div class="browse-group-fields" data-field-name="{{ $row->field }}">
                                                        @if(isset($fields))
                                                            @foreach($fields as $key => $field)
                                                                <span class="browse-group-field" data-key="{{ $key }}" title="{{ $field->label ?? '' }}">
                                                                    @if(!empty($field->value))
                                                                        <i class="voyager-check"></i>
                                                                    @else
                                                                        <i class="voyager-dot"></i>
                                                                    @endif
                                                                </span>
                                                            @endforeach
                                                        @else
                                                            <i class="voyager-dot"></i><i class="voyager-dot"></i><i class="voyager-dot"></i>
                                                        @endif
                                                        @if(property_exists($row->details, 'browse_inline_editor') && $canEdit)
                                                            <button data-name="{{ $row->field }}" data-group-data="{{ json_encode($group) }}" class="group-inline-edit" type="button" title="{{ __('voyager::generic.edit') }}"><i class="voyager-edit"></i></button>
                                                        @endif
                                                    </div>
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

    {{-- Group Fields Inline Edit Modal --}}
    <div class="modal modal-info fade" tabindex="-1" id="group_inline_edit_modal" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('voyager::generic.close') }}"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="voyager-edit"></i> {{ __('voyager::generic.edit') }} Fields</h4>
                </div>
                <div class="modal-body">
                    <form class="inline-group-form"></form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('voyager::generic.cancel') }}</button>
                    <button type="button" class="btn btn-primary group-save-btn">{{ __('voyager::generic.save') }}</button>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
@stop

@section('css')

@stop

@section('javascript')
    @php
        $browseListConfig = [
            'serverSide' => (bool) $dataType->server_side,
            'isModelTranslatable' => (bool) $isModelTranslatable,
            'usesSoftDeletes' => (bool) $usesSoftDeletes,
            'softDeleteUrls' => $usesSoftDeletes ? [
                'on' => route('voyager.'.$dataType->slug.'.index', array_merge($params, ['showSoftDeleted' => 1]), true),
                'off' => route('voyager.'.$dataType->slug.'.index', array_merge($params, ['showSoftDeleted' => 0]), true),
            ] : null,
            'updateFieldUrlTemplate' => route('voyager.'.$dataType->slug.'.update-field', ['id' => '__id']),
        ];
    @endphp
    <script type="application/json" id="voyager-browse-list-config">@json($browseListConfig)</script>
    {{-- Legacy inline JS removed; logic lives in resources/assets/js/pages/bread-browse-list.js --}}
@stop
