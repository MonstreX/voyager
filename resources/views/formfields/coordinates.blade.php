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
                    initMap: function() {
                        var vm = this;
                        var center = vm.points[vm.points.length - 1];

                        this.setLatLng(center.lat, center.lng);

                        // Create map
                        vm.map = new google.maps.Map(vm.$refs.mapContainer, {
                            zoom: vm.zoom,
                            center: { lat: parseFloat(center.lat), lng: parseFloat(center.lng) },
                            mapTypeControl: true,
                            streetViewControl: true,
                        });

                        // Create Marker (Legacy) - Guarantees drag support without Vector Map ID
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
                            // Use legacy Autocomplete for compatibility with legacy Marker logic
                            const autocomplete = new google.maps.places.Autocomplete(vm.$refs.autocompleteInput);
                            autocomplete.addListener('place_changed', vm.onPlaceChange);
                        }
                    },
                    
                    setLatLng: function(lat, lng) {
                        this.lat = parseFloat(lat);
                        this.lng = parseFloat(lng);
                    },

                    moveMapAndMarker: function(lat, lng) {
                        let position = new google.maps.LatLng(parseFloat(lat), parseFloat(lng));
                        if (this.marker) {
                            this.marker.setPosition(position);
                        }
                        if (this.map) {
                            this.map.panTo(position);
                        }
                    },

                    onMapDrag: function(event) {
                        let lat = event.latLng.lat();
                        let lng = event.latLng.lng();
                        this.setLatLng(lat, lng);
                        this.onChange('mapDragged');
                    },

                    setLatLng: function(lat, lng) {
                        this.lat = parseFloat(lat);
                        this.lng = parseFloat(lng);
                    },

                    moveMapAndMarker: function(lat, lng) {
                        let position = { lat: parseFloat(lat), lng: parseFloat(lng) };
                        // Update AdvancedMarker position directly
                        if (this.marker) {
                            this.marker.position = position;
                        }
                        if (this.map) {
                            this.map.panTo(position);
                        }
                    },

                    onMapDrag: function(event) {
                        // Read position from the marker property
                        if (this.marker && this.marker.position) {
                            const pos = this.marker.position;
                            // AdvancedMarker position is usually a plain object {lat, lng} or LatLng object
                            let lat, lng;
                            
                            if (typeof pos.lat === 'function') {
                                lat = pos.lat();
                                lng = pos.lng();
                            } else {
                                lat = pos.lat;
                                lng = pos.lng;
                            }
                            
                            this.setLatLng(lat, lng);
                            this.onChange('mapDragged');
                        }
                    },

                    onInputKeyPress: function(event) {
                        if (event.which === 13) {
                            event.preventDefault();
                        }
                    },

                    onPlaceChange: function() {
                        this.place = this.autocomplete.getPlace();

                        if (this.place.geometry) {
                            this.setLatLng(this.place.geometry.location.lat(), this.place.geometry.location.lng());
                            this.moveMapAndMarker(this.place.geometry.location.lat(), this.place.geometry.location.lng());
                        }

                        this.onChange('placeChanged');
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

            const app = window.createVueApp({});
            app.component('coordinates', coordinatesComponent);
            app.mount('#coordinates-formfield-{{ $row->field }}');
        })();
    </script>
@endpush
