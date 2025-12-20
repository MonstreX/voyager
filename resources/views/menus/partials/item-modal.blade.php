<div class="modal modal-info fade" tabindex="-1" id="menu_item_modal" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('voyager::generic.close') }}">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 id="m_hd_add" class="modal-title hidden">
                    <i class="voyager-plus"></i> {{ __('voyager::menu_builder.create_new_item') }}
                </h4>
                <h4 id="m_hd_edit" class="modal-title hidden">
                    <i class="voyager-edit"></i> {{ __('voyager::menu_builder.edit_item') }}
                </h4>
            </div>

            <form action="" id="m_form" method="POST"
                  data-action-add="{{ route('voyager.menus.item.add', ['menu' => $menu->id]) }}"
                  data-action-update="{{ route('voyager.menus.item.update', ['menu' => $menu->id]) }}">
                <input id="m_form_method" type="hidden" name="_method" value="POST">
                {{ csrf_field() }}

                <div class="modal-body">
                    <div class="row">
                        <div class="col-12 form-group">
                            <label for="m_status">{{ __('voyager::menu_builder.status') }}</label><br>
                            <input type="hidden" name="status" value="0">
                            <input
                                id="m_status"
                                type="checkbox"
                                name="status"
                                class="toggleswitch"
                                value="1"
                                checked
                                data-on="{{ __('voyager::menu_builder.status_active') }}"
                                data-off="{{ __('voyager::menu_builder.status_inactive') }}"
                                data-onstyle="primary"
                                data-offstyle="default"
                            >
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div>
                                @include('voyager::multilingual.language-selector')
                                <label for="name">{{ __('voyager::menu_builder.item_title') }}</label>
                                @include('voyager::multilingual.input-hidden', ['_field_name' => 'title', '_field_trans' => ''])
                                <input type="text" class="form-control" id="m_title" name="title" placeholder="{{ __('voyager::generic.title') }}"><br>
                            </div>
                        </div>
                    </div>

                    <label for="type">{{ __('voyager::menu_builder.link_type') }}</label>
                    <select id="m_link_type" class="form-control voyager-select" name="type">
                        <option value="url" selected="selected">{{ __('voyager::menu_builder.static_url') }}</option>
                        <option value="route">{{ __('voyager::menu_builder.dynamic_route') }}</option>
                    </select><br>

                    <div id="m_url_type">
                        <label for="url">{{ __('voyager::menu_builder.url') }}</label>
                        <input type="text" class="form-control" id="m_url" name="url" placeholder="{{ __('voyager::generic.url') }}"><br>
                    </div>

                    <div id="m_route_type">
                        <label for="route">{{ __('voyager::menu_builder.item_route') }}</label>
                        <input type="text" class="form-control" id="m_route" name="route" placeholder="{{ __('voyager::generic.route') }}"><br>
                        <label for="parameters">{{ __('voyager::menu_builder.route_parameter') }}</label>
                        <textarea rows="3" class="form-control" id="m_parameters" name="parameters" placeholder="{{ json_encode(['key' => 'value'], JSON_PRETTY_PRINT) }}"></textarea><br>
                    </div>

                    <label for="icon_class">{{ __('voyager::menu_builder.icon_class') }}
                        <a href="{{ route('voyager.compass.index') }}#fonts" target="_blank">
                            {!! __('voyager::menu_builder.icon_class2') !!}
                        </a>
                    </label>
                    <input type="text" class="form-control" id="m_icon_class" name="icon_class"
                           placeholder="{{ __('voyager::menu_builder.icon_class_ph') }}"><br>

                    <label for="color">{{ __('voyager::menu_builder.color') }}</label>
                    <input type="color" class="form-control" id="m_color" name="color"
                           placeholder="{{ __('voyager::menu_builder.color_ph') }}"><br>

                    <label for="target">{{ __('voyager::menu_builder.open_in') }}</label>
                    <select id="m_target" class="form-control voyager-select" name="target">
                        <option value="_self" selected="selected">{{ __('voyager::menu_builder.open_same') }}</option>
                        <option value="_blank">{{ __('voyager::menu_builder.open_new') }}</option>
                    </select>

                    <input type="hidden" name="menu_id" value="{{ $menu->id }}">
                    <input type="hidden" name="id" id="m_id" value="">
                </div>

                <div class="modal-footer">
                    <input type="submit" class="btn btn-success pull-right" value="{{ __('voyager::generic.update') }}">
                    <button type="button" class="btn btn-default pull-right" data-dismiss="modal">{{ __('voyager::generic.cancel') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

