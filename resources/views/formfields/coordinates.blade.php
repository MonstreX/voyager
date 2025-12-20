@php
    $showAutocomplete = property_exists($row->details, 'showAutocompleteInput') ? (bool)$row->details->showAutocompleteInput : true;
    $showAutocomplete = $showAutocomplete ? 'true' : 'false';
    $showLatLng = property_exists($row->details, 'showLatLngInput') ? (bool)$row->details->showLatLngInput : true;
    $showLatLng = $showLatLng ? 'true' : 'false';
@endphp

<div id="coordinates-formfield-{{ $row->field }}"
     data-voyager-coordinates-root
     data-voyager-template-id="coordinates-template-{{ $row->field }}"
     @if (property_exists($row->details, 'onChange'))
         data-voyager-on-change="{{ e($row->details->onChange) }}"
     @endif>
    <coordinates
        ref="coordinates"
        api-key="{{ config('voyager.googlemaps.key') }}"
        :points='@json($dataTypeContent->getCoordinates() && count($dataTypeContent->getCoordinates()) ? $dataTypeContent->getCoordinates() : [[ 'lat' => config('voyager.googlemaps.center.lat'), 'lng' => config('voyager.googlemaps.center.lng') ]])'
        :show-autocomplete="{{ $showAutocomplete }}"
        :show-lat-lng="{{ $showLatLng }}"
        :zoom={{ config('voyager.googlemaps.zoom') }}
    ></coordinates>
</div>

<script type="text/x-template" id="coordinates-template-{{ $row->field }}">
    <div>
        <div class="alert alert-warning" v-if="!apiKey">
            <strong>{{ __('voyager::generic.error') }}:</strong> Google Maps API key is missing. Please configure <code>GOOGLE_MAPS_KEY</code> in your .env file.
        </div>
        <div class="form-group row" v-else>
            <div class="col-md-5" v-if="showAutocomplete">
                <label class="control-label">{{ __('voyager::generic.find_by_place') }}</label>
                <input
                    class="form-control"
                    type="text"
                    placeholder="742 Evergreen Terrace"
                    ref="autocompleteInput"
                    v-on:keypress="onInputKeyPress($event)"
                />
            </div>
            <div class="col-md-2" v-if="showLatLng">
                <label class="control-label">{{ __('voyager::generic.latitude') }}</label>
                <input
                    class="form-control"
                    type="number"
                    step="any"
                    name="{{ $row->field }}[lat]"
                    placeholder="19.6400"
                    v-model="lat"
                    @change="onLatLngInputChange"
                    v-on:keypress="onInputKeyPress($event)"
                />
            </div>
            <div class="col-md-2" v-if="showLatLng">
                <label class="control-label">{{ __('voyager::generic.longitude') }}</label>
                <input
                    class="form-control"
                    type="number"
                    step="any"
                    name="{{ $row->field }}[lng]"
                    placeholder="-155.9969"
                    v-model="lng"
                    @change="onLatLngInputChange"
                    v-on:keypress="onInputKeyPress($event)"
                />
            </div>

            <div class="clearfix"></div>
        </div>

        <div class="voyager-map-container" ref="mapContainer" style="height: 400px; width: 100%;" v-if="apiKey"></div>
    </div>
</script>
