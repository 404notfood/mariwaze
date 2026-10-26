import { describe, expect, it } from 'vitest';
import { gpsPlace, gpsPlaceFromReference } from './gps-place';

describe('GPS places', () => {
    it('keeps a worldwide coordinate reference through a round trip', () => {
        const point = gpsPlace(16.2408636, -61.5334077);
        expect(point.name).toBe('GPS 16.24086, -61.53341');
        expect(gpsPlaceFromReference(point.reference)).toEqual(point);
    });
    it('normalizes map copies across the antimeridian', () => {
        expect(gpsPlace(-18, 181).longitude).toBe(-179);
        expect(gpsPlace(-18, -181).longitude).toBe(179);
    });
    it('rejects invalid references', () => {
        for (const value of [
            'gps:91,0',
            'gps:0,181',
            'gps:NaN,0',
            'locality:42',
            null,
        ]) {
            expect(gpsPlaceFromReference(value)).toBeNull();
        }
    });
});
