import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import HazardAlerts from '@/components/mariwaze/hazard-alerts';
import type { HazardAlert } from '@/components/mariwaze/planner-map';

const maliciousHazard: HazardAlert = {
    slug: 'test-zone',
    name: '<img src=x onerror=alert(1)>',
    category: 'traffic',
    category_label: 'Trafic',
    severity: 'warning',
    description: '<script>alert(1)</script>',
    advice: null,
    latitude: 48,
    longitude: -4,
    radius_nm: 5,
    on_route: true,
    distance_nm: 0,
    in_season: true,
};

describe('HazardAlerts', () => {
    it('renders external labels as text, never as executable markup', () => {
        const { container } = render(
            <HazardAlerts hazards={[maliciousHazard]} />,
        );

        expect(screen.getByText(maliciousHazard.name)).toBeInTheDocument();
        expect(
            screen.getByText(maliciousHazard.description),
        ).toBeInTheDocument();
        expect(container.querySelector('script')).toBeNull();
        expect(container.querySelector('img')).toBeNull();
    });
});
