import { useMemo } from 'react';
import PlannerMap, { type NavigationPoint } from '@/components/mariwaze/planner-map';

type Coordinates = { latitude: number; longitude: number };
type Props = { departure: string; destination: string; waypoints?: string[]; routeGeometry?: Array<Pick<NavigationPoint, 'latitude' | 'longitude'>>; routeAvailable?: boolean };

const knownPorts: Record<string, Coordinates> = {
    'saint-malo': { latitude: 48.649, longitude: -2.025 }, 'st-malo': { latitude: 48.649, longitude: -2.025 },
    vannes: { latitude: 47.655, longitude: -2.76 }, biarritz: { latitude: 43.483, longitude: -1.558 }, bonifacio: { latitude: 41.388, longitude: 9.159 },
    brest: { latitude: 48.39, longitude: -4.486 }, lorient: { latitude: 47.748, longitude: -3.37 }, 'la-rochelle': { latitude: 46.157, longitude: -1.153 },
    'saint-quay-portrieux': { latitude: 48.648, longitude: -2.825 }, trebeurden: { latitude: 48.769, longitude: -3.567 }, 'aber-wrach': { latitude: 48.604, longitude: -4.573 },
    'camaret-sur-mer': { latitude: 48.276, longitude: -4.596 }, audierne: { latitude: 48.025, longitude: -4.541 }, concarneau: { latitude: 47.875, longitude: -3.921 },
    marseille: { latitude: 43.296, longitude: 5.370 },
    quebec: { latitude: 46.813, longitude: -71.208 }, horta: { latitude: 38.537, longitude: -28.627 }, halifax: { latitude: 44.648, longitude: -63.575 },
};

function normalize(port: string): string { return port.normalize('NFD').replace(/\p{Diacritic}/gu, '').toLowerCase().replace(/[’']/g, '').replaceAll(' ', '-').trim(); }
function ignoreClick(): void {}

export default function CruiseMap({ departure, destination, waypoints = [], routeGeometry, routeAvailable = true }: Props) {
    const points = useMemo(() => [departure, ...waypoints, destination].map((name, index): NavigationPoint | null => {
        const coordinates = knownPorts[normalize(name)];
        return coordinates ? { name, ...coordinates, kind: index === 0 || index === waypoints.length + 1 ? 'port' : 'mouillage' } : null;
    }).filter((point): point is NavigationPoint => point !== null), [departure, destination, waypoints]);

    return <div className="relative aspect-[2/1] min-h-[280px] overflow-hidden border border-border/60 bg-muted">
        <PlannerMap points={points} routeGeometry={routeGeometry} routeAvailable={routeAvailable} onMapClick={ignoreClick} className="size-full" />
        <div className="absolute left-4 top-4 border border-border/70 bg-background/95 p-3 text-sm shadow-sm backdrop-blur-sm">
            <p className="text-xs font-semibold tracking-wide text-sea-blue uppercase">Itinéraire</p>
            <p className="mt-1 font-semibold">{departure} → {destination}</p>
            {points.length < 2 && <p className="mt-1 text-xs text-muted-foreground">Coordonnées du port en attente.</p>}
        </div>
        {!routeAvailable && <div className="absolute bottom-4 left-4 border border-safety-orange/40 bg-background/95 px-3 py-2 text-xs text-foreground shadow-sm">Route maritime indisponible : voie d’eau à vérifier.</div>}
    </div>;
}
