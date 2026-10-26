import { Head, Link, router } from '@inertiajs/react';
import AlertTriangle from 'lucide-react/icons/alert-triangle';
import Anchor from 'lucide-react/icons/anchor';
import ArrowRight from 'lucide-react/icons/arrow-right';
import Clock from 'lucide-react/icons/clock';
import MapPin from 'lucide-react/icons/map-pin';
import Route from 'lucide-react/icons/route';
import ShieldAlert from 'lucide-react/icons/shield-alert';
import Ship from 'lucide-react/icons/ship';
import Users from 'lucide-react/icons/users';
import Waves from 'lucide-react/icons/waves';
import { useEffect, useState } from 'react';
import CruiseChecklist from '@/components/mariwaze/cruise-checklist';
import type { Checklist } from '@/components/mariwaze/cruise-checklist';
import CruiseMap from '@/components/mariwaze/cruise-map';
import type { CruiseStop } from '@/components/mariwaze/cruise-map';
import HazardAlerts from '@/components/mariwaze/hazard-alerts';
import type { HazardAlert } from '@/components/mariwaze/planner-map';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useMariwazeRoutes } from '@/hooks/use-mariwaze-routes';
import { appRoutes } from '@/lib/mariwaze-routes';
import { cn } from '@/lib/utils';

type LegWeather = {
    label: string;
    detail: string;
    wind_knots: number | null;
    wave_height_meters: number | null;
    available: boolean;
    coverage_percent?: number;
} | null;

type Leg = {
    day: number;
    label: string;
    from: string;
    to: string;
    latitude: number | null;
    longitude: number | null;
    kind: 'port' | 'mouillage' | 'point';
    distance_nm: number;
    duration_hours: number | null;
    min_depth_meters: number | null;
    depth_warning: boolean;
    overnight: string | null;
    weather: LegWeather;
    note: string | null;
    anchorage_slug: string | null;
    port_slug: string | null;
};

type ScoreBreakdown = Record<
    string,
    { value: number; weight: number; points: number }
>;

type Scenario = {
    description: string | null;
    daily_target_hours: number | null;
    max_leg_hours: number | null;
    boat_context: string;
    weather_complete_legs: number;
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
    hazards: HazardAlert[];
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
    route_generation_status:
        'idle' | 'pending' | 'running' | 'completed' | 'failed';
    route_generation_error?: string | null;
};

type Props = {
    checklists: Checklist[];
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

export default function CruiseShow({ cruise, scenarios, checklists }: Props) {
    const routes = useMariwazeRoutes();
    const selected =
        scenarios.find((item) => item.selected) ?? scenarios[0] ?? null;
    const [active, setActive] = useState<string | null>(
        selected?.scenario ?? null,
    );
    const [generating, setGenerating] = useState(false);
    const generationInProgress = ['pending', 'running'].includes(
        cruise.route_generation_status,
    );

    useEffect(() => {
        if (!generationInProgress) {
            return;
        }

        const timer = window.setInterval(() => {
            router.reload({ only: ['cruise', 'scenarios'] });
        }, 2500);

        return () => window.clearInterval(timer);
    }, [generationInProgress]);

    const current =
        scenarios.find((item) => item.scenario === active) ?? selected;

    const generate = () => {
        setGenerating(true);
        router.post(
            `/croisieres/${cruise.slug}/itineraires`,
            {},
            { preserveScroll: true, onFinish: () => setGenerating(false) },
        );
    };

    const select = (scenario: string) => {
        router.put(
            `/croisieres/${cruise.slug}/itineraires/${scenario}`,
            {},
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head title={cruise.title} />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <Badge
                            variant="secondary"
                            className="mb-2 bg-sand/50 dark:bg-sand/20"
                        >
                            {cruise.status}
                        </Badge>
                        <h1 className="text-2xl font-bold md:text-3xl">
                            {cruise.title}
                        </h1>
                        <p className="mt-1 text-muted-foreground">
                            {cruise.route}
                        </p>
                        <div className="mt-3 flex flex-wrap gap-3 text-sm text-muted-foreground">
                            <span className="flex items-center gap-1">
                                <Users className="size-4" /> {cruise.crew}{' '}
                                personnes
                            </span>
                            <span>{cruise.style}</span>
                            <span>
                                {cruise.departure_date} → {cruise.return_date}
                            </span>
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button asChild variant="outline">
                            <a
                                href={`/croisieres/${cruise.slug}/carnet`}
                                download
                            >
                                Carnet hors connexion
                            </a>
                        </Button>
                        {selected && (
                            <Button asChild variant="outline">
                                <a
                                    href={`/croisieres/${cruise.slug}/export.gpx`}
                                    download
                                >
                                    Exporter le GPX retenu
                                </a>
                            </Button>
                        )}
                        <Button asChild variant="outline">
                            <Link href={routes.map}>Carte plein écran</Link>
                        </Button>
                        <Button asChild variant="outline">
                            <Link href={`/croisieres/${cruise.slug}/modifier`}>
                                Modifier
                            </Link>
                        </Button>
                        <Button
                            onClick={generate}
                            disabled={generating || generationInProgress}
                            className="bg-sea-blue hover:bg-sea-blue/90"
                        >
                            <Route className="size-4" />
                            {generationInProgress
                                ? 'Calcul en cours…'
                                : scenarios.length === 0
                                  ? 'Calculer les itinéraires'
                                  : 'Recalculer'}
                        </Button>
                    </div>
                </div>

                {cruise.duration_warning && (
                    <p className="flex items-center gap-2 rounded-lg border border-safety-orange/40 bg-safety-orange/5 px-4 py-3 text-sm text-safety-orange">
                        <AlertTriangle className="size-4 shrink-0" />
                        {cruise.duration_warning}
                    </p>
                )}

                {cruise.route_generation_error && (
                    <p className="flex items-center gap-2 rounded-lg border border-destructive/40 bg-destructive/5 px-4 py-3 text-sm text-destructive">
                        <AlertTriangle className="size-4 shrink-0" />
                        {cruise.route_generation_error}
                    </p>
                )}

                <CruiseMap
                    departure={cruise.departure_port}
                    destination={cruise.arrival_port}
                    stops={
                        current?.legs.map((leg): CruiseStop => ({
                            name: leg.to,
                            latitude: leg.latitude,
                            longitude: leg.longitude,
                            kind: leg.kind,
                            day: leg.day,
                        })) ?? []
                    }
                    routeGeometry={current?.geometry ?? []}
                    routeAvailable={(current?.geometry.length ?? 0) > 1}
                    hazards={current?.hazards ?? []}
                />

                {current && current.hazards.length > 0 && (
                    <Card className="border-border/60">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <ShieldAlert className="size-4 text-safety-orange" />
                                Vigilance sur le trajet —{' '}
                                {current.hazards.length} zone
                                {current.hazards.length > 1 ? 's' : ''}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <HazardAlerts hazards={current.hazards} />
                        </CardContent>
                    </Card>
                )}

                {scenarios.length === 0 ? (
                    <Card className="border-dashed border-border/60">
                        <CardContent className="flex flex-col items-center gap-3 py-12 text-center">
                            <Route className="size-8 text-muted-foreground" />
                            <p className="font-medium">
                                Aucun itinéraire calculé
                            </p>
                            <p className="max-w-md text-sm text-muted-foreground">
                                Le moteur compare trois scénarios — rapide,
                                confort et découverte — en tenant compte de la
                                météo, de votre bateau et des escales
                                disponibles.
                            </p>
                            <Button
                                onClick={generate}
                                disabled={generating || generationInProgress}
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
                                <article
                                    key={scenario.scenario}
                                    className={cn(
                                        'rounded-xl border p-4 text-left transition',
                                        scenario.scenario === current?.scenario
                                            ? 'border-sea-blue bg-sea-blue/5'
                                            : 'border-border/60 hover:border-sea-blue/50',
                                    )}
                                >
                                    <div className="flex items-start justify-between gap-2">
                                        <div>
                                            <button
                                                type="button"
                                                aria-pressed={
                                                    scenario.scenario ===
                                                    current?.scenario
                                                }
                                                onClick={() =>
                                                    setActive(scenario.scenario)
                                                }
                                                className="font-semibold underline-offset-4 hover:underline"
                                            >
                                                {scenario.label}
                                            </button>
                                            <p className="text-xs text-muted-foreground">
                                                {scenario.distance_nm} M ·{' '}
                                                {scenario.stops} escales ·{' '}
                                                {scenario.duration_days} j
                                            </p>
                                        </div>
                                        {scenario.score !== null && (
                                            <Badge className="bg-sea-blue/15 text-sea-blue dark:text-lagoon">
                                                {scenario.score}
                                            </Badge>
                                        )}
                                    </div>

                                    <p className="mt-3 text-sm">
                                        {scenario.description}
                                    </p>
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        Rythme visé :{' '}
                                        {scenario.daily_target_hours ?? '—'} h/j
                                        · Étape la plus longue :{' '}
                                        {scenario.max_leg_hours ?? '—'} h.
                                    </p>
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        Météo complète :{' '}
                                        {scenario.weather_complete_legs}/
                                        {scenario.legs.length} étapes.
                                    </p>

                                    {scenario.score_breakdown && (
                                        <div className="mt-3 space-y-1">
                                            {Object.entries(
                                                scenario.score_breakdown,
                                            ).map(([key, part]) => (
                                                <div
                                                    key={key}
                                                    className="flex items-center gap-2 text-xs"
                                                >
                                                    <span className="w-20 text-muted-foreground">
                                                        {breakdownLabels[key] ??
                                                            key}
                                                    </span>
                                                    <div className="h-1.5 flex-1 overflow-hidden rounded-full bg-muted">
                                                        <div
                                                            className="h-full rounded-full bg-sea-blue"
                                                            style={{
                                                                width: `${part.value}%`,
                                                            }}
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
                                        <button
                                            type="button"
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
                                        </button>
                                    )}
                                </article>
                            ))}
                        </section>

                        {current && (
                            <Card>
                                <CardHeader>
                                    <CardTitle
                                        role="heading"
                                        aria-level={2}
                                        className="text-base"
                                    >
                                        Comprendre cette proposition
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-2 text-sm">
                                    <p>{current.boat_context}</p>
                                    <p>
                                        La note compare les scénarios selon les
                                        critères ci-dessus. Chaque critère
                                        contribue à hauteur de son poids :{' '}
                                        {Object.entries(
                                            current.score_breakdown ?? {},
                                        )
                                            .map(
                                                ([key, part]) =>
                                                    `${breakdownLabels[key] ?? key} ${Math.round(part.weight * 100)} %`,
                                            )
                                            .join(', ')}
                                        .
                                    </p>
                                    {current.weather_complete_legs <
                                        current.legs.length && (
                                        <p className="text-safety-orange">
                                            La météo reste à confirmer sur
                                            certaines étapes. Une note globale
                                            élevée ne valide pas les données
                                            manquantes.
                                        </p>
                                    )}
                                    {current.max_leg_hours !== null &&
                                        current.daily_target_hours !== null &&
                                        current.max_leg_hours >
                                            current.daily_target_hours && (
                                            <p className="text-safety-orange">
                                                Une étape dépasse le rythme
                                                visé. Vérifiez les escales
                                                disponibles ou prévoyez
                                                davantage de temps.
                                            </p>
                                        )}
                                    <p className="text-xs text-muted-foreground">
                                        Calcul du{' '}
                                        {current.computed_at
                                            ? new Date(
                                                  current.computed_at,
                                              ).toLocaleString('fr-FR')
                                            : 'date inconnue'}
                                        . Les mesures absentes, les accès aux
                                        escales et les conditions au départ
                                        restent à vérifier.
                                    </p>
                                </CardContent>
                            </Card>
                        )}

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
                                                    <Badge variant="secondary">
                                                        {leg.label}
                                                    </Badge>
                                                    {leg.from} → {leg.to}
                                                </p>
                                                <p className="mt-1 flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
                                                    <span className="flex items-center gap-1">
                                                        <MapPin className="size-3" />
                                                        {leg.distance_nm} M
                                                    </span>
                                                    {leg.duration_hours !==
                                                        null && (
                                                        <span className="flex items-center gap-1">
                                                            <Clock className="size-3" />
                                                            {leg.duration_hours.toFixed(
                                                                1,
                                                            )}{' '}
                                                            h
                                                        </span>
                                                    )}
                                                    {leg.overnight && (
                                                        <span className="flex items-center gap-1">
                                                            {leg.overnight ===
                                                            'Mouillage' ? (
                                                                <Anchor className="size-3" />
                                                            ) : (
                                                                <Ship className="size-3" />
                                                            )}
                                                            {leg.overnight}
                                                        </span>
                                                    )}
                                                    {leg.min_depth_meters !==
                                                        null && (
                                                        <span
                                                            className={cn(
                                                                'flex items-center gap-1',
                                                                leg.depth_warning &&
                                                                    'font-semibold text-destructive',
                                                            )}
                                                            title="Profondeur minimale sur l'étape, au zéro des cartes (EMODnet, hors marée)"
                                                        >
                                                            <Waves className="size-3" />
                                                            min{' '}
                                                            {leg.min_depth_meters.toFixed(
                                                                1,
                                                            )}{' '}
                                                            m
                                                        </span>
                                                    )}
                                                </p>
                                            </div>

                                            {leg.weather && (
                                                <div className="text-left sm:text-right">
                                                    <p className="text-sm font-medium">
                                                        {leg.weather.label}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {leg.weather.detail}
                                                    </p>
                                                </div>
                                            )}
                                        </div>

                                        {leg.depth_warning &&
                                            leg.min_depth_meters !== null && (
                                                <p className="mt-2 flex items-center gap-1.5 text-xs font-medium text-destructive">
                                                    <AlertTriangle className="size-3 shrink-0" />
                                                    Passage à ~
                                                    {leg.min_depth_meters.toFixed(
                                                        1,
                                                    )}{' '}
                                                    m de fond : juste pour votre
                                                    tirant d&apos;eau (marge 0,5
                                                    m). Vérifiez marées et
                                                    cartographie officielle.
                                                </p>
                                            )}

                                        {leg.note && (
                                            <p className="mt-2 text-xs text-muted-foreground">
                                                {leg.note}
                                            </p>
                                        )}

                                        {(leg.anchorage_slug ||
                                            leg.port_slug) && (
                                            <Link
                                                href={
                                                    leg.anchorage_slug
                                                        ? appRoutes.anchorage(
                                                              leg.anchorage_slug,
                                                          )
                                                        : appRoutes.port(
                                                              leg.port_slug as string,
                                                          )
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
                <p className="text-sm text-muted-foreground">
                    Le carnet contient une copie datée des étapes et de la
                    checklist, consultable sans réseau. Le GPX exporte
                    l’itinéraire retenu.
                </p>
                <CruiseChecklist slug={cruise.slug} checklists={checklists} />
            </div>
        </>
    );
}

CruiseShow.layout = (props: Props) => ({
    breadcrumbs: [
        { title: 'Mes croisières', href: appRoutes.cruises },
        {
            title: props.cruise.title,
            href: appRoutes.cruise(props.cruise.slug),
        },
    ],
});
