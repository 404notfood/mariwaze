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

CruisesIndex.layout = () => ({
    breadcrumbs: [{ title: 'Mes croisières', href: appRoutes.cruises }],
});
