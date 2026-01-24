@php
    $edit = !is_null($dataTypeContent->getKey());
    $add  = is_null($dataTypeContent->getKey());
    $stickyPanelConfig = config('voyager.bread.sticky_action_panel', []);
    $stickyPanelEnabled = (bool) ($stickyPanelConfig['enabled'] ?? false);
    $stickyPanelAutohide = (bool) ($stickyPanelConfig['autohide'] ?? false);

    // Init Tabs Subsystem
    $dataTypeRows = $dataType->{(isset($dataTypeContent->id) ? 'editRows' : 'addRows' )};
    $tabs = [];
    $tabs[] = __('voyager::generic.general'); // Default tab
    foreach($dataTypeRows as $row) {
        if(isset($row->details->tab_title) && !in_array($row->details->tab_title, $tabs)) {
            $tabs[] = $row->details->tab_title;
        }
    }
@endphp

@extends('voyager::master')

@section('css')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@stop

@section('page_title', __('voyager::generic.'.($edit ? 'edit' : 'add')).' '.$dataType->getTranslatedAttribute('display_name_singular'))

@section('page_header')
    <h1 class="page-title">
        <i class="{{ $dataType->icon }}"></i>
        {{ __('voyager::generic.'.($edit ? 'edit' : 'add')).' '.$dataType->getTranslatedAttribute('display_name_singular') }}
    </h1>
    @include('voyager::multilingual.language-selector')
@stop

@section('content')
    <div class="page-content edit-add container-fluid">
        <div class="row">
            <div class="col-md-12">

                <div class="panel panel-bordered">
                    <!-- form start -->
                    <form role="form"
                            id="form-edit-add"
                            class="form-edit-add"
                            data-edit="{{ $edit ? 'true' : 'false' }}"
                            data-url="{{ url()->current() }}"
                            data-url-create="{{ route('voyager.'.$dataType->slug.'.create') }}"
                            action="{{ $edit ? route('voyager.'.$dataType->slug.'.update', $dataTypeContent->getKey()) : route('voyager.'.$dataType->slug.'.store') }}"
                            method="POST" enctype="multipart/form-data">
                        <!-- PUT Method if we are editing -->
                        @if($edit)
                            {{ method_field("PUT") }}
                        @endif

                        <!-- CSRF TOKEN -->
                        {{ csrf_field() }}

                        <input id="redirect-to" type="hidden" name="redirect_to" value="">
                        <input type="hidden" name="model_name" value="{{ $dataType->model_name }}">
                        <input type="hidden" name="model_id" value="{{ optional($dataTypeContent)->id }}">

                        <div class="panel-body">

                            @if (count($errors) > 0)
                                <div class="alert alert-danger">
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <!-- Adding / Editing -->
                            @php
                                $dataTypeRows = $dataType->{($edit ? 'editRows' : 'addRows' )};
                            @endphp

                            @if(count($tabs) > 1)
                                <ul class="nav nav-tabs bread-nav-tabs">
                                    @foreach($tabs as $key => $tab)
                                        <li @if($key == 0) class="active" @endif>
                                            <a data-toggle="tab" href="#{{ 'tab-id-'.\Illuminate\Support\Str::slug($tab) }}">{{$tab}}</a>
                                        </li>
                                    @endforeach
                                </ul>
                                <div class="tab-content bread-tab-content">
                            @else
                                <div class="row">
                            @endif

                            @foreach($dataTypeRows as $row)
                                @if(count($tabs) > 1)
                                    @if($loop->first)
                                        <div id="{{ 'tab-id-'.\Illuminate\Support\Str::slug($tabs[0]) }}" class="tab-pane active">
                                            <div class="row">
                                        @php $cur_tab = $tabs[0]; @endphp
                                    @elseif(isset($row->details->tab_title) && $row->details->tab_title !== $cur_tab)
                                            </div>
                                        </div>
                                        <div id="{{ 'tab-id-'.\Illuminate\Support\Str::slug($row->details->tab_title) }}" class="tab-pane">
                                            <div class="row">
                                        @php $cur_tab = $row->details->tab_title; @endphp
                                    @endif
                                @endif

                                <!-- GET THE DISPLAY OPTIONS -->
                                @php
                                    $display_options = $row->details->display ?? NULL;
                                    if ($dataTypeContent->{$row->field.'_'.($edit ? 'edit' : 'add')}) {
                                        $dataTypeContent->{$row->field} = $dataTypeContent->{$row->field.'_'.($edit ? 'edit' : 'add')};
                                    }
                                @endphp
                                @if (isset($row->details->legend) && isset($row->details->legend->text))
                                    <legend class="text-{{ $row->details->legend->align ?? 'center' }}" style="background-color: {{ $row->details->legend->bgcolor ?? '#f0f0f0' }};padding: 5px;">{{ $row->details->legend->text }}</legend>
                                @endif

                                <div class="form-group @if($row->type == 'hidden') hidden @endif col-md-{{ $display_options->width ?? 12 }} {{ $errors->has($row->field) ? 'has-error' : '' }}" @if(isset($display_options->id)){{ "id=$display_options->id" }}@endif>
                                    {{ $row->slugify }}
                                    <label class="control-label" for="name">{{ $row->getTranslatedAttribute('display_name') }}</label>
                                    @include('voyager::multilingual.input-hidden-bread-edit-add')
                                    @if ($add && isset($row->details->view_add))
                                        @include($row->details->view_add, ['row' => $row, 'dataType' => $dataType, 'dataTypeContent' => $dataTypeContent, 'content' => $dataTypeContent->{$row->field}, 'view' => 'add', 'options' => $row->details])
                                    @elseif ($edit && isset($row->details->view_edit))
                                        @include($row->details->view_edit, ['row' => $row, 'dataType' => $dataType, 'dataTypeContent' => $dataTypeContent, 'content' => $dataTypeContent->{$row->field}, 'view' => 'edit', 'options' => $row->details])
                                    @elseif (isset($row->details->view))
                                        @include($row->details->view, ['row' => $row, 'dataType' => $dataType, 'dataTypeContent' => $dataTypeContent, 'content' => $dataTypeContent->{$row->field}, 'action' => ($edit ? 'edit' : 'add'), 'view' => ($edit ? 'edit' : 'add'), 'options' => $row->details])
                                    @elseif ($row->type == 'relationship')
                                        @include('voyager::formfields.relationship', ['options' => $row->details])
                                    @else
                                        {!! app('voyager')->formField($row, $dataType, $dataTypeContent) !!}
                                    @endif

                                    @foreach (app('voyager')->afterFormFields($row, $dataType, $dataTypeContent) as $after)
                                        {!! $after->handle($row, $dataType, $dataTypeContent) !!}
                                    @endforeach
                                    @if ($errors->has($row->field))
                                        @foreach ($errors->get($row->field) as $error)
                                            <span class="help-block">{{ $error }}</span>
                                        @endforeach
                                    @endif
                                </div>
                            @endforeach

                            @if(count($tabs) > 1)
                                    </div> <!-- .row -->
                                </div> <!-- .tab-pane -->
                                </div> <!-- .tab-content -->
                            @else
                                </div> <!-- .row -->
                            @endif

                        </div><!-- panel-body -->

                        @section('submit-buttons')
                            <button type="submit" class="btn btn-primary save">{{ __('voyager::generic.save') }}</button>
                        @stop

                        @if(!$stickyPanelEnabled)
                            <div class="panel-footer">
                                @yield('submit-buttons')
                            </div>
                        @else
                            <div class="float-action-panel float-action-edit{{ $stickyPanelAutohide ? '' : ' locked' }}" data-autohide="{{ $stickyPanelAutohide ? 'true' : 'false' }}">
                                @yield('submit-buttons')
                                @if ($edit)
                                    <button type="button" class="btn btn-success btn-save-and-continue">{{ __('voyager::generic.save_and_continue') }}</button>
                                @endif
                                <button type="button" class="btn btn-warning btn-save-and-create">{{ __('voyager::generic.save_and_create') }}</button>
                            </div>
                        @endif
                    </form>

                    <div style="display:none">
                        <input type="hidden" id="upload_url" value="{{ route('voyager.upload') }}">
                        <input type="hidden" id="upload_type_slug" value="{{ $dataType->slug }}">
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('voyager::components.modal-confirm', [
        'id' => 'confirm_delete_modal',
        'title' => __('voyager::generic.are_you_sure'),
        'message' => __('voyager::generic.are_you_sure_delete').' \'<span class="confirm_delete_name"></span>\'',
        'confirmText' => __('voyager::generic.delete_confirm'),
        'confirmClass' => 'btn-danger',
        'icon' => 'voyager-warning'
    ])
    <!-- End Delete File Modal -->
@stop

@section('javascript')
    @php
        $editAddConfig = [
            'slug' => $dataType->slug,
            'isModelTranslatable' => (bool) $isModelTranslatable,
            'mediaRemoveUrl' => route('voyager.'.$dataType->slug.'.media.remove'),
            'mediaReorderUrl' => route('voyager.'.$dataType->slug.'.media.reorder'),
            'mediaCropUrl' => route('voyager.media.crop'),
            'slugifySelector' => '.side-body input[data-slug-origin]',
        ];
    @endphp
    <script type="application/json" id="voyager-edit-add-config">@json($editAddConfig)</script>
@stop
