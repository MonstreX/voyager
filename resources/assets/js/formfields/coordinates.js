import { getToastr } from '../core/toastr';

let vuePromise = null;

const resolveCallback = (path) => {
    if (!path) return null;
    const trimmed = String(path).trim();
    if (!trimmed) return null;
    const parts = trimmed.split('.').map((p) => p.trim()).filter(Boolean);
    let current = window;
    for (const part of parts) {
        if (current && Object.prototype.hasOwnProperty.call(current, part)) {
            current = current[part];
        } else if (current && part in current) {
            current = current[part];
        } else {
            return null;
        }
    }
    return typeof current === 'function' ? current : null;
};

const loadGoogleMaps = (apiKey) => {
    if (!apiKey) return Promise.resolve();
    if (!window.voyagerGoogleMapsPromise) {
        window.voyagerGoogleMapsPromise = new Promise((resolve, reject) => {
            if (window.google && window.google.maps) {
                resolve();
                return;
            }
            window.initVoyagerGoogleMaps = () => resolve();
            const script = document.createElement('script');
            script.src =
                'https://maps.googleapis.com/maps/api/js?key=' +
                encodeURIComponent(apiKey) +
                '&callback=initVoyagerGoogleMaps&libraries=places&v=weekly&loading=async';
            script.async = true;
            script.defer = true;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }
    return window.voyagerGoogleMapsPromise;
};

const createCoordinatesComponent = ({ templateSelector, onChangeCallbackPath }) => {
    const onChangeCallback = resolveCallback(onChangeCallbackPath);

    return {
        template: templateSelector,
        props: {
            apiKey: { type: String, default: '' },
            points: { type: Array, required: true },
            showAutocomplete: { type: Boolean, default: true },
            showLatLng: { type: Boolean, default: true },
            zoom: { type: Number, required: true },
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
            loadGoogleMaps(this.apiKey)
                .then(() => this.initMap())
                .catch((error) => {
                    console.error('[VoyagerCoordinates] Google Maps load failed', error);
                    const toastr = getToastr();
                    toastr && toastr.error('Google Maps failed to load');
                });
        },
        methods: {
            initMap: async function () {
                const vm = this;
                const center = vm.points[vm.points.length - 1];

                this.setLatLng(center.lat, center.lng);

                const { Map } = await google.maps.importLibrary('maps');
                const { Autocomplete } = await google.maps.importLibrary('places');

                vm.map = new Map(vm.$refs.mapContainer, {
                    zoom: vm.zoom,
                    center: { lat: parseFloat(center.lat), lng: parseFloat(center.lng) },
                    mapTypeControl: true,
                    streetViewControl: true,
                });

                vm.marker = new google.maps.Marker({
                    map: vm.map,
                    position: { lat: parseFloat(center.lat), lng: parseFloat(center.lng) },
                    draggable: true,
                    title: 'Drag to move',
                });

                vm.marker.addListener('dragend', function (event) {
                    vm.onMapDrag(event);
                });

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

            setLatLng: function (lat, lng) {
                this.lat = parseFloat(lat);
                this.lng = parseFloat(lng);
            },

            moveMapAndMarker: function (lat, lng) {
                const position = { lat: parseFloat(lat), lng: parseFloat(lng) };
                if (this.marker) {
                    this.marker.setPosition(position);
                }
                if (this.map) {
                    this.map.panTo(position);
                }
            },

            onMapDrag: function (event) {
                const lat = event.latLng.lat();
                const lng = event.latLng.lng();
                this.setLatLng(lat, lng);
                this.onChange('mapDragged');
            },

            onInputKeyPress: function (event) {
                if (event.which === 13) {
                    event.preventDefault();
                }
            },

            onLatLngInputChange: function () {
                this.moveMapAndMarker(this.lat, this.lng);
                this.onChange('latLngChanged');
            },

            onChange: function (eventType) {
                if (!onChangeCallback) {
                    return;
                }
                if (this.onChangeDebounceTimeout) {
                    clearTimeout(this.onChangeDebounceTimeout);
                }

                const self = this;
                this.onChangeDebounceTimeout = setTimeout(() => {
                    onChangeCallback(eventType, {
                        lat: self.lat,
                        lng: self.lng,
                        place: self.place,
                    });
                }, 300);
            },
        },
    };
};

const ensureVue = () => {
    if (vuePromise) return vuePromise;
    if (!window.Voyager || typeof window.Voyager.withVue !== 'function') {
        vuePromise = Promise.reject(new Error('Voyager.withVue unavailable'));
        return vuePromise;
    }
    vuePromise = window.Voyager.withVue((Vue) => Vue);
    return vuePromise;
};

export const initCoordinatesFields = () => {
    if (typeof document === 'undefined') return;

    const roots = Array.from(document.querySelectorAll('[data-voyager-coordinates-root]'));
    if (!roots.length) return;

    ensureVue()
        .then((Vue) => {
            roots.forEach((root) => {
                if (root.dataset.voyagerCoordinatesInit === 'true') {
                    return;
                }
                const templateId = root.dataset.voyagerTemplateId || '';
                if (!templateId) return;
                const templateSelector = `#${templateId}`;
                const onChangeCallbackPath = root.dataset.voyagerOnChange || '';

                const app = Vue.createApp({});
                app.component(
                    'coordinates',
                    createCoordinatesComponent({ templateSelector, onChangeCallbackPath })
                );
                app.mount(`#${root.id}`);
                root.dataset.voyagerCoordinatesInit = 'true';
            });
        })
        .catch((error) => {
            console.error('[VoyagerCoordinates] init failed', error);
        });
};

export const subscribeToEvents = (events) => {
    if (!events || typeof events.on !== 'function') return;
    events.on('dom:updated', () => initCoordinatesFields());
};

