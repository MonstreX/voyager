<style>
    #map {
        height: 400px;
        width: 100%;
    }
</style>

<div
    id="map"
    data-voyager-coordinates-map
    data-voyager-googlemaps-key="{{ config('voyager.googlemaps.key') }}"
    data-voyager-coordinates-zoom="{{ config('voyager.googlemaps.zoom') }}"
    data-voyager-coordinates-center-lat="{{ config('voyager.googlemaps.center.lat') }}"
    data-voyager-coordinates-center-lng="{{ config('voyager.googlemaps.center.lng') }}"
    data-voyager-coordinates-points='@json($dataTypeContent->getCoordinates())'
></div>
