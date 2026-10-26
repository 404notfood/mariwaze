import { Head, Link } from '@inertiajs/react';
import Anchor from 'lucide-react/icons/anchor';
import ArrowRight from 'lucide-react/icons/arrow-right';
import Compass from 'lucide-react/icons/compass';
import Eraser from 'lucide-react/icons/eraser';
import Flag from 'lucide-react/icons/flag';
import LoaderCircle from 'lucide-react/icons/loader-circle';
import MapPin from 'lucide-react/icons/map-pin';
import Wind from 'lucide-react/icons/wind';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import PlannerMap from '@/components/mariwaze/planner-map';
import type {
    HazardAlert,
    NavigationPoint,
} from '@/components/mariwaze/planner-map';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { appRoutes } from '@/lib/mariwaze-routes';

type Route = {
    slug: string;
    title: string;
    points: NavigationPoint[];
    geometry?: Array<Pick<NavigationPoint, 'latitude' | 'longitude'>>;
    available?: boolean;
    hazards?: HazardAlert[];
} | null;
type MarineConditions = {
    observed_at: string | null;
    current: { speed_knots: number | null; direction_degrees: number | null };
    wind: {
        speed_knots: number | null;
        direction_degrees: number | null;
        gusts_knots: number | null;
    };
};
type RoutePreview = {
    geometry: Array<Pick<NavigationPoint, 'latitude' | 'longitude'>>;
    distance_nm: number;
    hazards: HazardAlert[];
    departure_port: string | null;
    arrival_port: string | null;
};

export default function DemoMap({
    route,
    conditions: initialConditions = null,
}: {
    route: Route;
    conditions?: MarineConditions | null;
}) {
    const [addedPoints, setAddedPoints] = useState<NavigationPoint[]>([]);
    const [selectedPoint, setSelectedPoint] = useState<NavigationPoint | null>(
        route?.points[0] ?? null,
    );
    const [conditions, setConditions] = useState<MarineConditions | null>(
        initialConditions,
    );

    // Pointage d'un trajet à la volée : départ puis arrivée, puis calcul.
    const [picking, setPicking] = useState<'depart' | 'arrivee' | null>(null);
    const [departPoint, setDepartPoint] = useState<NavigationPoint | null>(
        null,
    );
    const [arrivalPoint, setArrivalPoint] = useState<NavigationPoint | null>(
        null,
    );
    const [preview, setPreview] = useState<RoutePreview | null>(null);
    const [previewLoading, setPreviewLoading] = useState(false);
    const [previewError, setPreviewError] = useState<string | null>(null);
    const initialRoutePoint = route?.points[0];

    const points = useMemo(
        () => [
            ...(route?.points ?? []),
            ...addedPoints,
            ...(departPoint ? [departPoint] : []),
            ...(arrivalPoint ? [arrivalPoint] : []),
        ],
        [route, addedPoints, departPoint, arrivalPoint],
    );

    const previewAbort = useRef<AbortController | null>(null);

    useEffect(() => () => previewAbort.current?.abort(), []);

    const requestPreview = useCallback(
        (from: NavigationPoint, to: NavigationPoint) => {
            previewAbort.current?.abort();
            const controller = new AbortController();
            previewAbort.current = controller;
            setPreview(null);
            setPreviewError(null);
            setPreviewLoading(true);

            const query = new URLSearchParams({
                from_latitude: String(from.latitude),
                from_longitude: String(from.longitude),
                to_latitude: String(to.latitude),
                to_longitude: String(to.longitude),
            });

            fetch(`/api/routes/preview?${query}`, {
                signal: controller.signal,
                headers: { Accept: 'application/json' },
            })
                .then(async (response) => {
                    const payload = await response.json();

                    if (!response.ok) {
                        throw new Error(
                            payload.message ??
                                'Le trajet n’a pas pu être calculé.',
                        );
                    }

                    setPreview(payload as RoutePreview);
                })
                .catch((error: unknown) => {
                    if (!controller.signal.aborted) {
                        setPreviewError(
                            error instanceof Error
                                ? error.message
                                : 'Le trajet n’a pas pu être calculé.',
                        );
                    }
                })
                .finally(() => {
                    if (!controller.signal.aborted) {
                        setPreviewLoading(false);
                    }
                });
        },
        [],
    );

    const handleMapClick = useCallback(
        (point: NavigationPoint) => {
            if (picking === 'depart') {
                const next: NavigationPoint = {
                    ...point,
                    kind: 'depart',
                    name: point.kind === 'point' ? 'Départ pointé' : point.name,
                };
                setDepartPoint(next);
                setPreview(null);
                setPreviewError(null);
                setPicking(arrivalPoint ? null : 'arrivee');

                if (arrivalPoint) {
                    requestPreview(next, arrivalPoint);
                }

                return;
            }

            if (picking === 'arrivee') {
                const next: NavigationPoint = {
                    ...point,
                    kind: 'arrivee',
                    name:
                        point.kind === 'point' ? 'Arrivée pointée' : point.name,
                };
                setArrivalPoint(next);
                setPreview(null);
                setPreviewError(null);
                setPicking(null);

                if (departPoint) {
                    requestPreview(departPoint, next);
                }

                return;
            }

            setSelectedPoint(point);
        },
        [picking, departPoint, arrivalPoint, requestPreview],
    );

    const clearPreview = () => {
        previewAbort.current?.abort();
        setDepartPoint(null);
        setArrivalPoint(null);
        setPreview(null);
        setPreviewError(null);
        setPreviewLoading(false);
        setPicking(null);
    };

    useEffect(() => {
        if (!selectedPoint) {
            return;
        }

        if (selectedPoint === initialRoutePoint && initialConditions) {
            return;
        }

        const controller = new AbortController();
        fetch(
            `/api/marine/conditions?latitude=${selectedPoint.latitude}&longitude=${selectedPoint.longitude}`,
            { signal: controller.signal },
        )
            .then((response) =>
                response.ok
                    ? (response.json() as Promise<MarineConditions>)
                    : null,
            )
            .then(setConditions)
            .catch(() => undefined);

        return () => controller.abort();
    }, [initialConditions, initialRoutePoint, selectedPoint]);

    const addSelectedPoint = () => {
        if (selectedPoint && selectedPoint.kind === 'point') {
            setAddedPoints((current) => [...current, selectedPoint]);
        }
    };

    const previewOnRouteHazards =
        preview?.hazards.filter((zone) => zone.on_route).length ?? 0;

    return (
        <>
            <Head title="Carte de préparation" />
            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <header className="flex flex-col gap-3 border-b border-border/60 pb-5 md:flex-row md:items-end md:justify-between">
                    <div>
                        <p className="text-sm text-muted-foreground">
                            Carte de préparation
                        </p>
                        <h1 className="mt-1 text-2xl font-bold md:text-3xl">
                            Itinéraire, vent et courants
                        </h1>
                    </div>
                    {route ? (
                        <Button
                            asChild
                            className="bg-sea-blue hover:bg-sea-blue/90"
                        >
                            <Link href={`/croisieres/${route.slug}`}>
                                Voir la croisière
                                <ArrowRight className="size-4" />
                            </Link>
                        </Button>
                    ) : (
                        <Button
                            asChild
                            className="bg-sea-blue hover:bg-sea-blue/90"
                        >
                            <Link href="/croisieres/nouvelle">
                                Créer une croisière
                                <ArrowRight className="size-4" />
                            </Link>
                        </Button>
                    )}
                </header>

                {route && (
                    <div className="flex flex-wrap items-center gap-2">
                        <Badge variant="secondary">{route.title}</Badge>
                        <span className="text-sm text-muted-foreground">
                            {points.length} points d’itinéraire
                        </span>
                    </div>
                )}

                <Card className="border-border/60">
                    <CardContent className="flex flex-wrap items-center gap-2 py-3">
                        <span className="mr-1 text-sm font-medium">
                            Tracer un trajet :
                        </span>
                        <Button
                            size="sm"
                            variant={
                                picking === 'depart' ? 'default' : 'outline'
                            }
                            className={
                                picking === 'depart'
                                    ? 'bg-sea-blue hover:bg-sea-blue/90'
                                    : ''
                            }
                            onClick={() =>
                                setPicking(
                                    picking === 'depart' ? null : 'depart',
                                )
                            }
                        >
                            <MapPin className="size-4" />
                            {departPoint
                                ? 'Déplacer le départ'
                                : 'Placer le départ'}
                        </Button>
                        <Button
                            size="sm"
                            variant={
                                picking === 'arrivee' ? 'default' : 'outline'
                            }
                            className={
                                picking === 'arrivee'
                                    ? 'bg-sea-blue hover:bg-sea-blue/90'
                                    : ''
                            }
                            onClick={() =>
                                setPicking(
                                    picking === 'arrivee' ? null : 'arrivee',
                                )
                            }
                        >
                            <Flag className="size-4" />
                            {arrivalPoint
                                ? 'Déplacer l’arrivée'
                                : 'Placer l’arrivée'}
                        </Button>
                        {(departPoint || arrivalPoint) && (
                            <Button
                                size="sm"
                                variant="ghost"
                                onClick={clearPreview}
                            >
                                <Eraser className="size-4" />
                                Effacer
                            </Button>
                        )}

                        <span className="ml-auto flex items-center gap-2 text-sm text-muted-foreground">
                            {previewLoading && (
                                <>
                                    <LoaderCircle className="size-4 animate-spin" />{' '}
                                    Calcul du trajet…
                                </>
                            )}
                            {!previewLoading &&
                                picking === 'depart' &&
                                'Cliquez sur la carte pour placer le départ.'}
                            {!previewLoading &&
                                picking === 'arrivee' &&
                                'Cliquez sur la carte pour placer l’arrivée.'}
                            {!previewLoading && !picking && preview && (
                                <>
                                    <span className="font-semibold text-foreground">
                                        {preview.distance_nm} M
                                    </span>
                                    {previewOnRouteHazards > 0 && (
                                        <span className="text-safety-orange">
                                            · {previewOnRouteHazards} zone
                                            {previewOnRouteHazards > 1
                                                ? 's'
                                                : ''}{' '}
                                            de vigilance
                                        </span>
                                    )}
                                </>
                            )}
                            {!previewLoading &&
                                !picking &&
                                !preview &&
                                !previewError &&
                                !departPoint &&
                                'Placez un départ et une arrivée pour calculer la route.'}
                        </span>

                        {preview?.departure_port && preview?.arrival_port && (
                            <Button
                                asChild
                                size="sm"
                                className="bg-sea-blue hover:bg-sea-blue/90"
                            >
                                <Link
                                    href={`/croisieres/nouvelle?departure=${encodeURIComponent(preview.departure_port)}&destination=${encodeURIComponent(preview.arrival_port)}`}
                                >
                                    Créer cette croisière
                                    <ArrowRight className="size-4" />
                                </Link>
                            </Button>
                        )}
                    </CardContent>
                    {previewError && (
                        <CardContent className="border-t border-border/60 py-2 text-sm text-destructive">
                            {previewError}
                        </CardContent>
                    )}
                </Card>

                <section className="overflow-hidden border border-border/60">
                    <PlannerMap
                        points={points}
                        routeGeometry={preview?.geometry ?? route?.geometry}
                        routeAvailable={preview ? true : route?.available}
                        hazards={preview ? preview.hazards : route?.hazards}
                        onMapClick={handleMapClick}
                        marine={{
                            point: selectedPoint,
                            current: conditions?.current,
                            wind: conditions?.wind,
                        }}
                    />
                </section>

                <section className="grid gap-4 lg:grid-cols-3">
                    <Card className="border-border/60 lg:col-span-2">
                        <CardContent className="grid gap-4 pt-6 sm:grid-cols-2">
                            <ConditionCard
                                icon={Compass}
                                label="Courant"
                                speed={conditions?.current.speed_knots}
                                direction={
                                    conditions?.current.direction_degrees
                                }
                                suffix="nd"
                            />
                            <ConditionCard
                                icon={Wind}
                                label="Vent à 10 m"
                                speed={conditions?.wind.speed_knots}
                                direction={conditions?.wind.direction_degrees}
                                suffix="nd"
                                detail={
                                    conditions?.wind.gusts_knots
                                        ? `Rafales ${conditions.wind.gusts_knots.toFixed(1)} nd`
                                        : undefined
                                }
                            />
                        </CardContent>
                    </Card>
                    <Card className="border-border/60">
                        <CardContent className="space-y-3 pt-6">
                            <p className="flex items-center gap-2 font-semibold">
                                <MapPin className="size-4 text-sea-blue" />
                                Point sélectionné
                            </p>
                            {selectedPoint ? (
                                <>
                                    <p className="text-sm">
                                        {selectedPoint.name}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {selectedPoint.latitude.toFixed(4)},{' '}
                                        {selectedPoint.longitude.toFixed(4)}
                                    </p>
                                    {selectedPoint.kind === 'point' && (
                                        <Button
                                            className="w-full bg-sea-blue hover:bg-sea-blue/90"
                                            onClick={addSelectedPoint}
                                        >
                                            <Anchor className="size-4" />
                                            Ajouter à l’itinéraire
                                        </Button>
                                    )}
                                </>
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    Cliquez sur la carte pour choisir une
                                    escale.
                                </p>
                            )}
                        </CardContent>
                    </Card>
                </section>
                <p className="text-xs text-muted-foreground">
                    Les conditions proviennent d’Open‑Meteo. Elles aident à
                    préparer une navigation, mais ne remplacent pas les
                    documents nautiques et avis officiels.
                </p>
            </div>
        </>
    );
}

function ConditionCard({
    icon: Icon,
    label,
    speed,
    direction,
    suffix,
    detail,
}: {
    icon: typeof Compass;
    label: string;
    speed?: number | null;
    direction?: number | null;
    suffix: string;
    detail?: string;
}) {
    const unavailable =
        speed === null ||
        speed === undefined ||
        direction === null ||
        direction === undefined;

    return (
        <div className="flex items-center gap-4 border-l-2 border-sea-blue/40 pl-4">
            <div className="flex size-11 items-center justify-center rounded-full bg-sea-blue/10 text-sea-blue">
                <Icon
                    className="size-5"
                    style={{ transform: `rotate(${direction ?? 0}deg)` }}
                />
            </div>
            <div>
                <p className="text-xs text-muted-foreground">{label}</p>
                <p className="mt-1 text-lg font-bold">
                    {unavailable
                        ? 'Indisponible'
                        : `${speed.toFixed(1)} ${suffix} · ${Math.round(direction)}°`}
                </p>
                {detail && (
                    <p className="text-xs text-muted-foreground">{detail}</p>
                )}
            </div>
        </div>
    );
}

DemoMap.layout = () => ({
    breadcrumbs: [{ title: 'Carte marine', href: appRoutes.map }],
});
