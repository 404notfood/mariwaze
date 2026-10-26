import { describe, expect, it } from 'vitest';
import { normalizeLongitude, unwrapPath } from './geo';

describe('world map coordinates', () => {
    it('keeps a Pacific route close across the antimeridian', () => {
        const path = unwrapPath([
            { latitude: -18, longitude: 179 },
            { latitude: -18, longitude: -179 },
            { latitude: -18, longitude: -175 },
        ]);
        expect(path).toEqual([
            [-18, 179],
            [-18, 181],
            [-18, 185],
        ]);
    });
    it('keeps Atlantic coordinates unchanged and normalizes repeated world copies', () => {
        expect(
            unwrapPath([
                { latitude: 48, longitude: -2 },
                { latitude: 16, longitude: -61 },
            ]),
        ).toEqual([
            [48, -2],
            [16, -61],
        ]);
        expect(normalizeLongitude(540)).toBe(-180);
        expect(normalizeLongitude(-541)).toBe(179);
    });
});
