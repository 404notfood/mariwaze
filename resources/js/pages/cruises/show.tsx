import { Head, Link, router } from '@inertiajs/react';
import { AlertTriangle, Anchor, ArrowRight, Clock, MapPin, Route, Ship, Users } from 'lucide-react';
import { useState } from 'react';
import CruiseMap from '@/components/mariwaze/cruise-map';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { appRoutes } from '@/lib/mariwaze-routes';
import { useMariwazeRoutes } from '@/hooks/use-mariwaze-routes';

type LegWeather = {
    label: string;
    detail: string;
    wind_knots: number | null;
    wave_height_meters: number | null;
    available: boolean;
} | null;

type Leg = {
    day: number;
    label: string;
    from: string;
    to: string;
    distance_nm: number;
    duration_hours: number | null;
    overnight: string | null;
    weather: LegWeather;
    note: string | null;
    anchorage_slug: string | null;
    port_slug: string | null;
};

type ScoreBreakdown = Record<string, { value: number; weight: number; points: number }>;

type Scenario = {
    scenario: string;
    label: string;
    selected: boolean;
    score: number | null;
    score_breakdown: ScoreBreakdown | null;
    distance_nm: number;
    duration_days: number;
    stops: number;
    geometry: Array<{ latitude: number; longitude: number }>;
    computed_at: string | null;
    legs: Leg[];
};

type Cruise = {
    slug: string;
    title: string;
    route: string;
    departure_date: string;
    return_date: string;
    duration: string;
    status: string;
    style: string;
    crew: number;
    departure_port: string;
    arrival_port: string;
    duration_warning?: string | null;
};

type Props = {
    cruise: Cruise;
    scenarios: Scenario[];
};

const breakdownLabels: Record<string, string> = {
    weather: 'Météo',
    boat: 'Bateau',
    safety: 'Sécurité',
    comfort: 'Confort',
    community: 'Communauté',
};

export default function CruiseShow({ cruise, scenarios }: Props) {
    const routes = useMariwazeRoutes();
    const selected = scenarios.find((item) => item.selected) ?? scenarios[0] ?? null;
    const [active, setActive] = useState<string | null>(selected?.scenario ?? null);
    const [generating, setGenerating] = useState(false);

    const current = scenarios.find((item) => item.scenario === active) ?? selected;

    const generate = () => {
        setGenerating(true);
        router.post(
            `/croisieres/${cruise.slug}/itineraires`,
            {},
            { preserveScroll: true, onFinish: () => setGenerating(false) },
        );
    };

    const select = (scenario: string) => {
        router.put(`/croisieres/${cruise.slug}/itineraires/${scenario}`, {}, { preserveScroll: true });
    };

    return (
        <>
            <Head title={cruise.title} />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <Badge variant="secondary" className="mb-2 bg-sand/50 dark:bg-sand/20">
                            {cruise.status}
                        </Badge>
                        <h1 className="text-2xl font-bold md:text-3xl">{cruise.title}</h1>
                        <p className="mt-1 text-muted-foreground">{cruise.route}</p>
                        <div className="mt-3 flex flex-wrap gap-3 text-sm text-muted-foreground">
                            <span className="flex items-center gap-1">
                                <Users className="size-4" /> {cruise.crew} personnes
                            </span>
                            <span>{cruise.style}</span>
                            <span>
                                {cruise.departure_date} → {cruise.return_date}
                            </span>
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button asChild variant="outline">
                            <Link href={routes.map}>Carte plein écran</Link>
                        </Button>
                        <Button asChild variant="outline">
                            <Link href={`/croisieres/${cruise.slug}/modifier`}>Modifier</Link>
                        </Button>
                        <Button
                            onClick={generate}
                            disabled={generating}
                            className="bg-sea-blue hover:bg-sea-blue/90"
                        >
                            <Route className="size-4" />
                            {scenarios.length === 0 ? 'Calculer les itinéraires' : 'Recalculer'}
                        </Button>
                    </div>
                </div>

                {cruise.duration_warning && (
                    <p className="flex items-center gap-2 rounded-lg border border-safety-orange/40 bg-safety-orange/5 px-4 py-3 text-sm text-safety-orange">
                        <AlertTriangle className="size-4 shrink-0" />
                        {cruise.duration_warning}
                    </p>
                )}

                <CruiseMap
                    departure={cruise.departure_port}
                    destination={cruise.arrival_port}
                    waypoints={current?.legs.map((leg) => leg.to) ?? []}
                    routeGeometry={current?.geometry ?? []}
                    routeAvailable={(current?.geometry.length ?? 0) > 1}
                />

                {scenarios.length === 0 ? (
                    <Card className="border-dashed border-border/60">
                        <CardContent className="flex flex-col items-center gap-3 py-12 text-center">
                            <Route className="size-8 text-muted-foreground" />
                            <p className="font-medium">Aucun itinéraire calculé</p>
                            <p className="max-w-md text-sm text-muted-foreground">
                                Le moteur compare trois scénarios — rapide, confort et découverte — en tenant
                                compte de la météo, de votre bateau et des escales disponibles.
                            </p>
                            <Button
                                onClick={generate}
                                disabled={generating}
                                className="bg-sea-blue hover:bg-sea-blue/90"
                            >
                                Calculer les itinéraires
                                <ArrowRight className="size-4" />
                            </Button>
                        </CardContent>
                    </Card>
                ) : (
                    <>
                        <section className="grid gap-3 md:grid-cols-3">
                            {scenarios.map((scenario) => (
                                <button
                                    key={scenario.scenario}
                                    type="button"
                                    onClick={() => setActive(scenario.scenario)}
                                    className={cn(
                                        'rounded-xl border p-4 text-left transition',
                                        scenario.scenario === active
                                            ? 'border-sea-blue bg-sea-blue/5'
                                            : 'border-border/60 hover:border-sea-blue/50',
                                    )}
                                >
                                    <div className="flex items-start justify-between gap-2">
                                        <div>
                                            <p className="font-semibold">{scenario.label}</p>
                                            <p className="text-xs text-muted-foreground">
                                                {scenario.distance_nm} M · {scenario.stops} escales ·{' '}
                                                {scenario.duration_days} j
                                            </p>
                                        </div>
                                        {scenario.score !== null && (
                                            <Badge className="bg-sea-blue/15 text-sea-blue dark:text-lagoon">
                                                {scenario.score}
                                            </Badge>
                                        )}
                                    </div>

                                    {scenario.score_breakdown && (
                                        <div className="mt-3 space-y-1">
                                            {Object.entries(scenario.score_breakdown).map(([key, part]) => (
                                                <div key={key} className="flex items-center gap-2 text-xs">
                                                    <span className="w-20 text-muted-foreground">
                                                        {breakdownLabels[key] ?? key}
                                                    </span>
                                                    <div className="h-1.5 flex-1 overflow-hidden rounded-full bg-muted">
                                                        <div
                                                            className="h-full rounded-full bg-sea-blue"
                                                            style={{ width: `${part.value}%` }}
                                                        />
                                                    </div>
                                                    <span className="w-7 text-right text-muted-foreground">
                                                        {part.value}
                                                    </span>
                                                </div>
                                            ))}
                                        </div>
                                    )}

                                    {scenario.selected ? (
                                        <p className="mt-3 text-xs font-medium text-sea-blue">
                                            Itinéraire retenu
                                        </p>
                                    ) : (
                                        <span
                                            role="button"
                                            tabIndex={0}
                                            onClick={(event) => {
                                                event.stopPropagation();
                                                select(scenario.scenario);
                                            }}
                                            onKeyDown={(event) => {
                                                if (event.key === 'Enter') {
                                                    event.stopPropagation();
                                                    select(scenario.scenario);
                                                }
                                            }}
                                            className="mt-3 inline-block text-xs font-medium text-muted-foreground underline hover:text-sea-blue"
                                        >
                                            Retenir cet itinéraire
                                        </span>
                                    )}
                                </button>
                            ))}
                        </section>

                        <Card className="border-border/60">
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Étapes — {current?.label ?? ''}
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {current?.legs.map((leg) => (
                                    <div
                                        key={`${leg.day}-${leg.to}`}
                                        className="rounded-xl border border-border/60 p-4"
                                    >
                                        <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                            <div>
                                                <p className="flex items-center gap-2 font-medium">
                                                    <Badge variant="secondary">{leg.label}</Badge>
                                                    {leg.from} → {leg.to}
                                                </p>
                                                <p className="mt-1 flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
                                                    <span className="flex items-center gap-1">
                                                        <MapPin className="size-3" />
                                                        {leg.distance_nm} M
                                                    </span>
                                                    {leg.duration_hours !== null && (
                                                        <span className="flex items-center gap-1">
                                                            <Clock className="size-3" />
                                                            {leg.duration_hours.toFixed(1)} h
                                                        </span>
                                                    )}
                                                    {leg.overnight && (
                                                        <span className="flex items-center gap-1">
                                                            {leg.overnight === 'Mouillage' ? (
                                                                <Anchor className="size-3" />
                                                            ) : (
                                                                <Ship className="size-3" />
                                                            )}
                                                            {leg.overnight}
                                                        </span>
                                                    )}
                                                </p>
                                            </div>

                                            {leg.weather && (
                                                <div className="text-left sm:text-right">
                                                    <p className="text-sm font-medium">{leg.weather.label}</p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {leg.weather.detail}
                                                    </p>
                                                </div>
                                            )}
                                        </div>

                                        {leg.note && (
                                            <p className="mt-2 text-xs text-muted-foreground">{leg.note}</p>
                                        )}

                                        {(leg.anchorage_slug || leg.port_slug) && (
                                            <Link
                                                href={
                                                    leg.anchorage_slug
                                                        ? appRoutes.anchorage(leg.anchorage_slug)
                                                        : appRoutes.port(leg.port_slug as string)
                                                }
                                                className="mt-2 inline-flex items-center gap-1 text-xs font-medium text-sea-blue"
                                            >
                                                Voir la fiche escale
                                                <ArrowRight className="size-3" />
                                            </Link>
                                        )}
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                    </>
                )}
            </div>
        </>
    );
}

CruiseShow.layout = (props: Props) => ({
    breadcrumbs: [
        { title: 'Mes croisières', href: appRoutes.cruises },
        { title: props.cruise.title, href: appRoutes.cruise(props.cruise.slug) },
    ],
});
