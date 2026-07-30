import { useMemo } from 'react';
import PlannerMap from '@/components/mariwaze/planner-map';
import type {HazardAlert, NavigationPoint} from '@/components/mariwaze/planner-map';

type Coordinates = { latitude: number; longitude: number };

export type CruiseStop = {
    name: string;
    latitude: number | null;
    longitude: number | null;
    kind: 'port' | 'mouillage' | 'point';
    day?: number;
};

type Props = {
    departure: string;
    destination: string;
    stops?: CruiseStop[];
    routeGeometry?: Coordinates[];
    routeAvailable?: boolean;
    hazards?: HazardAlert[];
    className?: string;
};

/** Repli quand aucune route n'a encore été calculée pour la croisière. */
const knownPorts: Record<string, Coordinates> = {
    'saint-malo': { latitude: 48.649, longitude: -2.025 }, 'st-malo': { latitude: 48.649, longitude: -2.025 },
    dinard: { latitude: 48.633, longitude: -2.055 },
    vannes: { latitude: 47.655, longitude: -2.76 }, biarritz: { latitude: 43.483, longitude: -1.558 }, bonifacio: { latitude: 41.388, longitude: 9.159 },
    brest: { latitude: 48.39, longitude: -4.486 }, lorient: { latitude: 47.748, longitude: -3.37 }, 'la-rochelle': { latitude: 46.157, longitude: -1.153 },
    'saint-quay-portrieux': { latitude: 48.648, longitude: -2.825 }, trebeurden: { latitude: 48.769, longitude: -3.567 }, 'aber-wrach': { latitude: 48.604, longitude: -4.573 },
    'camaret-sur-mer': { latitude: 48.276, longitude: -4.596 }, audierne: { latitude: 48.025, longitude: -4.541 }, concarneau: { latitude: 47.875, longitude: -3.921 },
    marseille: { latitude: 43.296, longitude: 5.370 },
    quebec: { latitude: 46.813, longitude: -71.208 }, horta: { latitude: 38.537, longitude: -28.627 }, halifax: { latitude: 44.648, longitude: -63.575 },
};

function normalize(port: string): string {
 return port.normalize('NFD').replace(/\p{Diacritic}/gu, '').toLowerCase().replace(/[’']/g, '').replaceAll(' ', '-').trim(); 
}
function ignoreClick(): void {}

const legendEntries = [
    { symbol: <span className="flex size-4 items-center justify-center rounded-full bg-[#15803d] text-[9px] font-bold text-white">D</span>, label: 'Départ' },
    { symbol: <span className="flex size-4 items-center justify-center rounded-full bg-ocean-navy text-[9px] font-bold text-white">A</span>, label: 'Arrivée' },
    { symbol: <span className="size-4 rounded-full border-2 border-sea-blue bg-white" />, label: 'Port-étape' },
    { symbol: <span className="size-4 rounded-full border-2 border-[#0e9f6e] bg-white" />, label: 'Mouillage' },
];

export default function CruiseMap({ departure, destination, stops = [], routeGeometry = [], routeAvailable = true, hazards = [], className }: Props) {
    const points = useMemo((): NavigationPoint[] => {
        const first = routeGeometry[0] ?? knownPorts[normalize(departure)] ?? null;
        const last = routeGeometry.at(-1) ?? knownPorts[normalize(destination)] ?? null;

        const intermediate = stops
            .filter((stop): stop is CruiseStop & Coordinates => stop.latitude !== null && stop.longitude !== null)
            .filter((stop) => normalize(stop.name) !== normalize(destination))
            .map((stop): NavigationPoint => ({ name: stop.name, latitude: stop.latitude, longitude: stop.longitude, kind: stop.kind, day: stop.day }));

        return [
            ...(first ? [{ name: departure, ...first, kind: 'depart' as const }] : []),
            ...intermediate,
            ...(last ? [{ name: destination, ...last, kind: 'arrivee' as const }] : []),
        ];
    }, [departure, destination, stops, routeGeometry]);

    const onRouteHazards = hazards.filter((zone) => zone.on_route);
    const anchorages = stops.filter((stop) => stop.kind === 'mouillage').length;

    return <div className={className ?? 'relative aspect-[2/1] min-h-[320px] overflow-hidden rounded-xl border border-border/60 bg-muted shadow-sm'}>
        <PlannerMap points={points} routeGeometry={routeGeometry} routeAvailable={routeAvailable} hazards={hazards} onMapClick={ignoreClick} className="size-full" />

        <div className="absolute left-4 top-4 z-[500] rounded-lg border border-border/70 bg-background/95 p-3 text-sm shadow-sm backdrop-blur-sm">
            <p className="text-xs font-semibold tracking-wide text-sea-blue uppercase">Itinéraire</p>
            <p className="mt-1 font-semibold">{departure} → {destination}</p>
            {stops.length > 0 && (
                <p className="mt-0.5 text-xs text-muted-foreground">
                    {stops.length} escale{stops.length > 1 ? 's' : ''}
                    {anchorages > 0 && ` dont ${anchorages} mouillage${anchorages > 1 ? 's' : ''}`}
                </p>
            )}
            {points.length < 2 && <p className="mt-1 text-xs text-muted-foreground">Coordonnées du port en attente.</p>}
        </div>

        {onRouteHazards.length > 0 && (
            <div className="absolute bottom-4 left-4 z-[500] rounded-lg border border-destructive/40 bg-background/95 px-3 py-2 text-xs font-medium shadow-sm backdrop-blur-sm">
                ⚠ {onRouteHazards.length} zone{onRouteHazards.length > 1 ? 's' : ''} de vigilance sur le trajet
            </div>
        )}

        {!routeAvailable && (
            <div className="absolute bottom-4 left-4 z-[500] rounded-lg border border-safety-orange/40 bg-background/95 px-3 py-2 text-xs shadow-sm">
                Route non calculée : tracé indicatif.
            </div>
        )}

        <div className="absolute bottom-4 right-4 z-[500] hidden rounded-lg border border-border/70 bg-background/95 p-2.5 text-xs shadow-sm backdrop-blur-sm sm:block">
            <div className="grid grid-cols-2 gap-x-4 gap-y-1.5">
                {legendEntries.map((entry) => (
                    <span key={entry.label} className="flex items-center gap-1.5 text-muted-foreground">
                        {entry.symbol}
                        {entry.label}
                    </span>
                ))}
                {hazards.length > 0 && (
                    <>
                        <span className="flex items-center gap-1.5 text-muted-foreground">
                            <span className="size-4 rounded-full border-2 border-dashed border-[#dc2626] bg-[#dc2626]/10" />
                            Danger
                        </span>
                        <span className="flex items-center gap-1.5 text-muted-foreground">
                            <span className="size-4 rounded-full border-2 border-dashed border-safety-orange bg-safety-orange/10" />
                            Vigilance
                        </span>
                    </>
                )}
            </div>
        </div>
    </div>;
}
