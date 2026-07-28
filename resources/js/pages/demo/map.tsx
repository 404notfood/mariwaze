import { Head, Link } from '@inertiajs/react';
import { useCallback, useEffect, useMemo, useState } from 'react';
import { Anchor, ArrowRight, Compass, MapPin, Wind } from 'lucide-react';
import PlannerMap, { type NavigationPoint } from '@/components/mariwaze/planner-map';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { mariwazeRoutesForPreview } from '@/lib/mariwaze-routes';

type Route = { slug: string; title: string; points: NavigationPoint[]; geometry?: Array<Pick<NavigationPoint, 'latitude' | 'longitude'>>; available?: boolean } | null;
type MarineConditions = {
    observed_at: string | null;
    current: { speed_knots: number | null; direction_degrees: number | null };
    wind: { speed_knots: number | null; direction_degrees: number | null; gusts_knots: number | null };
};

export default function DemoMap({ route, conditions: initialConditions = null }: { route: Route; conditions?: MarineConditions | null }) {
    const [addedPoints, setAddedPoints] = useState<NavigationPoint[]>([]);
    const [selectedPoint, setSelectedPoint] = useState<NavigationPoint | null>(route?.points[0] ?? null);
    const [conditions, setConditions] = useState<MarineConditions | null>(initialConditions);
    const points = useMemo(() => [...(route?.points ?? []), ...addedPoints], [route, addedPoints]);
    const selectPoint = useCallback((point: NavigationPoint) => setSelectedPoint(point), []);

    useEffect(() => {
        if (!selectedPoint) return;
        if (selectedPoint === route?.points[0] && initialConditions) return;
        const controller = new AbortController();
        fetch(`/api/marine/conditions?latitude=${selectedPoint.latitude}&longitude=${selectedPoint.longitude}`, { signal: controller.signal })
            .then((response) => response.ok ? response.json() as Promise<MarineConditions> : null)
            .then(setConditions)
            .catch(() => undefined);
        return () => controller.abort();
    }, [selectedPoint]);

    const addSelectedPoint = () => {
        if (selectedPoint && selectedPoint.kind === 'point') setAddedPoints((current) => [...current, selectedPoint]);
    };

    return <>
        <Head title="Carte de préparation" />
        <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
            <header className="flex flex-col gap-3 border-b border-border/60 pb-5 md:flex-row md:items-end md:justify-between">
                <div><p className="text-sm text-muted-foreground">Carte de préparation</p><h1 className="mt-1 text-2xl font-bold md:text-3xl">Itinéraire, vent et courants</h1></div>
                {route ? <Button asChild className="bg-sea-blue hover:bg-sea-blue/90"><Link href={`/croisieres/${route.slug}`}>Voir la croisière<ArrowRight className="size-4" /></Link></Button> : <Button asChild className="bg-sea-blue hover:bg-sea-blue/90"><Link href="/croisieres/nouvelle">Créer une croisière<ArrowRight className="size-4" /></Link></Button>}
            </header>

            {route && <div className="flex flex-wrap items-center gap-2"><Badge variant="secondary">{route.title}</Badge><span className="text-sm text-muted-foreground">{points.length} points d’itinéraire</span></div>}
            <section className="overflow-hidden border border-border/60"><PlannerMap points={points} routeGeometry={route?.geometry} routeAvailable={route?.available} onMapClick={selectPoint} marine={{ point: selectedPoint, current: conditions?.current, wind: conditions?.wind }} /></section>

            <section className="grid gap-4 lg:grid-cols-3">
                <Card className="border-border/60 lg:col-span-2"><CardContent className="grid gap-4 pt-6 sm:grid-cols-2">
                    <ConditionCard icon={Compass} label="Courant" speed={conditions?.current.speed_knots} direction={conditions?.current.direction_degrees} suffix="nd" />
                    <ConditionCard icon={Wind} label="Vent à 10 m" speed={conditions?.wind.speed_knots} direction={conditions?.wind.direction_degrees} suffix="nd" detail={conditions?.wind.gusts_knots ? `Rafales ${conditions.wind.gusts_knots.toFixed(1)} nd` : undefined} />
                </CardContent></Card>
                <Card className="border-border/60"><CardContent className="space-y-3 pt-6">
                    <p className="flex items-center gap-2 font-semibold"><MapPin className="size-4 text-sea-blue" />Point sélectionné</p>
                    {selectedPoint ? <><p className="text-sm">{selectedPoint.name}</p><p className="text-xs text-muted-foreground">{selectedPoint.latitude.toFixed(4)}, {selectedPoint.longitude.toFixed(4)}</p>{selectedPoint.kind === 'point' && <Button className="w-full bg-sea-blue hover:bg-sea-blue/90" onClick={addSelectedPoint}><Anchor className="size-4" />Ajouter à l’itinéraire</Button>}</> : <p className="text-sm text-muted-foreground">Cliquez sur la carte pour choisir une escale.</p>}
                </CardContent></Card>
            </section>
            <p className="text-xs text-muted-foreground">Les conditions proviennent d’Open‑Meteo. Elles aident à préparer une navigation, mais ne remplacent pas les documents nautiques et avis officiels.</p>
        </div>
    </>;
}

function ConditionCard({ icon: Icon, label, speed, direction, suffix, detail }: { icon: typeof Compass; label: string; speed?: number | null; direction?: number | null; suffix: string; detail?: string }) {
    const unavailable = speed === null || speed === undefined || direction === null || direction === undefined;
    return <div className="flex items-center gap-4 border-l-2 border-sea-blue/40 pl-4"><div className="flex size-11 items-center justify-center rounded-full bg-sea-blue/10 text-sea-blue"><Icon className="size-5" style={{ transform: `rotate(${direction ?? 0}deg)` }} /></div><div><p className="text-xs text-muted-foreground">{label}</p><p className="mt-1 text-lg font-bold">{unavailable ? 'Indisponible' : `${speed.toFixed(1)} ${suffix} · ${Math.round(direction)}°`}</p>{detail && <p className="text-xs text-muted-foreground">{detail}</p>}</div></div>;
}

DemoMap.layout = () => ({ breadcrumbs: [{ title: 'Carte marine', href: mariwazeRoutesForPreview(false).map }] });
