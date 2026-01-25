<ol class="dd-list">

@foreach ($items as $item)

    @php
        $statusValue = array_key_exists('status', $item->getAttributes()) ? (int) $item->status : 1;
        $isEnabled = $statusValue !== 0;
    @endphp
    <li class="dd-item{{ $isEnabled ? '' : ' unpublished-record' }}" data-id="{{ $item->id }}">
        @if(!$item->children->isEmpty())
            <button data-action="collapse" type="button">Collapse</button>
            <button data-action="expand" type="button" style="display: none;">Expand</button>
        @endif

        <div class="dd-tree-handle">
            <div class="dd-tree-move">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>

        <div class="dd-handle">
            <div class="dd-content-holder">
                <div class="dd-content-main dd-menu-content">
                    @if($options->isModelTranslatable)
                        @include('voyager::multilingual.input-hidden', [
                            'isModelTranslatable' => true,
                            '_field_name'         => 'title'.$item->id,
                            '_field_trans'        => json_encode($item->getTranslationsOf('title'))
                        ])
                    @endif

                    <span class="tree-admin-status tree-status">
                        <span
                            class="voyager-status-toggle {{ $isEnabled ? 'active' : 'inactive' }}"
                            data-id="{{ $item->id }}"
                            data-value="{{ $isEnabled ? 1 : 0 }}"
                            title="{{ __('voyager::menu_builder.status') }}"
                        ></span>
                    </span>

                    <span class="dd-menu-title">{{ $item->title }}</span>
                    <small class="dd-menu-url url">{{ $item->link() }}</small>
                </div>

                <div class="dd-content-actions">
                    <div class="item_actions bread-actions">
                        <a href="javascript:;" class="btn btn-sm btn-primary edit"
                            data-id="{{ $item->id }}"
                            data-title="{{ $item->title }}"
                            data-key="{{ $item->key }}"
                            data-url="{{ $item->url }}"
                            data-target="{{ $item->target }}"
                            data-icon_class="{{ $item->icon_class }}"
                            data-color="{{ $item->color }}"
                            data-route="{{ $item->route }}"
                            data-parameters="{{ json_encode($item->parameters) }}"
                            data-status="{{ $isEnabled ? 1 : 0 }}"
                            title="{{ __('voyager::generic.edit') }}"
                        >
                            <i class="voyager-edit"></i>
                        </a>
                        <a
                            href="#"
                            class="btn btn-sm btn-danger pull-right delete"
                            data-confirm-target="#delete_modal"
                            data-confirm-form="#delete_form"
                            data-confirm-form-action="{{ route('voyager.menus.item.destroy', ['menu' => $item->menu_id, 'id' => $item->id]) }}"
                            data-confirm-name="{{ $item->title }}"
                            title="{{ __('voyager::generic.delete') }}"
                        >
                            <i class="voyager-trash"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @if(!$item->children->isEmpty())
            @include('voyager::menu.admin', ['items' => $item->children])
        @endif
    </li>

@endforeach

</ol>
