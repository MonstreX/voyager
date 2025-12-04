@php
    $coords = $data->getCoordinates();
@endphp
@if(count($coords) > 0)
    <img src="https://maps.googleapis.com/maps/api/staticmap?zoom={{ config('voyager.googlemaps.zoom') }}&size=400x100&maptype=roadmap&markers=color:red%7C{{ $coords[0]['lat'] }},{{ $coords[0]['lng'] }}&center={{ $coords[0]['lat'] }},{{ $coords[0]['lng'] }}&key={{ config('voyager.googlemaps.key') }}"/>
@endif