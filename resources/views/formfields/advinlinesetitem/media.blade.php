@php
    $mediaIds = $source && isset($source[$key_field]) ? (array) $source[$key_field] : [];
    $mediaIds = array_values(array_filter(array_map('intval', $mediaIds)));
    $mediaItems = collect();
    if (!empty($mediaIds)) {
        $mediaItems = \TCG\Voyager\Models\Media::whereIn('id', $mediaIds)->get()->keyBy('id');
    }
    $accept = isset($field->accept) ? $field->accept : 'image/*';
    $listId = 'list_'.$row_field.'_'.$key_field.'_'.($row_id ?? '%id%');
@endphp

<input
    type="hidden"
    class="adv-inline-set-media-ids"
    name="{{ $row_field }}_{{ $key_field }}_{{ $row_id ?? '%id%' }}_media_ids"
    value="{{ implode(',', $mediaIds) }}"
>
<input
    type="hidden"
    class="adv-inline-set-media-deleted-ids"
    name="{{ $row_field }}_{{ $key_field }}_{{ $row_id ?? '%id%' }}_media_deleted_ids"
    value=""
>

@if(!empty($mediaIds))
    <div id="{{ $listId }}"
         class="adv-inline-set-media-list"
         data-inline-key="{{ $key_field }}"
         data-row-id="{{ $row_id ?? '%id%' }}">
        @foreach($mediaIds as $mediaId)
            @php $media = $mediaItems->get($mediaId); @endphp
            @if($media)
                <div class="adv-inline-set-media-item columns-{{ 12/$columns }}"
                     data-media-id="{{ $media->id }}">
                    <div class="adv-inline-set-media-delete" title="{{ __('voyager::generic.delete') }}">
                        <i class="voyager-x"></i>
                    </div>
                    @if($media->isImage())
                        <img src="{{ $media->cacheBustedFullUrl() }}" alt="">
                    @else
                        @php
                            $extension = strtolower(pathinfo($media->file_name, PATHINFO_EXTENSION));
                            $iconMap = [
                                'pdf' => 'pdf.svg',
                                'doc' => 'doc.svg',
                                'docx' => 'doc.svg',
                                'xls' => 'xls.svg',
                                'xlsx' => 'xls.svg',
                                'zip' => 'zip.svg',
                                'js' => 'javascript.svg',
                                'html' => 'html.svg',
                                'htm' => 'html.svg',
                                'json' => 'json-file.svg',
                                'txt' => 'txt.svg',
                                'xml' => 'xml.svg',
                                'csv' => 'csv.svg',
                            ];
                            $icon = $iconMap[$extension] ?? 'file.svg';
                        @endphp
                        <img class="file-type" src="{{ voyager_asset('icons/files/'.$icon) }}" alt="{{ $extension }}">
                    @endif
                </div>
            @endif
        @endforeach
    </div>
@endif

<input
    id="{{ $row_field }}_{{ $key_field }}_{{ $row_id ?? '%id%' }}"
    class="adv-form-control form-control adv-inline-set-media-upload"
    data-field-type="{{ $field->type }}"
    data-inline-key="{{ $key_field }}"
    name="{{ $row_field }}_{{ $key_field }}_{{ $row_id ?? '%id%' }}[]"
    type="file"
    multiple="multiple"
    accept="{{ $accept }}"
    @include('voyager::formfields.advinlinesetitem.attr')
>
