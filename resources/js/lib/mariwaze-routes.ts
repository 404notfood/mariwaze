export type ChartMarkerType = 'port' | 'anchorage' | 'waypoint' | 'danger';

export type ChartMarker = {
    id: string;
    x: number;
    y: number;
    label: string;
    type: ChartMarkerType;
};

export type ChartRoute = {
    x: number;
    y: number;
}[];

export type ChartCurrent = {
    id: string;
    x: number;
    y: number;
    direction: number;
    speed: number;
    label: string;
};

export type ChartData = {
    route?: ChartRoute;
    markers: ChartMarker[];
    currents?: ChartCurrent[];
    currents_updated_at?: string;
    x?: number;
    y?: number;
};

export type MariwazeRoutes = {
    dashboard: string;
    cruises: string;
    cruise: (slug: string) => string;
    anchorage: (slug: string) => string;
    explore: string;
    map: string;
    boat: string;
    boatSetup: string;
    security: string;
};

export const appRoutes: MariwazeRoutes = {
    dashboard: '/dashboard',
    cruises: '/croisieres',
    cruise: (slug: string) => `/croisieres/${slug}`,
    anchorage: (slug: string) => `/mouillages/${slug}`,
    explore: '/explorer',
    map: '/carte',
    boat: '/bateau',
    boatSetup: '/bateau/configurer',
    security: '/securite',
};

export function mariwazeRoutesForPreview(_preview: boolean): MariwazeRoutes {
    return appRoutes;
}

export function mariwazeRoutesFromPath(pathname: string): MariwazeRoutes {
    return appRoutes;
}
