<ol class="dd-list">
    @foreach($items as $key => $item)

    @php
        $data = $dataTypeContent->filter(function ($record, $key) use($item) {
            return $record->id === $item['id'] ;
        })->first();
    @endphp

    <li data-record-id="{{$item['id']}}" data-slug="{{$dataType->slug}}" class="dd-item @if(isset($item['status']) && $item['status'] === 0) unpublished-record @endif" data-id="{{ $item['id'] }}">

        <div class="dd-handle">
            <div class="dd-content-holder">
                <div class="dd-content-main">
                @foreach($dataType->browseRows as $row)
                    @if($row->browse)
                        @php
                            $display_options = $row->details->display ?? NULL;
                        @endphp
                        {{-- Handle Inline Checkbox --}}
                        @if(isset($row->details->browse_inline_checkbox))
                        <span class="tree-field tree-{{ $row->field }}">
                            <input type="checkbox" data-id="{{ $item['id'] }}" name="{{ $row->field }}" @if($item[$row->field]) checked @endif class="tiny-toggle" data-tt-type="dot" data-tt-size="tiny">
                        </span>
                        @else
                            {{-- Skip parent_id field in display --}}
                            @if($row->field !== 'parent_id')
                                <span class="tree-field tree-{{$row->field}} @if(isset($row->details->browse_tree_push_right)) ml-auto @endif" 
                                      @if(isset($row->details->browse_width)) style="width:{{ $row->details->browse_width }}; flex-shrink: 0;" @endif
                                      @if(isset($row->details->browse_align)) class="{{ $row->details->browse_align }}" @endif>
                                    
                                    @if(isset($row->details->url)) <a href="{{ route('voyager.'.$dataType->slug.'.'.$row->details->url, $item['id']) }}"> @endif
                                    
                                    @if($row->type == 'date' || $row->type == 'timestamp')
                                        @if ( property_exists($row->details, 'format') && !is_null($data->{$row->field}) )
                                            {{ \Carbon\Carbon::parse($data->{$row->field})->formatLocalized($row->details->format) }}
                                        @else
                                            {{ $data->{$row->field} }}
                                        @endif
                                    @elseif($row->type == 'image')
                                         <img src="@if( !filter_var($data->{$row->field}, FILTER_VALIDATE_URL)){{ Voyager::image( $data->{$row->field} ) }}@else{{ $data->{$row->field} }}@endif" style="height: 30px; width:auto">
                                    @else
                                        @include('voyager::multilingual.input-hidden-bread-browse')
                                        <span>{{ mb_strlen( $item[$row->field] ) > 200 ? mb_substr($item[$row->field], 0, 200) . ' ...' : $item[$row->field] }}</span>
                                    @endif

                                    @if(isset($row->details->url)) </a> @endif
                                </span>
                            @endif
                        @endif
                    @endif
                @endforeach
                </div>
                
                <div class="dd-content-actions">
                    <div class="no-sort no-click bread-actions">
                        @foreach($actions as $action)
                            @include('voyager::bread.partials.actions', ['action' => $action])
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        @if(isset($item['children']) && count($item['children']) > 0)
            @include('voyager::bread.partials.tree-list', ['items' => $item['children']])
        @endif
    </li>
    @endforeach
</ol>
