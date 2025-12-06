@php
    $showAutocomplete = property_exists($row->details, 'showAutocompleteInput') ? (bool)$row->details->showAutocompleteInput : true;
    $showAutocomplete = $showAutocomplete ? 'true' : 'false';
    $showLatLng = property_exists($row->details, 'showLatLngInput') ? (bool)$row->details->showLatLngInput : true;
    $showLatLng = $showLatLng ? 'true' : 'false';
@endphp

<div id="coordinates-formfield-{{ $row->field }}">
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
        <div class="form-group" v-else>
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

@push('javascript')
    <script>
        (function() {
            const coordinatesComponent = {
                template: '#coordinates-template-{{ $row->field }}',
                props: {
                    apiKey: {
                        type: String,
                        default: '',
                    },
                    points: {
                        type: Array,
                        required: true,
                    },
                    showAutocomplete: {
                        type: Boolean,
                        default: true,
                    },
                    showLatLng: {
                        type: Boolean,
                        default: true,
                    },
                    zoom: {
                        type: Number,
                        required: true,
                    }
                },
                data() {
                    return {
                        lat: '',
                        lng: '',
                        map: null,
                        marker: null,
                        onChangeDebounceTimeout: null,
                        place: null,
                        autocomplete: null,
                    };
                },
                mounted() {
                    if (!this.apiKey) {
                        return;
                    }

                    const loadGoogleMaps = () => {
                        if (!window.voyagerGoogleMapsPromise) {
                            window.voyagerGoogleMapsPromise = new Promise((resolve, reject) => {
                                if (window.google && window.google.maps) {
                                    resolve();
                                    return;
                                }
                                window.initVoyagerGoogleMaps = () => {
                                    resolve();
                                };
                                const script = document.createElement('script');
                                // Keep loading 'places' library, removed 'marker' as we use legacy Marker
                                script.src = 'https://maps.googleapis.com/maps/api/js?key=' + this.apiKey + '&callback=initVoyagerGoogleMaps&libraries=places&v=weekly&loading=async';
                                script.async = true;
                                script.defer = true;
                                script.onerror = reject;
                                document.head.appendChild(script);
                            });
                        }
                        return window.voyagerGoogleMapsPromise;
                    };

                    loadGoogleMaps().then(() => {
                        this.initMap();
                    });
                },
                methods: {
                    initMap: async function() {
                        var vm = this;
                        var center = vm.points[vm.points.length - 1];

                        this.setLatLng(center.lat, center.lng);

                        // Import libraries (modern loading, legacy usage)
                        const { Map } = await google.maps.importLibrary("maps");
                        const { Autocomplete } = await google.maps.importLibrary("places");

                        // Create map
                        vm.map = new Map(vm.$refs.mapContainer, {
                            zoom: vm.zoom,
                            center: { lat: parseFloat(center.lat), lng: parseFloat(center.lng) },
                            mapTypeControl: true,
                            streetViewControl: true,
                        });

                        // Create Marker (Legacy) - Works without Map ID
                        vm.marker = new google.maps.Marker({
                            map: vm.map,
                            position: { lat: parseFloat(center.lat), lng: parseFloat(center.lng) },
                            draggable: true,
                            title: 'Drag to move'
                        });

                        // Listen to marker drag events
                        vm.marker.addListener('dragend', function(event) {
                            vm.onMapDrag(event);
                        });

                        // Setup places Autocomplete
                        if (this.showAutocomplete) {
                            const autocomplete = new Autocomplete(vm.$refs.autocompleteInput);
                            autocomplete.addListener('place_changed', () => {
                                vm.place = autocomplete.getPlace();

                                if (vm.place.geometry) {
                                    vm.setLatLng(vm.place.geometry.location.lat(), vm.place.geometry.location.lng());
                                    vm.moveMapAndMarker(vm.place.geometry.location.lat(), vm.place.geometry.location.lng());
                                }

                                vm.onChange('placeChanged');
                            });
                            vm.autocomplete = autocomplete;
                        }
                    },
                    
                    setLatLng: function(lat, lng) {
                        this.lat = parseFloat(lat);
                        this.lng = parseFloat(lng);
                    },

                    moveMapAndMarker: function(lat, lng) {
                        let position = { lat: parseFloat(lat), lng: parseFloat(lng) };
                        if (this.marker) {
                            this.marker.setPosition(position);
                        }
                        if (this.map) {
                            this.map.panTo(position);
                        }
                    },

                    onMapDrag: function(event) {
                        // Legacy Marker event has latLng method
                        let lat = event.latLng.lat();
                        let lng = event.latLng.lng();
                        
                        this.setLatLng(lat, lng);
                        this.onChange('mapDragged');
                    },

                    onInputKeyPress: function(event) {
                        if (event.which === 13) {
                            event.preventDefault();
                        }
                    },

                    onLatLngInputChange: function(event) {
                        this.moveMapAndMarker(this.lat, this.lng);
                        this.onChange('latLngChanged');
                    },

                    onChange: function(eventType) {
                        @if (property_exists($row->details, 'onChange'))
                            if (this.onChangeDebounceTimeout) {
                                clearTimeout(this.onChangeDebounceTimeout);
                            }

                            var self = this;
                            this.onChangeDebounceTimeout = setTimeout(function() {
                                {{ $row->details->onChange }}(eventType, {
                                    lat: self.lat,
                                    lng: self.lng,
                                    place: self.place
                                });
                            }, 300);
                        @endif
                    },
                }
            };

            window.whenVueReady(function() {
                const app = window.createVueApp({});
                app.component('coordinates', coordinatesComponent);
                app.mount('#coordinates-formfield-{{ $row->field }}');
            });
        })();
    </script>
@endpush
