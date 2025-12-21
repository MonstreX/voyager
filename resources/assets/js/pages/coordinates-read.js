const getConfigFromDataset = (mapEl) => {
    if (!mapEl) return null;

    const key = mapEl.dataset.voyagerGooglemapsKey || '';
    const zoomRaw = mapEl.dataset.voyagerCoordinatesZoom || '';
    const zoom = zoomRaw ? Number(zoomRaw) : 8;
    const centerLat = Number(mapEl.dataset.voyagerCoordinatesCenterLat || '0');
    const centerLng = Number(mapEl.dataset.voyagerCoordinatesCenterLng || '0');

    let points = [];
    try {
        points = JSON.parse(mapEl.dataset.voyagerCoordinatesPoints || '[]') || [];
    } catch (error) {
        points = [];
    }

    const normalizedPoints = Array.isArray(points)
        ? points
              .map((point) => ({
                  lat: Number(point && point.lat),
                  lng: Number(point && point.lng),
              }))
              .filter((point) => Number.isFinite(point.lat) && Number.isFinite(point.lng))
        : [];

    const fallbackCenter = Number.isFinite(centerLat) && Number.isFinite(centerLng) ? { lat: centerLat, lng: centerLng } : null;

    return {
        key,
        zoom: Number.isFinite(zoom) ? zoom : 8,
        center: normalizedPoints[0] || fallbackCenter,
        points: normalizedPoints,
    };
};

const loadGoogleMaps = (apiKey) => {
    if (typeof window === 'undefined') {
        return Promise.reject(new Error('window is undefined'));
    }
    if (window.google && window.google.maps) {
        return Promise.resolve(window.google.maps);
    }

    const key = String(apiKey || '').trim();
    if (!key) {
        return Promise.reject(new Error('Google Maps API key is missing'));
    }

    if (window.__voyagerGoogleMapsPromise) {
        return window.__voyagerGoogleMapsPromise;
    }

    window.__voyagerGoogleMapsPromise = new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.async = true;
        script.defer = true;
        script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(key)}`;
        script.onload = () => resolve(window.google && window.google.maps ? window.google.maps : null);
        script.onerror = () => reject(new Error('Failed to load Google Maps'));
        document.head.appendChild(script);
    }).then((maps) => {
        if (!maps) {
            throw new Error('Google Maps loaded but API is unavailable');
        }
        return maps;
    });

    return window.__voyagerGoogleMapsPromise;
};

const initSingleMap = (mapEl) => {
    const config = getConfigFromDataset(mapEl);
    if (!config || !config.center) return;

    loadGoogleMaps(config.key)
        .then(() => {
            const map = new google.maps.Map(mapEl, {
                zoom: config.zoom,
                center: config.center,
            });

            config.points.forEach((point) => {
                new google.maps.Marker({
                    position: point,
                    map,
                });
            });
        })
        .catch((error) => {
            console.error('[Voyager] Failed to initialize Google map', error);
        });
};

export const initCoordinatesReadMaps = () => {
    if (typeof document === 'undefined') return;
    const mapEl = document.querySelector('[data-voyager-coordinates-map]');
    if (!mapEl || mapEl.dataset.voyagerInit === '1') return;
    mapEl.dataset.voyagerInit = '1';
    initSingleMap(mapEl);
};

export const subscribeToEvents = (events) => {
    if (!events || typeof events.on !== 'function') return;
    events.on('dom:updated', () => initCoordinatesReadMaps());
};

