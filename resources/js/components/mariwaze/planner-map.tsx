import { useEffect, useRef } from 'react';

export type NavigationPoint = {
    name: string;
    latitude: number;
    longitude: number;
    kind: 'port' | 'mouillage' | 'point';
};
type MarineOverlay = {
    point: NavigationPoint | null;
    current?: { speed_knots: number | null; direction_degrees: number | null };
    wind?: { speed_knots: number | null; direction_degrees: number | null };
};

declare global {
    interface Window {
        L?: any;
    }
}

function loadLeaflet(): Promise<any> {
    if (window.L) return Promise.resolve(window.L);

    return new Promise((resolve, reject) => {
        if (!document.getElementById('leaflet-style')) {
            const style = document.createElement('link');
            style.id = 'leaflet-style'; style.rel = 'stylesheet';
            style.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
            document.head.appendChild(style);
        }
        const existing = document.getElementById('leaflet-script') as HTMLScriptElement | null;
        if (existing) { existing.addEventListener('load', () => resolve(window.L), { once: true }); return; }
        const script = document.createElement('script');
        script.id = 'leaflet-script'; script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        script.onload = () => resolve(window.L); script.onerror = reject;
        document.body.appendChild(script);
    });
}

export default function PlannerMap({ points, onMapClick, className, marine, routeGeometry, routeAvailable = true }: { points: NavigationPoint[]; onMapClick: (point: NavigationPoint) => void; className?: string; marine?: MarineOverlay; routeGeometry?: Array<Pick<NavigationPoint, 'latitude' | 'longitude'>>; routeAvailable?: boolean }) {
    const element = useRef<HTMLDivElement>(null);

    useEffect(() => {
        let map: any;
        let active = true;

        loadLeaflet().then((L) => {
            if (!active || !element.current) return;
            map = L.map(element.current, { zoomControl: true, attributionControl: true });
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 18, attribution: '&copy; OpenStreetMap contributors',
            }).addTo(map);

            const sailingPath = routeGeometry && routeGeometry.length > 1 ? routeGeometry : routeAvailable ? safeSailingPath(points) : [];
            if (sailingPath.length > 1) {
                const path = sailingPath.map((point) => [point.latitude, point.longitude]);
                L.polyline(path, { color: '#0b68d7', weight: 4, dashArray: '10 8' }).addTo(map);
                map.fitBounds(path, { padding: [36, 36] });
            } else if (points.length === 1) {
                map.setView([points[0].latitude, points[0].longitude], 9);
            } else {
                map.setView([46.7, -2.4], 6);
            }

            points.forEach((point, index) => {
                const color = point.kind === 'mouillage' ? '#0e9f6e' : point.kind === 'port' ? '#0b68d7' : '#e66a21';
                L.circleMarker([point.latitude, point.longitude], { radius: index === 0 ? 9 : 7, color, fillColor: color, fillOpacity: 1, weight: 3 })
                    .bindTooltip(`${point.name} · ${point.kind}`, { direction: 'top' })
                    .on('click', () => onMapClick(point))
                    .addTo(map);
            });

            if (marine?.point && marine.current?.speed_knots !== null && marine.current?.speed_knots !== undefined && marine.current.direction_degrees !== null && marine.current.direction_degrees !== undefined) {
                addDirectionMarker(L, map, marine.point, marine.current.speed_knots, marine.current.direction_degrees, '#0b68d7', 'Courant');
            }
            const wind = marine?.wind;
            if (marine?.point && wind?.speed_knots !== null && wind?.speed_knots !== undefined && wind?.direction_degrees !== null && wind?.direction_degrees !== undefined) {
                addDirectionMarker(L, map, { ...marine.point, longitude: marine.point.longitude + 0.08 }, wind.speed_knots, wind.direction_degrees + 180, '#8b5cf6', 'Vent');
            }

            map.on('click', (event: { latlng: { lat: number; lng: number } }) => onMapClick({
                name: `Escale ${event.latlng.lat.toFixed(3)}, ${event.latlng.lng.toFixed(3)}`,
                latitude: event.latlng.lat,
                longitude: event.latlng.lng,
                kind: 'point',
            }));
        }).catch(() => undefined);

        return () => { active = false; map?.remove(); };
    }, [points, onMapClick, marine, routeGeometry, routeAvailable]);

    return <div ref={element} className={className ?? 'h-[58vh] min-h-[420px] w-full bg-muted'} aria-label="Carte interactive de préparation" />;
}

function safeSailingPath(points: NavigationPoint[]): NavigationPoint[] {
    const departure = points[0]?.name.normalize('NFD').replace(/\p{Diacritic}/gu, '').toLowerCase();
    const destination = points.at(-1)?.name.normalize('NFD').replace(/\p{Diacritic}/gu, '').toLowerCase();

    if (departure !== 'saint-malo' || destination !== 'quebec' || points.length < 4) return points;

    const horta = points.find((point) => point.name.toLowerCase() === 'horta');
    const halifax = points.find((point) => point.name.toLowerCase() === 'halifax');
    const quebec = points.at(-1);
    if (!horta || !halifax || !quebec) return points;

    return [
        points[0], horta, halifax,
        { name: 'Au large de la Nouvelle-Écosse', latitude: 45.12, longitude: -61.45, kind: 'point' },
        { name: 'Détroit de Cabot', latitude: 47.35, longitude: -59.45, kind: 'point' },
        { name: 'Golfe du Saint-Laurent', latitude: 48.55, longitude: -64.30, kind: 'point' },
        { name: 'Estuaire du Saint-Laurent', latitude: 48.40, longitude: -68.85, kind: 'point' },
        { name: 'Traverse du Saint-Laurent', latitude: 47.95, longitude: -70.05, kind: 'point' },
        quebec,
    ];
}

function addDirectionMarker(L: any, map: any, point: NavigationPoint, speed: number, direction: number, color: string, label: string): void {
    L.marker([point.latitude, point.longitude], {
        icon: L.divIcon({
            className: 'marine-direction-marker',
            html: `<div style="display:flex;flex-direction:column;align-items:center;color:${color};font-family:system-ui;font-weight:700;text-shadow:0 1px 2px #fff"><span style="font-size:34px;line-height:28px;transform:rotate(${direction}deg)">↑</span><span style="background:#ffffffdd;border:1px solid ${color};padding:1px 4px;font-size:11px">${label} ${speed.toFixed(1)} nd</span></div>`,
            iconSize: [82, 52], iconAnchor: [41, 26],
        }),
        interactive: false,
    }).addTo(map);
}
