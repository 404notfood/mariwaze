import { Head, Link, router } from '@inertiajs/react';
import ArrowRight from 'lucide-react/icons/arrow-right';
import Plus from 'lucide-react/icons/plus';
import Route from 'lucide-react/icons/route';
import Trash2 from 'lucide-react/icons/trash-2';
import {
    Chip,
    Instrument,
    PageHeader,
    SegmentBar,
} from '@/components/mariwaze/passerelle';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { useDesign } from '@/hooks/use-design';
import { useMariwazeRoutes } from '@/hooks/use-mariwaze-routes';
import { appRoutes } from '@/lib/mariwaze-routes';
import { cn } from '@/lib/utils';

type Cruise = {
    slug: string;
    title: string;
    route: string;
    departure_date: string;
    duration: string;
    status: string;
    progress: number;
    miles: number;
    stops: number;
    style: string;
    route_available?: boolean;
    deletable?: boolean;
};

type Props = {
    cruises: Cruise[];
};

const nf = new Intl.NumberFormat('fr-FR');

export default function CruisesIndex({ cruises }: Props) {
    const routes = useMariwazeRoutes();
    const totalMiles = cruises.reduce(
        (sum, cruise) =>
            sum + (cruise.route_available === false ? 0 : cruise.miles),
        0,
    );
    const toFinish = cruises.filter((cruise) => cruise.progress < 100).length;
    const { design } = useDesign();

    if (design === 'large') {
        return <CruisesLarge cruises={cruises} />;
    }

    return (
        <>
            <Head title="Mes croisières" />
            <div className="flex flex-1 flex-col">
                <PageHeader
                    label="Projets"
                    title="Mes croisières"
                    actions={
                        <Button asChild>
                            <Link href="/croisieres/nouvelle">
                                <Plus className="size-4" />
                                Croisière
                            </Link>
                        </Button>
                    }
                />

                {cruises.length > 0 && (
                    <div className="grid grid-cols-3 border-b border-border">
                        <Instrument
                            label="Croisières"
                            value={cruises.length}
                            className="border-r border-border"
                        />
                        <Instrument
                            label="À préparer"
                            value={toFinish}
                            className="border-r border-border"
                        />
                        <Instrument
                            label="Milles prévus"
                            value={nf.format(totalMiles)}
                            unit="M"
                        />
                    </div>
                )}

                {cruises.length === 0 && (
                    <section className="flex flex-col items-start gap-4 px-5 py-16">
                        <Route className="size-8 text-primary" />
                        <div className="max-w-lg">
                            <p className="pw-label">Aucune croisière</p>
                            <h2 className="mt-1 text-2xl font-semibold tracking-[0.02em] uppercase">
                                Choisissez un port de départ
                            </h2>
                            <p className="mt-2 text-muted-foreground">
                                Un départ, une destination : MariWaze compare
                                les itinéraires et prépare votre liste avant
                                d’appareiller.
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Button asChild>
                                <Link href="/croisieres/nouvelle">
                                    Préparer ma première croisière
                                    <ArrowRight className="size-4" />
                                </Link>
                            </Button>
                            <Button asChild variant="outline">
                                <Link href={appRoutes.explore}>
                                    Ports et mouillages
                                </Link>
                            </Button>
                        </div>
                    </section>
                )}

                {cruises.length > 0 && (
                    <div role="table" aria-label="Croisières">
                        <div
                            role="row"
                            className="hidden grid-cols-[minmax(0,2.2fr)_1fr_1fr_0.8fr_1.4fr_auto] gap-4 border-b border-line-strong bg-sunken px-5 py-2 lg:grid"
                        >
                            <span role="columnheader" className="pw-label">
                                Croisière
                            </span>
                            <span role="columnheader" className="pw-label">
                                Départ
                            </span>
                            <span
                                role="columnheader"
                                className="pw-label text-right"
                            >
                                Distance
                            </span>
                            <span
                                role="columnheader"
                                className="pw-label text-right"
                            >
                                Durée
                            </span>
                            <span role="columnheader" className="pw-label">
                                Préparation
                            </span>
                            <span role="columnheader" className="sr-only">
                                Actions
                            </span>
                        </div>

                        {cruises.map((cruise, index) => (
                            <div
                                key={cruise.slug}
                                role="row"
                                className={cn(
                                    'grid gap-3 border-b border-border px-5 py-4 lg:grid-cols-[minmax(0,2.2fr)_1fr_1fr_0.8fr_1.4fr_auto] lg:items-center lg:gap-4',
                                    index === 0 &&
                                        'bg-raised shadow-[inset_2px_0_0_var(--primary)]',
                                )}
                            >
                                <div role="cell" className="min-w-0">
                                    <div className="flex flex-wrap gap-1">
                                        <Chip accent>{cruise.status}</Chip>
                                        <Chip>{cruise.style}</Chip>
                                    </div>
                                    <p className="mt-1.5 truncate text-lg leading-6 font-semibold">
                                        {cruise.title}
                                    </p>
                                    <p className="truncate text-sm text-muted-foreground">
                                        {cruise.route}
                                    </p>
                                </div>

                                <div role="cell">
                                    <span className="pw-label lg:hidden">
                                        Départ{' '}
                                    </span>
                                    <span className="pw-figure text-sm">
                                        {cruise.departure_date}
                                    </span>
                                </div>

                                <div role="cell" className="lg:text-right">
                                    <span className="pw-label lg:hidden">
                                        Distance{' '}
                                    </span>
                                    {cruise.route_available === false ? (
                                        <span className="pw-status text-warn">
                                            À vérifier
                                        </span>
                                    ) : (
                                        <span className="pw-figure text-sm">
                                            {nf.format(cruise.miles)} M
                                            <span className="block text-xs text-muted-foreground">
                                                {cruise.stops} escale
                                                {cruise.stops > 1 ? 's' : ''}
                                            </span>
                                        </span>
                                    )}
                                </div>

                                <div role="cell" className="lg:text-right">
                                    <span className="pw-label lg:hidden">
                                        Durée{' '}
                                    </span>
                                    <span className="pw-figure text-sm">
                                        {cruise.duration}
                                    </span>
                                </div>

                                <div role="cell">
                                    <span className="pw-figure text-lg">
                                        {cruise.progress}%
                                    </span>
                                    <SegmentBar
                                        value={cruise.progress}
                                        className="mt-1 h-2.5 max-w-[180px]"
                                    />
                                </div>

                                <div
                                    role="cell"
                                    className="flex items-center gap-2 lg:justify-end"
                                >
                                    <Button
                                        asChild
                                        variant={
                                            index === 0 ? 'default' : 'outline'
                                        }
                                        size="sm"
                                    >
                                        <Link href={routes.cruise(cruise.slug)}>
                                            Ouvrir
                                            <ArrowRight className="size-4" />
                                        </Link>
                                    </Button>
                                    {cruise.deletable && (
                                        <DeleteCruise cruise={cruise} />
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

function DeleteCruise({ cruise }: { cruise: Cruise }) {
    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className="size-8 text-muted-foreground hover:text-destructive"
                    title="Supprimer la croisière"
                    aria-label={`Supprimer ${cruise.title}`}
                >
                    <Trash2 className="size-4" />
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Supprimer cette croisière ?</DialogTitle>
                    <DialogDescription>
                        « {cruise.title} » sera supprimée définitivement, avec
                        son itinéraire préparé.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <DialogClose asChild>
                        <Button variant="outline">Annuler</Button>
                    </DialogClose>
                    <Button
                        variant="destructive"
                        onClick={() =>
                            router.delete(`/croisieres/${cruise.slug}`, {
                                preserveScroll: true,
                                onSuccess: () =>
                                    router.visit('/croisieres', {
                                        replace: true,
                                    }),
                            })
                        }
                    >
                        <Trash2 className="size-4" />
                        Supprimer
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

/* ------------------------------------------------------------------
 * Design « Au large » : une carte par croisière, posée sur le fond.
 * ------------------------------------------------------------------ */

function CruisesLarge({ cruises }: Props) {
    const routes = useMariwazeRoutes();
    const nextDeparture = cruises.find((cruise) => cruise.progress < 100);

    return (
        <>
            <Head title="Mes croisières" />
            <div className="flex flex-col gap-6 pt-4">
                <header className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h1 className="text-[34px] leading-10 font-semibold tracking-[-0.02em]">
                            Vos croisières
                        </h1>
                        <p className="mt-1 text-[15px] text-muted-foreground">
                            {cruises.length === 0
                                ? 'Aucune croisière pour l’instant.'
                                : nextDeparture
                                  ? `Prochain départ : ${nextDeparture.departure_date}.`
                                  : `${cruises.length} croisière${cruises.length > 1 ? 's' : ''}.`}
                        </p>
                    </div>
                    <Button asChild size="lg">
                        <Link href="/croisieres/nouvelle">
                            <Plus className="size-4" />
                            Nouvelle croisière
                        </Link>
                    </Button>
                </header>

                <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                    {cruises.map((cruise, index) => (
                        <article
                            key={cruise.slug}
                            className="flex flex-col overflow-hidden rounded-[24px] bg-card shadow-[var(--soft-shadow)]"
                        >
                            <RouteBand
                                route={cruise.route}
                                status={cruise.status}
                            />
                            <div className="flex flex-1 flex-col p-5">
                                <div className="flex items-start justify-between gap-2">
                                    <h2 className="text-[19px] leading-6 font-semibold tracking-[-0.01em]">
                                        {cruise.title}
                                    </h2>
                                    {cruise.deletable && (
                                        <DeleteCruise cruise={cruise} />
                                    )}
                                </div>
                                <p className="text-sm text-muted-foreground">
                                    {cruise.departure_date} · {cruise.style}
                                </p>

                                <div className="my-4 grid grid-cols-3 border-y border-border py-3">
                                    <SmallFigure
                                        value={
                                            cruise.route_available === false
                                                ? '—'
                                                : nf.format(cruise.miles)
                                        }
                                        label="milles"
                                    />
                                    <SmallFigure
                                        value={String(cruise.stops)}
                                        label="escales"
                                        divided
                                    />
                                    <SmallFigure
                                        value={cruise.duration.replace(
                                            /\s*jours?/,
                                            '',
                                        )}
                                        label="jours"
                                        divided
                                    />
                                </div>

                                <div className="mb-5 flex items-center gap-3">
                                    <ProgressRing value={cruise.progress} />
                                    <div>
                                        <p className="font-semibold">
                                            {cruise.progress} % prêt
                                        </p>
                                        <p className="text-[13px] text-muted-foreground">
                                            {cruise.route_available === false
                                                ? 'Itinéraire à vérifier'
                                                : 'Préparation en cours'}
                                        </p>
                                    </div>
                                </div>

                                <Button
                                    asChild
                                    variant={
                                        index === 0 ? 'default' : 'secondary'
                                    }
                                    className="mt-auto w-full"
                                >
                                    <Link href={routes.cruise(cruise.slug)}>
                                        {index === 0
                                            ? 'Continuer la préparation'
                                            : 'Ouvrir'}
                                    </Link>
                                </Button>
                            </div>
                        </article>
                    ))}

                    <Link
                        href="/croisieres/nouvelle"
                        className="flex min-h-[320px] flex-col items-center justify-center gap-3 rounded-[24px] border-2 border-dashed border-line-strong p-6 text-center text-muted-foreground transition-colors hover:border-primary hover:text-foreground"
                    >
                        <span className="flex size-14 items-center justify-center rounded-full bg-accent text-primary">
                            <Plus className="size-7" />
                        </span>
                        <span className="text-[17px] font-semibold text-foreground">
                            Nouvelle croisière
                        </span>
                        <span className="text-sm">
                            Un port de départ, une destination : MariWaze
                            propose les itinéraires adaptés à votre bateau.
                        </span>
                    </Link>
                </div>
            </div>
        </>
    );
}

/** Bandeau du haut de carte : départ et arrivée reliés sur un fond marin. */
function RouteBand({ route, status }: { route: string; status: string }) {
    const [from, to] = route.split(/\s*→\s*/);

    return (
        <div className="relative h-[132px] overflow-hidden bg-[var(--chart-water-1)]">
            <svg
                viewBox="0 0 300 132"
                preserveAspectRatio="none"
                className="absolute inset-0 size-full"
                aria-hidden="true"
            >
                {[28, 52, 76].map((y) => (
                    <path
                        key={y}
                        d={`M-10 ${y + 30} C 60 ${y + 10}, 120 ${y + 40}, 180 ${y + 18} S 280 ${y}, 320 ${y + 12}`}
                        fill="none"
                        stroke="var(--chart-water-3)"
                        strokeWidth="1"
                        opacity="0.7"
                    />
                ))}
                <path
                    d="M40 96 C 110 30, 190 30, 260 62"
                    fill="none"
                    stroke="var(--card)"
                    strokeWidth="6"
                    strokeLinecap="round"
                />
                <path
                    d="M40 96 C 110 30, 190 30, 260 62"
                    fill="none"
                    stroke="var(--route)"
                    strokeWidth="2.5"
                    strokeLinecap="round"
                    strokeDasharray={to ? undefined : '6 6'}
                />
                <circle
                    cx="40"
                    cy="96"
                    r="6"
                    fill="var(--deep)"
                    stroke="var(--card)"
                    strokeWidth="2"
                />
                <circle
                    cx="260"
                    cy="62"
                    r="6"
                    fill="var(--deep)"
                    stroke="var(--card)"
                    strokeWidth="2"
                />
            </svg>
            <span className="absolute top-3 left-3 rounded-full bg-warn-soft px-2.5 py-0.5 text-xs font-semibold">
                {status}
            </span>
            <span className="absolute bottom-2.5 left-3 max-w-[45%] truncate text-xs font-semibold">
                {from}
            </span>
            {to && (
                <span className="absolute top-[78px] right-3 max-w-[45%] truncate text-right text-xs font-semibold">
                    {to}
                </span>
            )}
        </div>
    );
}

function SmallFigure({
    value,
    label,
    divided = false,
}: {
    value: string;
    label: string;
    divided?: boolean;
}) {
    return (
        <div className={cn(divided && 'border-l border-border pl-3')}>
            <p className="text-[22px] leading-7 font-light tracking-[-0.02em] tabular-nums">
                {value}
            </p>
            <p className="text-[11.5px] text-muted-foreground">{label}</p>
        </div>
    );
}

function ProgressRing({ value }: { value: number }) {
    const circumference = 2 * Math.PI * 16;
    const clamped = Math.max(0, Math.min(100, value));

    return (
        <svg
            width="40"
            height="40"
            viewBox="0 0 40 40"
            className="shrink-0"
            aria-hidden="true"
        >
            <circle
                cx="20"
                cy="20"
                r="16"
                fill="none"
                stroke="var(--sunken)"
                strokeWidth="5"
            />
            <circle
                cx="20"
                cy="20"
                r="16"
                fill="none"
                stroke="var(--primary)"
                strokeWidth="5"
                strokeLinecap="round"
                strokeDasharray={`${(clamped / 100) * circumference} ${circumference}`}
                transform="rotate(-90 20 20)"
            />
        </svg>
    );
}

CruisesIndex.layout = () => ({
    breadcrumbs: [{ title: 'Mes croisières', href: appRoutes.cruises }],
});
