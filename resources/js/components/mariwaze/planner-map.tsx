import type * as Leaflet from 'leaflet';
import { useEffect, useEffectEvent, useRef } from 'react';
import { useAppearance } from '@/hooks/use-appearance';
import { useDesign } from '@/hooks/use-design';
import { normalizeLongitude, unwrapPath } from '@/lib/geo';

import 'leaflet/dist/leaflet.css';

export type NavigationPoint = {
    name: string;
    latitude: number;
    longitude: number;
    kind: 'depart' | 'arrivee' | 'port' | 'mouillage' | 'point';
    day?: number;
};

export type HazardAlert = {
    slug: string;
    name: string;
    category: string;
    category_label: string;
    severity: 'info' | 'warning' | 'danger';
    description: string;
    advice: string | null;
    latitude: number;
    longitude: number;
    radius_nm: number;
    geometry?: Array<{ latitude: number; longitude: number }> | null;
    on_route: boolean;
    distance_nm: number;
    in_season: boolean;
};

type SeamarkPin = {
    name: string | null;
    category: string;
    label: string;
    latitude: number;
    longitude: number;
};

type MarineOverlay = {
    point: NavigationPoint | null;
    current?: { speed_knots: number | null; direction_degrees: number | null };
    wind?: { speed_knots: number | null; direction_degrees: number | null };
};

const SEVERITY_COLORS: Record<HazardAlert['severity'], string> = {
    danger: '#dc2626',
    warning: '#ff8c42',
    info: '#47b5ff',
};

// Couleurs Passerelle, résolues par le navigateur (styles en ligne des marqueurs).
const POINT_COLORS: Record<NavigationPoint['kind'], string> = {
    depart: 'var(--route)',
    arrivee: 'var(--route)',
    port: 'var(--primary)',
    mouillage: 'var(--good)',
    point: 'var(--muted-foreground)',
};

/** Lit un jeton du thème pour les tracés SVG de Leaflet (qui n'acceptent pas var()). */
function themeColor(name: string, fallback: string): string {
    if (typeof window === 'undefined') {
        return fallback;
    }

    const value = getComputedStyle(document.documentElement)
        .getPropertyValue(name)
        .trim();

    return value || fallback;
}

export type FitPadding = {
    paddingTopLeft: [number, number];
    paddingBottomRight: [number, number];
};

const DEFAULT_FIT_PADDING: FitPadding = {
    paddingTopLeft: [40, 40],
    paddingBottomRight: [40, 40],
};

let leafletPromise: Promise<typeof Leaflet> | null = null;
const EMPTY_HAZARDS: HazardAlert[] = [];

function loadLeaflet(): Promise<typeof Leaflet> {
    leafletPromise ??= import('leaflet');

    return leafletPromise;
}

function textLine(text: string, cssText: string): HTMLParagraphElement {
    const line = document.createElement('p');
    line.textContent = text;
    line.style.cssText = cssText;

    return line;
}

function hazardPopup(zone: HazardAlert, color: string): HTMLDivElement {
    const popup = document.createElement('div');
    popup.style.cssText = 'max-width:240px;font-family:system-ui';
    const category = textLine(
        zone.category_label,
        `margin:0;font-weight:700;color:${color}`,
    );
    const name = textLine(zone.name, 'margin:2px 0 0;font-weight:600');
    const description = textLine(
        zone.description,
        'margin:4px 0 0;font-size:12px',
    );
    popup.append(category, name, description);

    if (zone.advice) {
        popup.append(
            textLine(
                `Conseil : ${zone.advice}`,
                'margin:4px 0 0;font-size:12px',
            ),
        );
    }

    if (!zone.in_season) {
        popup.append(
            textLine(
                'Hors saison principale aux dates de la croisière.',
                'margin:4px 0 0;font-size:11px;color:#64748b',
            ),
        );
    }

    return popup;
}

function tooltip(text: string): HTMLSpanElement {
    const element = document.createElement('span');
    element.textContent = text;

    return element;
}

function markerHtml(point: NavigationPoint): { html: string; size: number } {
    const color = POINT_COLORS[point.kind] ?? POINT_COLORS.point;
    const anchorGlyph =
        '<svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><circle cx="12" cy="5" r="2.6"/><path d="M12 8v13M5 13H3a9 9 0 0 0 18 0h-2"/></svg>';

    if (point.kind === 'depart' || point.kind === 'arrivee') {
        const label = point.kind === 'depart' ? 'D' : 'A';

        return {
            size: 30,
            html: `<div style="width:26px;height:26px;border-radius:3px;background:${color};color:var(--background);display:flex;align-items:center;justify-content:center;font:700 12px 'JetBrains Mono',monospace;border:2px solid var(--background)">${label}</div>`,
        };
    }

    if (point.kind === 'point') {
        return {
            size: 14,
            html: `<div style="width:12px;height:12px;border-radius:9999px;background:var(--background);border:2px solid ${color}"></div>`,
        };
    }

    const badge = point.day
        ? `<span style="position:absolute;top:-8px;right:-12px;background:var(--background);color:var(--foreground);border-radius:2px;padding:0 3px;font:500 9px/13px 'JetBrains Mono',monospace;border:1px solid var(--line-strong)">J${point.day}</span>`
        : '';

    return {
        size: 24,
        html: `<div style="position:relative;width:24px;height:24px;border-radius:9999px;background:var(--background);color:${color};display:flex;align-items:center;justify-content:center;border:2px solid ${color}">${anchorGlyph}${badge}</div>`,
    };
}

export default function PlannerMap({
    points,
    onMapClick,
    className,
    marine,
    routeGeometry,
    routeAvailable = true,
    hazards = EMPTY_HAZARDS,
    preserveViewport = false,
    zoomPosition = 'topleft',
    fitPadding = DEFAULT_FIT_PADDING,
}: {
    points: NavigationPoint[];
    onMapClick: (point: NavigationPoint) => void;
    className?: string;
    marine?: MarineOverlay;
    routeGeometry?: Array<Pick<NavigationPoint, 'latitude' | 'longitude'>>;
    routeAvailable?: boolean;
    hazards?: HazardAlert[];
    preserveViewport?: boolean;
    /** Coin du contrôle de zoom (Au large le place à droite, hors des panneaux). */
    zoomPosition?: 'topleft' | 'topright' | 'bottomleft' | 'bottomright';
    /** Marges de cadrage en pixels, pour ne pas cacher le tracé sous les panneaux. */
    fitPadding?: FitPadding;
}) {
    const element = useRef<HTMLDivElement>(null);
    const viewport = useRef<{ center: Leaflet.LatLng; zoom: number } | null>(
        null,
    );
    const handleMapClick = useEffectEvent(onMapClick);
    const { design } = useDesign();
    const { resolvedAppearance } = useAppearance();

    useEffect(() => {
        const container = element.current;

        if (!container) {
            return;
        }

        let map: any;
        let active = true;
        let detachSeamarks: () => void = () => undefined;

        loadLeaflet()
            .then((L) => {
                if (!active) {
                    return;
                }

                map = L.map(container, {
                    zoomAnimation: false,
                    fadeAnimation: false,
                    zoomControl: false,
                    attributionControl: true,
                    center: [20, 0],
                    zoom: 2,
                    worldCopyJump: true,
                });
                L.control.zoom({ position: zoomPosition }).addTo(map);
                L.tileLayer(
                    'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                    {
                        maxZoom: 18,
                        attribution: '&copy; OpenStreetMap contributors',
                    },
                ).addTo(map);
                L.control.scale({ imperial: false }).addTo(map);

                // Zones de vigilance, dessinées sous le tracé et les marqueurs.
                // Contour réel (polygone importé d'OpenSeaMap) quand il existe,
                // cercle enveloppant sinon.
                hazards.forEach((zone) => {
                    const color =
                        SEVERITY_COLORS[zone.severity] ?? SEVERITY_COLORS.info;
                    const style = {
                        color,
                        weight: 1.5,
                        dashArray:
                            zone.geometry && zone.geometry.length >= 3
                                ? undefined
                                : '6 6',
                        fillColor: color,
                        fillOpacity: zone.on_route ? 0.12 : 0.06,
                        interactive: true,
                    };
                    const shape =
                        zone.geometry && zone.geometry.length >= 3
                            ? L.polygon(
                                  zone.geometry.map((point) => [
                                      point.latitude,
                                      point.longitude,
                                  ]),
                                  style,
                              )
                            : L.circle([zone.latitude, zone.longitude], {
                                  ...style,
                                  radius: zone.radius_nm * 1852,
                              });

                    shape.bindPopup(hazardPopup(zone, color)).addTo(map);
                });

                detachSeamarks = attachSeamarkLayer(L, map);

                const hasExactRoute = Boolean(
                    routeGeometry && routeGeometry.length > 1,
                );
                const sailingPath = hasExactRoute
                    ? routeGeometry!
                    : routeAvailable
                      ? points
                      : [];

                if (sailingPath.length > 1) {
                    const path = unwrapPath(sailingPath);
                    // Liseré clair sous la route pour la détacher du fond de carte.
                    L.polyline(path, {
                        color: themeColor('--background', '#0a1118'),
                        weight: 7,
                        opacity: 0.85,
                    }).addTo(map);
                    L.polyline(path, {
                        color: themeColor('--chart-route', '#ffb020'),
                        weight: 3,
                        ...(hasExactRoute ? {} : { dashArray: '10 8' }),
                    }).addTo(map);

                    if (!preserveViewport || !viewport.current) {
                        map.fitBounds(path, fitPadding);
                    }
                } else if (
                    points.length > 1 &&
                    (!preserveViewport || !viewport.current)
                ) {
                    map.fitBounds(unwrapPath(points), {
                        ...fitPadding,
                        animate: false,
                    });
                } else if (
                    points.length === 1 &&
                    (!preserveViewport || !viewport.current)
                ) {
                    map.setView([points[0].latitude, points[0].longitude], 9);
                } else {
                    map.setView([20, 0], 2);
                }

                if (preserveViewport && viewport.current) {
                    map.setView(
                        viewport.current.center,
                        viewport.current.zoom,
                        { animate: false },
                    );
                }

                points.forEach((point) => {
                    const anchor =
                        routeGeometry?.[0]?.longitude ??
                        points[0]?.longitude ??
                        point.longitude;
                    const longitude =
                        point.longitude +
                        360 * Math.round((anchor - point.longitude) / 360);
                    const { html, size } = markerHtml(point);
                    const kindLabel = {
                        depart: 'Départ',
                        arrivee: 'Arrivée',
                        port: 'Port',
                        mouillage: 'Mouillage',
                        point: 'Étape',
                    }[point.kind];
                    L.marker([point.latitude, longitude], {
                        icon: L.divIcon({
                            className: 'mariwaze-point-marker',
                            html,
                            iconSize: [size, size],
                            iconAnchor: [size / 2, size / 2],
                        }),
                    })
                        .bindTooltip(
                            tooltip(
                                `${point.name} · ${kindLabel}${point.day ? ` (J${point.day})` : ''}`,
                            ),
                            { direction: 'top', offset: [0, -size / 2] },
                        )
                        .on('click', () => handleMapClick(point))
                        .addTo(map);
                });

                if (
                    marine?.point &&
                    marine.current?.speed_knots !== null &&
                    marine.current?.speed_knots !== undefined &&
                    marine.current.direction_degrees !== null &&
                    marine.current.direction_degrees !== undefined
                ) {
                    addDirectionMarker(
                        L,
                        map,
                        marine.point,
                        marine.current.speed_knots,
                        marine.current.direction_degrees,
                        '#0b68d7',
                        'Courant',
                    );
                }

                const wind = marine?.wind;

                if (
                    marine?.point &&
                    wind?.speed_knots !== null &&
                    wind?.speed_knots !== undefined &&
                    wind?.direction_degrees !== null &&
                    wind?.direction_degrees !== undefined
                ) {
                    addDirectionMarker(
                        L,
                        map,
                        {
                            ...marine.point,
                            longitude: marine.point.longitude + 0.08,
                        },
                        wind.speed_knots,
                        wind.direction_degrees + 180,
                        '#8b5cf6',
                        'Vent',
                    );
                }

                map.on(
                    'click',
                    (event: { latlng: { lat: number; lng: number } }) =>
                        handleMapClick({
                            name: `Escale ${event.latlng.lat.toFixed(3)}, ${event.latlng.lng.toFixed(3)}`,
                            latitude: event.latlng.lat,
                            longitude: event.latlng.lng,
                            kind: 'point',
                        }),
                );
            })
            .catch(() => undefined);

        return () => {
            active = false;
            detachSeamarks();

            if (map) {
                viewport.current = {
                    center: map.getCenter(),
                    zoom: map.getZoom(),
                };
                map.remove();
            } else if ((container as any)._leaflet_id) {
                delete (container as any)._leaflet_id;
                (container as any)._leaflet_events = undefined;
            }
        };
    }, [
        points,
        marine,
        routeGeometry,
        routeAvailable,
        hazards,
        preserveViewport,
        zoomPosition,
        fitPadding,
        // Redessine le tracé avec les couleurs du design et du mode choisis.
        design,
        resolvedAppearance,
    ]);

    return (
        <div
            ref={element}
            className={className ?? 'h-[58vh] min-h-[420px] w-full bg-muted'}
            aria-label="Carte interactive de préparation"
        />
    );
}

const SEAMARK_STYLES: Record<
    string,
    { bg: string; fg: string; glyph: string }
> = {
    cardinal_north: { bg: '#fbbf24', fg: '#000', glyph: 'N' },
    cardinal_south: { bg: '#fbbf24', fg: '#000', glyph: 'S' },
    cardinal_east: { bg: '#fbbf24', fg: '#000', glyph: 'E' },
    cardinal_west: { bg: '#fbbf24', fg: '#000', glyph: 'W' },
    lateral_port: { bg: '#dc2626', fg: '#fff', glyph: '▮' },
    lateral_starboard: { bg: '#16a34a', fg: '#fff', glyph: '▲' },
    isolated_danger: { bg: '#111827', fg: '#f87171', glyph: '!' },
    safe_water: { bg: '#fff', fg: '#dc2626', glyph: '●' },
    special_purpose: { bg: '#facc15', fg: '#000', glyph: '✕' },
    lighthouse: { bg: '#1e3a8a', fg: '#fff', glyph: '★' },
    light: { bg: '#1e3a8a', fg: '#fff', glyph: '☆' },
};

/**
 * Couche de balisage (cardinales, latérales, phares) chargée dynamiquement
 * dès que le zoom est suffisant pour qu'elle soit lisible.
 */
function attachSeamarkLayer(L: any, map: any): () => void {
    const layer = L.layerGroup().addTo(map);
    let controller: AbortController | null = null;

    const refresh = () => {
        if (map.getZoom() < 11) {
            layer.clearLayers();

            return;
        }

        const bounds = map.getBounds();
        controller?.abort();
        controller = new AbortController();
        const query = new URLSearchParams({
            south: bounds.getSouth().toFixed(4),
            north: bounds.getNorth().toFixed(4),
            west: normalizeLongitude(bounds.getWest()).toFixed(4),
            east: normalizeLongitude(bounds.getEast()).toFixed(4),
        });

        fetch(`/api/seamarks?${query}`, {
            signal: controller.signal,
            headers: { Accept: 'application/json' },
        })
            .then((response) =>
                response.ok ? (response.json() as Promise<SeamarkPin[]>) : [],
            )
            .then((marks) => {
                layer.clearLayers();
                marks.forEach((mark) => {
                    const style =
                        SEAMARK_STYLES[mark.category] ??
                        SEAMARK_STYLES.special_purpose;
                    L.marker([mark.latitude, mark.longitude], {
                        icon: L.divIcon({
                            className: 'mariwaze-seamark',
                            html: `<div style="width:16px;height:16px;border-radius:4px;background:${style.bg};color:${style.fg};display:flex;align-items:center;justify-content:center;font:700 10px system-ui;border:1.5px solid #06283d;box-shadow:0 1px 3px rgba(6,40,61,.4)">${style.glyph}</div>`,
                            iconSize: [16, 16],
                            iconAnchor: [8, 8],
                        }),
                    })
                        .bindTooltip(
                            tooltip(
                                `${mark.name ? `${mark.name} · ` : ''}${mark.label}`,
                            ),
                            { direction: 'top', offset: [0, -8] },
                        )
                        .addTo(layer);
                });
            })
            .catch(() => undefined);
    };

    map.on('moveend zoomend', refresh);
    refresh();

    return () => {
        controller?.abort();
        map.off('moveend zoomend', refresh);
        layer.remove();
    };
}

function addDirectionMarker(
    L: any,
    map: any,
    point: NavigationPoint,
    speed: number,
    direction: number,
    color: string,
    label: string,
): void {
    L.marker([point.latitude, point.longitude], {
        icon: L.divIcon({
            className: 'marine-direction-marker',
            html: `<div style="display:flex;flex-direction:column;align-items:center;color:${color};font-family:system-ui;font-weight:700;text-shadow:0 1px 2px #fff"><span style="font-size:34px;line-height:28px;transform:rotate(${direction}deg)">↑</span><span style="background:#ffffffdd;border:1px solid ${color};padding:1px 4px;font-size:11px">${label} ${speed.toFixed(1)} nd</span></div>`,
            iconSize: [82, 52],
            iconAnchor: [41, 26],
        }),
        interactive: false,
    }).addTo(map);
}
