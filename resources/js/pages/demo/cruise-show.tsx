import { Head, Link } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowRight,
    CheckCircle2,
    Clock,
    CircleCheck,
    MapPin,
    Route,
    Sun,
    Users,
    Wind,
} from 'lucide-react';
import CruiseMap from '@/components/mariwaze/cruise-map';
import PreviewBanner from '@/components/mariwaze/preview-banner';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { mariwazeRoutesForPreview } from '@/lib/mariwaze-routes';
import { useMariwazeRoutes } from '@/hooks/use-mariwaze-routes';

type Leg = {
    day: number;
    label: string;
    from: string;
    to: string;
    distance: string;
    duration: string;
    departure: string;
    weather: string;
    weather_detail: string;
    status: string;
    anchorage_slug: string | null;
    overnight?: string;
    estimated_cost?: string;
    why?: string;
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
    experience: string;
    progress: number;
    miles: number;
    stops: number;
    readiness: number;
    departure_port: string;
    arrival_port: string;
    scenarios: { id: string; label: string; active: boolean; miles: number; stops: number; days: number }[];
    plan_b: {
        issue: string;
        original: string;
        alternative: string;
        difference: string;
        reasons: string[];
    };
    legs: Leg[];
    budget?: {
        ports: number;
        fuel: number;
        provisions: number;
        total: number;
        note: string;
    };
    duration_warning?: string | null;
    route_geometry?: Array<{ latitude: number; longitude: number }>;
    route_available?: boolean;
};

type Props = {
    cruise: Cruise;
    preview?: boolean;
};

export default function DemoCruiseShow({ cruise, preview = false }: Props) {
    const routes = useMariwazeRoutes(preview);

    return (
        <>
            <Head title={cruise.title} />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PreviewBanner preview={preview} />

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
                            <span>{cruise.experience}</span>
                            <span>{cruise.departure_date} → {cruise.return_date}</span>
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <Button asChild variant="outline">
                            <Link href={routes.map}>Carte plein écran</Link>
                        </Button>
                        <Button asChild className="bg-sea-blue hover:bg-sea-blue/90">
                            <Link href={`/croisieres/${cruise.slug}/modifier`}>Modifier l&apos;itinéraire</Link>
                        </Button>
                    </div>
                </div>

                <CruiseMap
                    departure={cruise.departure_port}
                    destination={cruise.arrival_port}
                    waypoints={cruise.legs.map((leg) => leg.to)}
                    routeGeometry={cruise.route_geometry}
                    routeAvailable={cruise.route_available}
                />

                {cruise.duration_warning && (
                    <div className="border border-safety-orange/40 bg-safety-orange/10 px-4 py-3 text-sm text-foreground">
                        <strong>Durée à ajuster.</strong> {cruise.duration_warning}
                    </div>
                )}

                <div className="grid gap-4 md:grid-cols-3">
                    {cruise.scenarios.map((scenario) => (
                        <Card
                            key={scenario.id}
                            className={cn(
                                'border-border/60 transition-shadow',
                                scenario.active && 'ring-2 ring-sea-blue/50',
                            )}
                        >
                            <CardHeader className="pb-2">
                                <CardTitle className="text-base">{scenario.label}</CardTitle>
                            </CardHeader>
                            <CardContent className="text-sm text-muted-foreground">
                                <p>{scenario.miles} M · {scenario.stops} escales · {scenario.days} j</p>
                                {scenario.active && (
                                    <Badge className="mt-2 bg-sea-blue">Sélectionné</Badge>
                                )}
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <Card className="border-safety-orange/30 bg-safety-orange/[0.04]">
                    <CardHeader className="pb-3">
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Route className="size-4 text-safety-orange" />
                            Plan B météo proposé
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-5 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,1fr)_minmax(0,1.2fr)]">
                        <div>
                            <p className="text-sm text-muted-foreground">{cruise.plan_b.issue}</p>
                            <p className="mt-3 text-sm font-semibold">Itinéraire prévu</p>
                            <p className="text-sm text-muted-foreground">{cruise.plan_b.original}</p>
                        </div>
                        <div className="rounded-lg border border-green-600/20 bg-green-500/10 p-4">
                            <p className="text-xs font-semibold tracking-wide text-green-700 uppercase dark:text-green-400">Alternative conseillée</p>
                            <p className="mt-1 font-semibold">{cruise.plan_b.alternative}</p>
                            <p className="mt-1 text-sm text-muted-foreground">{cruise.plan_b.difference}</p>
                        </div>
                        <div className="space-y-2">
                            {cruise.plan_b.reasons.map((reason) => (
                                <p key={reason} className="flex gap-2 text-sm text-muted-foreground">
                                    <CircleCheck className="mt-0.5 size-4 shrink-0 text-green-600" />
                                    {reason}
                                </p>
                            ))}
                        </div>
                    </CardContent>
                </Card>

                <Card className="border-border/60">
                    <CardHeader>
                        <CardTitle>Étapes de navigation</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {cruise.legs.map((leg) => (
                            <div
                                key={leg.day}
                                className={cn(
                                    'rounded-xl border p-4',
                                    leg.status === 'current'
                                        ? 'border-sea-blue/40 bg-sea-blue/5'
                                        : 'border-border/60',
                                )}
                            >
                                <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                    <div>
                                        <div className="mb-1 flex items-center gap-2">
                                            <Badge variant="outline">{leg.label}</Badge>
                                            {leg.status === 'done' && (
                                                <CheckCircle2 className="size-4 text-green-600" />
                                            )}
                                            {leg.status === 'current' && (
                                                <Badge className="bg-sea-blue">En cours</Badge>
                                            )}
                                            {leg.weather === 'Attention' && (
                                                <AlertTriangle className="size-4 text-safety-orange" />
                                            )}
                                        </div>
                                        <p className="font-semibold">
                                            {leg.from} → {leg.to}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {leg.distance} · {leg.duration} · Départ {leg.departure}
                                        </p>
                                    </div>
                                    <div className="rounded-lg bg-muted/50 px-3 py-2 text-sm">
                                        <p className="flex items-center gap-1 font-medium">
                                            <Wind className="size-3.5" /> {leg.weather}
                                        </p>
                                        <p className="text-muted-foreground">{leg.weather_detail}</p>
                                    </div>
                                </div>
                                {leg.overnight && (
                                    <div className="mt-4 grid gap-3 border-t border-border/60 pt-3 text-sm sm:grid-cols-3">
                                        <div>
                                            <p className="text-xs text-muted-foreground">Nuitée</p>
                                            <p className="font-medium">{leg.overnight}</p>
                                        </div>
                                        <div>
                                            <p className="text-xs text-muted-foreground">Tarif estimatif</p>
                                            <p className="font-medium">{leg.estimated_cost}</p>
                                        </div>
                                        <div>
                                            <p className="text-xs text-muted-foreground">Pourquoi cette escale</p>
                                            <p className="font-medium">{leg.why}</p>
                                        </div>
                                    </div>
                                )}
                                {leg.anchorage_slug && (
                                    <Button asChild variant="link" className="mt-2 h-auto p-0 text-sea-blue">
                                        <Link href={routes.anchorage(leg.anchorage_slug)}>
                                            Voir la fiche mouillage →
                                        </Link>
                                    </Button>
                                )}
                            </div>
                        ))}
                    </CardContent>
                </Card>

                {cruise.budget && (
                    <section className="border-y border-border/60 py-5">
                        <div className="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:justify-between">
                            <h2 className="text-lg font-semibold">Budget prévisionnel</h2>
                            <p className="text-sm text-muted-foreground">Pour l&apos;itinéraire sélectionné</p>
                        </div>
                        <div className="mt-4 grid grid-cols-2 gap-4 md:grid-cols-4">
                            <BudgetItem label="Ports" value={cruise.budget.ports} />
                            <BudgetItem label="Carburant" value={cruise.budget.fuel} />
                            <BudgetItem label="Avitaillement" value={cruise.budget.provisions} />
                            <BudgetItem label="Total estimatif" value={cruise.budget.total} total />
                        </div>
                        <p className="mt-4 text-xs text-muted-foreground">{cruise.budget.note}</p>
                    </section>
                )}

                <div className="grid gap-4 md:grid-cols-3">
                    <StatCard icon={MapPin} label="Distance totale" value={`${cruise.miles} milles`} />
                    <StatCard icon={Clock} label="Escales prévues" value={String(cruise.stops)} />
                    <StatCard icon={Sun} label="Prêt au départ" value={`${cruise.readiness}%`} />
                </div>
            </div>
        </>
    );
}

function BudgetItem({ label, value, total = false }: { label: string; value: number; total?: boolean }) {
    return (
        <div className={cn('border-l-2 border-sea-blue/30 pl-3', total && 'border-sea-blue')}>
            <p className="text-xs text-muted-foreground">{label}</p>
            <p className="mt-1 text-lg font-bold">{value} €</p>
        </div>
    );
}

function StatCard({
    icon: Icon,
    label,
    value,
}: {
    icon: typeof MapPin;
    label: string;
    value: string;
}) {
    return (
        <Card className="border-border/60">
            <CardContent className="flex items-center gap-4 pt-6">
                <div className="flex size-10 items-center justify-center rounded-xl bg-sea-blue/10 text-sea-blue">
                    <Icon className="size-5" />
                </div>
                <div>
                    <p className="text-xs text-muted-foreground">{label}</p>
                    <p className="text-lg font-bold">{value}</p>
                </div>
            </CardContent>
        </Card>
    );
}

DemoCruiseShow.layout = (props: Props) => {
    const routes = mariwazeRoutesForPreview(!!props.preview);

    return {
        breadcrumbs: [
            { title: 'Mes croisières', href: routes.cruises },
            { title: props.cruise.title, href: routes.cruise(props.cruise.slug) },
        ],
    };
};
