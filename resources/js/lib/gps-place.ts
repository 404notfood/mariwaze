import type { Place } from '@/components/mariwaze/place-search';

export function gpsPlace(latitude: number, longitude: number): Place {
    const lat = Number(Math.max(-90, Math.min(90, latitude)).toFixed(5));
    const lon = Number(
        (((((longitude + 180) % 360) + 360) % 360) - 180).toFixed(5),
    );

    return {
        reference: `gps:${lat.toFixed(5)},${lon.toFixed(5)}`,
        name: `GPS ${lat.toFixed(5)}, ${lon.toFixed(5)}`,
        latitude: lat,
        longitude: lon,
        region: null,
        country_code: null,
        type: 'point',
    };
}

export function gpsPlaceFromReference(reference?: string | null): Place | null {
    const match = reference?.match(/^gps:(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)$/);

    if (!match) {
        return null;
    }

    const latitude = Number(match[1]);
    const longitude = Number(match[2]);

    if (
        !Number.isFinite(latitude) ||
        !Number.isFinite(longitude) ||
        Math.abs(latitude) > 90 ||
        Math.abs(longitude) > 180
    ) {
        return null;
    }

    return gpsPlace(latitude, longitude);
}
