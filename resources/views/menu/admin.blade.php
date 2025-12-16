<ol class="dd-list">

@foreach ($items as $item)

    @php
        $statusValue = array_key_exists('status', $item->getAttributes()) ? (int) $item->status : 1;
        $isEnabled = $statusValue !== 0;
    @endphp
    <li class="dd-item{{ $isEnabled ? '' : ' unpublished-record' }}" data-id="{{ $item->id }}">
        <div class="dd-handle dd-menu-item">
            <div class="dd-menu-left">
                @if($options->isModelTranslatable)
                    @include('voyager::multilingual.input-hidden', [
                        'isModelTranslatable' => true,
                        '_field_name'         => 'title'.$item->id,
                        '_field_trans'        => json_encode($item->getTranslationsOf('title'))
                    ])
                @endif
                <span class="tree-admin-status">
                    <span
                        class="voyager-status-toggle {{ $isEnabled ? 'active' : 'inactive' }}"
                        data-id="{{ $item->id }}"
                        data-value="{{ $isEnabled ? 1 : 0 }}"
                        title="{{ __('voyager::menu_builder.status') }}"
                    ></span>
                </span>
                <span>{{ $item->title }}</span> <small class="url">{{ $item->link() }}</small>
            </div>

            <div class="item_actions bread-actions">
                <a href="javascript:;" class="btn btn-sm btn-primary edit"
                     data-id="{{ $item->id }}"
                     data-title="{{ $item->title }}"
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
                <a href="javascript:;" class="btn btn-sm btn-danger pull-right delete" data-id="{{ $item->id }}" title="{{ __('voyager::generic.delete') }}">
                    <i class="voyager-trash"></i>
                </a>
            </div>
        </div>
        @if(!$item->children->isEmpty())
            @include('voyager::menu.admin', ['items' => $item->children])
        @endif
    </li>

@endforeach

</ol>
