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
