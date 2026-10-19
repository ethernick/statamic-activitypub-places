/**
 * Centralized mapping utilities for ActivityPub Places
 */

export const getOsmUrl = (location) => {
    if (!location) return 'https://www.openstreetmap.org';

    const lat = location.latitude || location.lat;
    const lon = location.longitude || location.lon;
    const osmType = location.osm_type;
    const osmId = location.osm_id;

    if (osmType && osmId) {
        return `https://www.openstreetmap.org/${osmType}/${osmId}#map=19/${lat}/${lon}`;
    }

    if (lat && lon) {
        return `https://www.openstreetmap.org/?mlat=${lat}&mlon=${lon}#map=17/${lat}/${lon}`;
    }

    return 'https://www.openstreetmap.org';
};

export const getGoogleMapsUrl = (location) => {
    if (!location) return 'https://www.google.com/maps';
    const lat = location.latitude || location.lat;
    const lon = location.longitude || location.lon;
    if (lat && lon) {
        return `https://www.google.com/maps/dir/?api=1&destination=${lat},${lon}`;
    }
    const query = encodeURIComponent(location.address || location.title || location.osm_name || location.name || '');
    return `https://www.google.com/maps/dir/?api=1&destination=${query}`;
};

export const getAppleMapsUrl = (location) => {
    if (!location) return 'https://maps.apple.com';
    const lat = location.latitude || location.lat;
    const lon = location.longitude || location.lon;
    const name = encodeURIComponent(location.title || location.osm_name || location.name || 'Location');
    if (lat && lon) {
        return `https://maps.apple.com/?daddr=${lat},${lon}&q=${name}`;
    }
    const query = encodeURIComponent(location.address || location.title || location.osm_name || location.name || '');
    return `https://maps.apple.com/?daddr=${query}&q=${name}`;
};

export const mapServices = [
    {
        name: 'Google Maps',
        color: '#4285F4',
        getUrl: getGoogleMapsUrl
    },
    {
        name: 'Apple Maps',
        color: '#007AFF',
        getUrl: getAppleMapsUrl
    },
    {
        name: 'OpenStreetMap',
        color: '#7EBC6F',
        getUrl: getOsmUrl
    }
];
