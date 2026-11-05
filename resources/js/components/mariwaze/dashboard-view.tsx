import { Link } from '@inertiajs/react';
import AlertTriangle from 'lucide-react/icons/alert-triangle';
import ArrowRight from 'lucide-react/icons/arrow-right';
import Plus from 'lucide-react/icons/plus';
import Route from 'lucide-react/icons/route';
import CruiseMap from '@/components/mariwaze/cruise-map';
import {
    Chip,
    Instrument,
    SegmentBar,
    toneText,
} from '@/components/mariwaze/passerelle';
import type { Tone } from '@/components/mariwaze/passerelle';
import { Button } from '@/components/ui/button';
import { useDesign } from '@/hooks/use-design';
import { useMariwazeRoutes } from '@/hooks/use-mariwaze-routes';
import { cn } from '@/lib/utils';

export type DashboardLeg = {
    day: number;
    from: string;
    to: string;
    distance_nm: number;
    duration_hours: number | null;
    overnight: string | null;
    weather: {
        label: string;
        detail: string;
        wind_knots: number | null;
        wave_height_meters: number | null;
        available: boolean;
    } | null;
};

export type DashboardData = {
    boat: {
        name: string;
        model: string | null;
        type: string;
        draft: number;
        mast_height: number | null;
    } | null;
    cruise: {
        slug: string;
        title: string;
        departure_port: string;
        destination_port: string;
        departure_date: string | null;
        duration_days: number;
        crew_count: number;
        style: string;
        status: string;
        route: {
            scenario: string;
            label: string;
            score: number | null;
            distance_nm: number;
            stops: number;
            geometry: Array<{ latitude: number; longitude: number }>;
            available: boolean;
            computed_at: string | null;
        } | null;
        next_legs: DashboardLeg[];
    } | null;
    readiness: number;
    readiness_label: string;
    missing_equipment: string[];
    alerts: {
        id: string;
        type: string;
        message: string;
        created_at: string | null;
    }[];
};

/** Classe l'avis météo d'une étape à partir de son libellé ; le mot reste affiché. */
export function weatherTone(weather: DashboardLeg['weather']): Tone {
    if (!weather || !weather.available) {
        return 'muted';
    }

    const label = weather.label
        .normalize('NFD')
        .replace(/\p{Diacritic}/gu, '')
        .toLowerCase();

    if (/defavorable|deconseill|dangereu|interdit/.test(label)) {
        return 'bad';
    }

    if (/surveill|modere|prudence|limite|attention/.test(label)) {
        return 'warn';
    }

    if (/favorable|calme|bon/.test(label)) {
        return 'good';
    }

    return 'muted';
}

const nf = new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 1 });

export function formatMiles(value: number): string {
    return nf.format(value >= 100 ? Math.round(value) : value);
}

export function formatHours(hours: number | null): string | null {
    if (hours === null) {
        return null;
    }

    const h = Math.floor(hours);
    const m = Math.round((hours - h) * 60);

    return m === 0 ? `${h} h` : `${h} h ${String(m).padStart(2, '0')}`;
}

export default function DashboardView({ data }: { data: DashboardData }) {
    const routes = useMariwazeRoutes();
    const { design } = useDesign();
    const { boat, cruise, alerts } = data;
    const nextLeg = cruise?.next_legs[0] ?? null;

    if (design === 'large') {
        return <DashboardLarge data={data} />;
    }

    return (
        <div className="flex flex-1 flex-col">
            {/* Bande d'instruments */}
            <header className="flex flex-col border-b border-border lg:flex-row lg:items-stretch">
                <div className="border-border px-5 py-3 lg:min-w-[300px] lg:border-r">
                    <p className="pw-label">
                        {cruise
                            ? `Croisière · ${cruise.status}`
                            : 'Préparation de navigation'}
                    </p>
                    <h1 className="text-[22px] leading-7 font-semibold tracking-[0.02em] uppercase">
                        {cruise
                            ? `${cruise.departure_port} → ${cruise.destination_port}`
                            : 'Tableau de bord'}
                    </h1>
                    {cruise && (
                        <div className="mt-1.5 flex flex-wrap gap-1">
                            <Chip accent>
                                {cruise.route?.label ?? 'Itinéraire à calculer'}
                            </Chip>
                            {cruise.route?.score !== null &&
                                cruise.route?.score !== undefined && (
                                    <Chip>Score {cruise.route.score}</Chip>
                                )}
                            <Chip>{cruise.style}</Chip>
                            <Chip>{cruise.crew_count} pax</Chip>
                        </div>
                    )}
                </div>

                {cruise && (
                    <div className="grid flex-1 grid-cols-2 sm:grid-cols-4">
                        <Instrument
                            className="border-t border-r border-border lg:border-t-0"
                            label="Départ"
                            value={cruise.departure_date ?? 'À définir'}
                        />
                        <Instrument
                            className="border-t border-r border-border lg:border-t-0"
                            label="Distance"
                            value={
                                cruise.route
                                    ? formatMiles(cruise.route.distance_nm)
                                    : '—'
                            }
                            unit={cruise.route ? 'M' : undefined}
                        />
                        <Instrument
                            className="border-t border-r border-border lg:border-t-0"
                            label="Escales"
                            value={
                                cruise.route ? String(cruise.route.stops) : '—'
                            }
                        />
                        <Instrument
                            className="border-t border-r border-border lg:border-t-0"
                            label="Durée"
                            value={String(cruise.duration_days)}
                            unit="j"
                        />
                    </div>
                )}

                <div className="flex items-center px-5 py-3 lg:ml-auto">
                    <Button asChild>
                        <Link href="/croisieres/nouvelle">
                            <Plus className="size-4" />
                            Croisière
                        </Link>
                    </Button>
                </div>
            </header>

            {alerts.length > 0 && (
                <section className="divide-y divide-border border-b border-border">
                    {alerts.map((alert) => (
                        <div
                            key={alert.id}
                            className="flex items-start gap-3 bg-warn-soft/60 px-5 py-2.5"
                        >
                            <AlertTriangle className="mt-0.5 size-4 shrink-0 text-warn" />
                            <p className="flex-1">{alert.message}</p>
                            {alert.created_at && (
                                <span className="pw-label">
                                    {alert.created_at}
                                </span>
                            )}
                        </div>
                    ))}
                </section>
            )}

            {cruise ? (
                <div className="grid flex-1 gap-px bg-border xl:grid-cols-[minmax(0,1fr)_340px]">
                    <div className="flex min-w-0 flex-col gap-px bg-border">
                        <div className="relative min-h-[420px] flex-1 bg-background">
                            <CruiseMap
                                departure={cruise.departure_port}
                                destination={cruise.destination_port}
                                routeGeometry={cruise.route?.geometry ?? []}
                                routeAvailable={
                                    cruise.route?.available ?? false
                                }
                                className="absolute inset-0 overflow-hidden bg-muted"
                            />
                        </div>

                        {cruise.next_legs.length > 0 && (
                            <div className="grid gap-px bg-border sm:grid-cols-2 lg:grid-cols-5">
                                {cruise.next_legs.slice(0, 5).map((leg, i) => (
                                    <LegTile
                                        key={`${leg.day}-${leg.to}`}
                                        leg={leg}
                                        current={i === 0}
                                    />
                                ))}
                            </div>
                        )}
                    </div>

                    <aside className="flex flex-col bg-background">
                        {nextLeg && <NextLegPanel leg={nextLeg} />}

                        <section className="border-b border-border px-5 py-4">
                            <h2 className="pw-title">Préparation</h2>
                            <div className="mt-3 flex items-center gap-3">
                                <span className="pw-figure text-[28px] leading-none">
                                    {data.readiness}%
                                </span>
                                <SegmentBar
                                    value={data.readiness}
                                    className="h-3.5"
                                />
                            </div>
                            <p className="mt-2 text-sm font-semibold">
                                {data.readiness_label}
                            </p>
                            <ul className="mt-2 divide-y divide-dashed divide-border text-sm">
                                <Row
                                    label="Équipement sécurité"
                                    status={
                                        data.missing_equipment.length === 0
                                            ? 'OK'
                                            : `${data.missing_equipment.length} manquant${data.missing_equipment.length > 1 ? 's' : ''}`
                                    }
                                    tone={
                                        data.missing_equipment.length === 0
                                            ? 'good'
                                            : 'warn'
                                    }
                                />
                                {data.missing_equipment
                                    .slice(0, 3)
                                    .map((item) => (
                                        <Row
                                            key={item}
                                            label={item}
                                            status="Manquant"
                                            tone="bad"
                                        />
                                    ))}
                                {data.missing_equipment.length > 3 && (
                                    <li className="py-2">
                                        <Link
                                            href={routes.boat}
                                            className="pw-label hover:!text-primary"
                                        >
                                            +{' '}
                                            {data.missing_equipment.length - 3}{' '}
                                            autres sur la fiche bateau →
                                        </Link>
                                    </li>
                                )}
                                <Row
                                    label="Itinéraire"
                                    status={
                                        cruise.route ? 'Calculé' : 'À calculer'
                                    }
                                    tone={cruise.route ? 'good' : 'warn'}
                                />
                            </ul>
                            <Button
                                asChild
                                variant="outline"
                                className="mt-4 w-full"
                            >
                                <Link href={routes.cruise(cruise.slug)}>
                                    Préparer l’itinéraire
                                    <ArrowRight className="size-4" />
                                </Link>
                            </Button>
                        </section>

                        <BoatPanel boat={boat} />
                    </aside>
                </div>
            ) : (
                <div className="grid flex-1 gap-px bg-border xl:grid-cols-[minmax(0,1fr)_340px]">
                    <section className="flex flex-col items-start justify-center gap-4 bg-background px-8 py-16">
                        <Route className="size-8 text-primary" />
                        <div>
                            <p className="pw-label">Aucune croisière</p>
                            <h2 className="mt-1 text-2xl font-semibold tracking-[0.02em] uppercase">
                                Choisissez un port de départ
                            </h2>
                            <p className="mt-2 max-w-lg text-muted-foreground">
                                MariWaze trace l’itinéraire selon votre bateau,
                                puis juge chaque étape avec la météo.
                            </p>
                        </div>
                        <Button asChild>
                            <Link href="/croisieres/nouvelle">
                                Créer une croisière
                                <ArrowRight className="size-4" />
                            </Link>
                        </Button>
                    </section>
                    <aside className="bg-background">
                        <BoatPanel boat={boat} />
                    </aside>
                </div>
            )}
        </div>
    );
}

function Row({
    label,
    status,
    tone,
}: {
    label: string;
    status: string;
    tone: Tone;
}) {
    return (
        <li className="flex items-center justify-between gap-3 py-2">
            <span>{label}</span>
            <span className={cn('pw-status', toneText[tone])}>{status}</span>
        </li>
    );
}

function NextLegPanel({ leg }: { leg: DashboardLeg }) {
    const tone = weatherTone(leg.weather);

    return (
        <section className="border-b border-border px-5 py-4">
            <p className="pw-label">
                Prochaine étape · J{leg.day} {leg.from} → {leg.to}
            </p>
            <div className="mt-2 flex items-baseline gap-3">
                <span
                    className={cn(
                        'text-[26px] leading-8 font-semibold',
                        toneText[tone],
                    )}
                >
                    {leg.weather?.available
                        ? leg.weather.label
                        : 'Prévision à venir'}
                </span>
            </div>
            {leg.weather?.detail && (
                <p className="mt-1 text-sm text-muted-foreground">
                    {leg.weather.detail}
                </p>
            )}
            <div className="mt-3 grid grid-cols-3 border border-border">
                <Reading
                    label="Vent"
                    value={
                        leg.weather?.wind_knots !== null &&
                        leg.weather?.wind_knots !== undefined
                            ? String(leg.weather.wind_knots)
                            : '—'
                    }
                    unit="nds"
                />
                <Reading
                    label="Mer"
                    value={
                        leg.weather?.wave_height_meters !== null &&
                        leg.weather?.wave_height_meters !== undefined
                            ? nf.format(leg.weather.wave_height_meters)
                            : '—'
                    }
                    unit="m"
                />
                <Reading
                    label="Distance"
                    value={formatMiles(leg.distance_nm)}
                    unit="M"
                />
            </div>
        </section>
    );
}

function Reading({
    label,
    value,
    unit,
}: {
    label: string;
    value: string;
    unit: string;
}) {
    return (
        <div className="border-r border-border px-3 py-2 last:border-r-0">
            <p className="pw-label">{label}</p>
            <p className="pw-figure text-lg">
                {value}
                <small className="ml-1 text-[11px] text-muted-foreground">
                    {unit}
                </small>
            </p>
        </div>
    );
}

function LegTile({ leg, current }: { leg: DashboardLeg; current: boolean }) {
    const tone = weatherTone(leg.weather);
    const duration = formatHours(leg.duration_hours);

    return (
        <div
            className={cn(
                'flex flex-col gap-1.5 bg-background px-4 py-3',
                current && 'bg-raised shadow-[inset_0_2px_0_var(--route)]',
            )}
        >
            <div className="flex items-center justify-between gap-2">
                <span className={cn('pw-label', current && '!text-route')}>
                    J{leg.day}
                </span>
                <span className={cn('pw-status', toneText[tone])}>
                    {leg.weather?.available ? leg.weather.label : 'À venir'}
                </span>
            </div>
            <p className="leading-5 font-semibold">
                {leg.from} → {leg.to}
            </p>
            <p className="pw-figure text-xs text-muted-foreground">
                <b className="font-medium text-foreground">
                    {formatMiles(leg.distance_nm)}
                </b>{' '}
                M{duration && ` · ${duration}`}
                {leg.overnight && ` · ${leg.overnight}`}
            </p>
        </div>
    );
}

function BoatPanel({ boat }: { boat: DashboardData['boat'] }) {
    const routes = useMariwazeRoutes();

    return (
        <section className="px-5 py-4">
            <div className="flex items-baseline justify-between">
                <h2 className="pw-title">Bateau</h2>
                <Link
                    href={boat ? routes.boat : routes.boatSetup}
                    className="pw-label hover:!text-primary"
                >
                    {boat ? 'Fiche →' : 'Configurer →'}
                </Link>
            </div>
            {boat ? (
                <ul className="mt-2 divide-y divide-dashed divide-border text-sm">
                    <li className="flex justify-between py-2">
                        <span>{boat.name}</span>
                        <span className="text-muted-foreground">
                            {boat.model || boat.type}
                        </span>
                    </li>
                    <li className="flex justify-between py-2">
                        <span>Tirant d’eau</span>
                        <span className="pw-figure">
                            {nf.format(boat.draft)} m
                        </span>
                    </li>
                    <li className="flex justify-between py-2">
                        <span>Tirant d’air</span>
                        <span className="pw-figure">
                            {boat.mast_height
                                ? `${nf.format(boat.mast_height)} m`
                                : '—'}
                        </span>
                    </li>
                </ul>
            ) : (
                <p className="mt-2 text-sm text-muted-foreground">
                    Ajoutez tirant d’eau et tirant d’air : MariWaze écartera les
                    ports et ponts incompatibles.
                </p>
            )}
        </section>
    );
}

/* ------------------------------------------------------------------
 * Design « Au large » : la carte en plein écran, l'interface en feuilles.
 * ------------------------------------------------------------------ */

// Cadrage du tracé hors des feuilles (gauche 412 px, bas 230 px, droite 330 px).
const LARGE_FIT_PADDING = {
    paddingTopLeft: [440, 110] as [number, number],
    paddingBottomRight: [350, 250] as [number, number],
};

const sheet =
    'relative rounded-[24px] bg-card shadow-[var(--soft-shadow)] lg:absolute';

function DashboardLarge({ data }: { data: DashboardData }) {
    const routes = useMariwazeRoutes();
    const { boat, cruise, alerts } = data;

    return (
        <div className="relative flex flex-col gap-3 pb-4 lg:fixed lg:inset-0 lg:block lg:pb-0">
            <div className="mw-fullbleed-map relative isolate h-[58vh] bg-[var(--chart-water-1)] lg:absolute lg:inset-0 lg:h-auto">
                {cruise && (
                    <CruiseMap
                        departure={cruise.departure_port}
                        destination={cruise.destination_port}
                        routeGeometry={cruise.route?.geometry ?? []}
                        routeAvailable={cruise.route?.available ?? false}
                        className="absolute inset-0 overflow-hidden"
                        chrome={false}
                        zoomPosition="topright"
                        fitPadding={LARGE_FIT_PADDING}
                    />
                )}
            </div>

            {cruise ? (
                <>
                    <section
                        className={cn(
                            sheet,
                            'mx-4 flex flex-col p-6 lg:top-[88px] lg:left-5 lg:mx-0 lg:w-[392px] lg:overflow-y-auto',
                            // La frise des étapes occupe le bas ; sans elle, la feuille suit son contenu.
                            cruise.next_legs.length > 0
                                ? 'lg:bottom-[236px]'
                                : 'lg:max-h-[calc(100svh-108px)]',
                        )}
                    >
                        <div className="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                            <span className="rounded-full bg-warn-soft px-2.5 py-0.5 font-semibold text-foreground">
                                {cruise.status}
                            </span>
                            {cruise.departure_date &&
                                `Départ le ${cruise.departure_date}`}
                        </div>
                        <h1 className="mt-3 text-[28px] leading-8 font-semibold tracking-[-0.02em]">
                            {cruise.title}
                        </h1>
                        <p className="mt-1 text-muted-foreground">
                            {cruise.departure_port} → {cruise.destination_port}
                            {cruise.route && ` · ${cruise.route.label}`}
                        </p>

                        <div className="my-5 grid grid-cols-3 border-y border-border py-4">
                            <BigFigure
                                value={
                                    cruise.route
                                        ? formatMiles(cruise.route.distance_nm)
                                        : '—'
                                }
                                label="milles"
                            />
                            <BigFigure
                                value={
                                    cruise.route
                                        ? String(cruise.route.stops)
                                        : '—'
                                }
                                label="escales"
                                divided
                            />
                            <BigFigure
                                value={String(cruise.duration_days)}
                                label={`jours · ${cruise.style}`}
                                divided
                            />
                        </div>

                        <div className="flex items-center gap-4">
                            <ReadinessRing value={data.readiness} />
                            <div>
                                <p className="font-semibold">
                                    {data.readiness_label}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    {data.missing_equipment.length === 0
                                        ? 'Équipement de sécurité complet.'
                                        : `${data.missing_equipment.length} équipement${data.missing_equipment.length > 1 ? 's' : ''} à compléter.`}
                                </p>
                            </div>
                        </div>

                        <ul className="mt-4 space-y-1.5">
                            {alerts.slice(0, 2).map((alert) => (
                                <li
                                    key={alert.id}
                                    className="flex items-start gap-2.5 rounded-xl bg-warn-soft px-3 py-2 text-sm"
                                >
                                    <AlertTriangle className="mt-0.5 size-4 shrink-0 text-warn" />
                                    {alert.message}
                                </li>
                            ))}
                            {data.missing_equipment.slice(0, 3).map((item) => (
                                <li
                                    key={item}
                                    className="flex items-center gap-2.5 rounded-xl bg-background px-3 py-2 text-sm"
                                >
                                    <i className="size-2 shrink-0 rounded-full bg-bad" />
                                    <span className="flex-1">{item}</span>
                                    <span className="text-xs font-semibold text-bad">
                                        Manquant
                                    </span>
                                </li>
                            ))}
                            {!cruise.route && (
                                <li className="flex items-center gap-2.5 rounded-xl bg-background px-3 py-2 text-sm">
                                    <i className="size-2 shrink-0 rounded-full bg-warn" />
                                    <span className="flex-1">Itinéraire</span>
                                    <span className="text-xs font-semibold text-warn">
                                        À calculer
                                    </span>
                                </li>
                            )}
                        </ul>

                        <div className="mt-6 flex flex-col gap-2 lg:mt-auto lg:pt-6">
                            <Button asChild size="lg" className="w-full">
                                <Link href={routes.cruise(cruise.slug)}>
                                    Continuer la préparation
                                </Link>
                            </Button>
                            {cruise.route?.score !== null &&
                                cruise.route?.score !== undefined && (
                                    <p className="text-center text-sm text-muted-foreground">
                                        Note de l’itinéraire{' '}
                                        {cruise.route.score}/100
                                    </p>
                                )}
                        </div>
                    </section>

                    <LargeBoatSheet boat={boat} />

                    {cruise.next_legs.length > 0 && (
                        <section
                            className={cn(
                                sheet,
                                'mx-4 px-6 py-5 lg:right-5 lg:bottom-8 lg:left-5 lg:mx-0',
                            )}
                        >
                            <div className="flex items-baseline justify-between">
                                <h2 className="font-semibold">
                                    Prochaines escales
                                </h2>
                                <Link
                                    href={routes.cruise(cruise.slug)}
                                    className="text-sm font-semibold text-primary"
                                >
                                    Tout l’itinéraire →
                                </Link>
                            </div>
                            <ol className="relative mt-4 grid gap-4 sm:grid-cols-3 lg:grid-cols-6 lg:before:absolute lg:before:top-[5px] lg:before:right-1.5 lg:before:left-1.5 lg:before:h-0.5 lg:before:bg-border">
                                {cruise.next_legs.slice(0, 6).map((leg, i) => (
                                    <LargeLeg
                                        key={`${leg.day}-${leg.to}`}
                                        leg={leg}
                                        current={i === 0}
                                    />
                                ))}
                            </ol>
                        </section>
                    )}
                </>
            ) : (
                <>
                    <section
                        className={cn(
                            sheet,
                            'mx-4 p-8 lg:top-[88px] lg:left-5 lg:mx-0 lg:w-[420px]',
                        )}
                    >
                        <span className="rounded-full bg-accent px-3 py-1 text-xs font-semibold">
                            Aucune croisière
                        </span>
                        <h1 className="mt-4 text-[30px] leading-9 font-semibold tracking-[-0.02em]">
                            Où allez-vous naviguer&nbsp;?
                        </h1>
                        <p className="mt-2 text-muted-foreground">
                            Choisissez un port de départ et une destination :
                            MariWaze trace l’itinéraire selon votre bateau.
                        </p>
                        <Button asChild size="lg" className="mt-6 w-full">
                            <Link href="/croisieres/nouvelle">
                                Créer une croisière
                            </Link>
                        </Button>
                    </section>
                    <LargeBoatSheet boat={boat} />
                </>
            )}
        </div>
    );
}

function BigFigure({
    value,
    label,
    divided = false,
}: {
    value: string;
    label: string;
    divided?: boolean;
}) {
    return (
        <div className={cn(divided && 'border-l border-border pl-4')}>
            <p className="text-[30px] leading-9 font-light tracking-[-0.02em] tabular-nums">
                {value}
            </p>
            <p className="text-xs text-muted-foreground">{label}</p>
        </div>
    );
}

function ReadinessRing({ value }: { value: number }) {
    const circumference = 2 * Math.PI * 27;
    const clamped = Math.max(0, Math.min(100, value));

    return (
        <svg
            width="64"
            height="64"
            viewBox="0 0 64 64"
            className="shrink-0"
            role="img"
            aria-label={`${clamped} % prêt`}
        >
            <circle
                cx="32"
                cy="32"
                r="27"
                fill="none"
                stroke="var(--sunken)"
                strokeWidth="7"
            />
            <circle
                cx="32"
                cy="32"
                r="27"
                fill="none"
                stroke="var(--primary)"
                strokeWidth="7"
                strokeLinecap="round"
                strokeDasharray={`${(clamped / 100) * circumference} ${circumference}`}
                transform="rotate(-90 32 32)"
            />
            <text
                x="32"
                y="37"
                textAnchor="middle"
                fill="var(--foreground)"
                fontSize="15"
                fontWeight="600"
            >
                {clamped}%
            </text>
        </svg>
    );
}

const nfLarge = new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 1 });

function LargeBoatSheet({ boat }: { boat: DashboardData['boat'] }) {
    const routes = useMariwazeRoutes();

    return (
        <section
            className={cn(
                sheet,
                'mx-4 px-5 py-4 lg:top-[88px] lg:right-5 lg:mx-0 lg:w-[300px]',
            )}
        >
            <div className="flex items-baseline justify-between gap-3">
                <h2 className="truncate font-semibold">
                    {boat
                        ? `${boat.name} · ${boat.model || boat.type}`
                        : 'Votre bateau'}
                </h2>
                <Link
                    href={boat ? routes.boat : routes.boatSetup}
                    className="shrink-0 text-sm font-semibold text-primary"
                >
                    {boat ? 'Fiche' : 'Ajouter'}
                </Link>
            </div>
            {boat ? (
                <div className="mt-3 grid grid-cols-2 gap-2">
                    <div className="rounded-2xl bg-background px-3 py-2">
                        <p className="text-lg font-medium tabular-nums">
                            {nfLarge.format(boat.draft)} m
                        </p>
                        <p className="text-xs text-muted-foreground">
                            tirant d’eau
                        </p>
                    </div>
                    <div className="rounded-2xl bg-background px-3 py-2">
                        <p className="text-lg font-medium tabular-nums">
                            {boat.mast_height
                                ? `${nfLarge.format(boat.mast_height)} m`
                                : '—'}
                        </p>
                        <p className="text-xs text-muted-foreground">
                            tirant d’air
                        </p>
                    </div>
                </div>
            ) : (
                <p className="mt-2 text-sm text-muted-foreground">
                    Ajoutez tirant d’eau et tirant d’air pour écarter les
                    escales incompatibles.
                </p>
            )}
        </section>
    );
}

const legPill: Record<Tone, string> = {
    good: 'bg-good-soft',
    warn: 'bg-warn-soft',
    bad: 'bg-bad-soft',
    muted: 'bg-sunken text-muted-foreground',
    accent: 'bg-accent',
};

const legDot: Record<Tone, string> = {
    good: 'bg-good',
    warn: 'bg-warn',
    bad: 'bg-bad',
    muted: 'border border-muted-foreground',
    accent: 'bg-primary',
};

function LargeLeg({ leg, current }: { leg: DashboardLeg; current: boolean }) {
    const tone = weatherTone(leg.weather);
    const duration = formatHours(leg.duration_hours);

    return (
        <li className="relative lg:pt-6">
            <span
                className={cn(
                    'absolute top-0 left-0 hidden size-3 rounded-full border-2 border-route lg:block',
                    current ? 'bg-route' : 'bg-card',
                )}
            />
            <p className="text-xs font-medium text-muted-foreground">
                J{leg.day}
            </p>
            <p className="mt-0.5 leading-5 font-semibold">
                {leg.from} → {leg.to}
            </p>
            <p className="text-[13px] text-muted-foreground tabular-nums">
                {formatMiles(leg.distance_nm)} M{duration && ` · ${duration}`}
                {leg.overnight && ` · ${leg.overnight}`}
            </p>
            <span
                className={cn(
                    'mt-2 inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold',
                    legPill[tone],
                )}
            >
                <i className={cn('size-1.5 rounded-full', legDot[tone])} />
                {leg.weather?.available
                    ? leg.weather.label
                    : 'Prévision à venir'}
            </span>
        </li>
    );
}
