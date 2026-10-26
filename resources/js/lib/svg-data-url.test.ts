import { describe, expect, it } from 'vitest';
import { svgDataUrl } from '@/lib/svg-data-url';

describe('svgDataUrl', () => {
    it('encodes SVG markup instead of exposing raw HTML', () => {
        const result = svgDataUrl('<svg><script>alert(1)</script></svg>');

        expect(result).toMatch(/^data:image\/svg\+xml,/);
        expect(result).not.toContain('<script>');
        expect(decodeURIComponent(result.split(',')[1])).toContain('<svg>');
    });
});
