import { Head, Link, router } from '@inertiajs/react';
import ArrowRight from 'lucide-react/icons/arrow-right';
import Calendar from 'lucide-react/icons/calendar';
import MapPin from 'lucide-react/icons/map-pin';
import Ship from 'lucide-react/icons/ship';
import Trash2 from 'lucide-react/icons/trash-2';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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

export default function CruisesIndex({ cruises }: Props) {
    const routes = useMariwazeRoutes();

    return (
        <>
            <Head title="Mes croisières" />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-foreground md:text-3xl">
                            Mes croisières
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Vos projets de navigation en préparation ou en cours
                        </p>
                    </div>
                    <Button
                        asChild
                        className="bg-sea-blue hover:bg-sea-blue/90"
                    >
                        <Link href="/croisieres/nouvelle">
                            Nouvelle croisière
                            <ArrowRight className="size-4" />
                        </Link>
                    </Button>
                </div>

                {cruises.length === 0 && (
                    <Card className="border-dashed border-sea-blue/30 bg-sea-blue/5">
                        <CardContent className="flex flex-col items-center gap-4 px-6 py-12 text-center">
                            <div className="rounded-full bg-sea-blue/10 p-4">
                                <Ship
                                    className="size-8 text-sea-blue"
                                    aria-hidden="true"
                                />
                            </div>
                            <div className="max-w-md space-y-2">
                                <h2 className="text-xl font-semibold">
                                    Votre prochaine aventure commence ici
                                </h2>
                                <p className="text-sm text-muted-foreground">
                                    Choisissez un départ et une destination pour
                                    préparer votre première croisière, comparer
                                    les itinéraires et suivre votre checklist.
                                </p>
                            </div>
                            <Button
                                asChild
                                className="bg-sea-blue hover:bg-sea-blue/90"
                            >
                                <Link href="/croisieres/nouvelle">
                                    Préparer ma première croisière
                                    <ArrowRight className="size-4" />
                                </Link>
                            </Button>
                            <Link
                                href={appRoutes.explore}
                                className="text-sm text-sea-blue underline underline-offset-4"
                            >
                                Découvrir les ports et mouillages
                            </Link>
                        </CardContent>
                    </Card>
                )}
                <div className="grid gap-4">
                    {cruises.map((cruise) => (
                        <Card
                            key={cruise.slug}
                            className="overflow-hidden border-border/60"
                        >
                            <div className="h-1 bg-gradient-to-r from-ocean-navy via-sea-blue to-lagoon" />
                            <CardHeader className="flex flex-row items-start justify-between gap-4">
                                <div>
                                    <Badge
                                        variant="secondary"
                                        className="mb-2 bg-sand/50 dark:bg-sand/20"
                                    >
                                        {cruise.status}
                                    </Badge>
                                    <CardTitle className="text-xl">
                                        {cruise.title}
                                    </CardTitle>
                                    <p className="mt-1 flex items-center gap-1 text-sm text-muted-foreground">
                                        <MapPin className="size-3.5" />
                                        {cruise.route}
                                    </p>
                                </div>
                                <Ship className="size-8 text-sea-blue/60" />
                            </CardHeader>
                            <CardContent>
                                <div className="mb-4 grid grid-cols-2 gap-4 text-sm md:grid-cols-4">
                                    <Meta
                                        icon={Calendar}
                                        label="Départ"
                                        value={cruise.departure_date}
                                    />
                                    <Meta
                                        label="Durée"
                                        value={cruise.duration}
                                    />
                                    <Meta
                                        label="Milles"
                                        value={
                                            cruise.route_available === false
                                                ? 'À vérifier'
                                                : `${cruise.miles} M`
                                        }
                                    />
                                    <Meta label="Style" value={cruise.style} />
                                </div>
                                <div className="mb-4">
                                    <div className="mb-1 flex justify-between text-sm">
                                        <span className="text-muted-foreground">
                                            Préparation
                                        </span>
                                        <span className="font-semibold text-sea-blue">
                                            {cruise.progress}%
                                        </span>
                                    </div>
                                    <div className="h-2 overflow-hidden rounded-full bg-muted">
                                        <div
                                            className="h-full rounded-full bg-gradient-to-r from-sea-blue to-lagoon"
                                            style={{
                                                width: `${cruise.progress}%`,
                                            }}
                                        />
                                    </div>
                                </div>
                                <div className="flex flex-col gap-2 sm:flex-row">
                                    <Button
                                        asChild
                                        variant="outline"
                                        className="w-full sm:w-auto"
                                    >
                                        <Link href={routes.cruise(cruise.slug)}>
                                            Voir la croisière
                                            <ArrowRight className="size-4" />
                                        </Link>
                                    </Button>
                                    {cruise.deletable && (
                                        <Dialog>
                                            <DialogTrigger asChild>
                                                <Button
                                                    variant="outline"
                                                    className="w-full border-destructive/40 text-destructive hover:bg-destructive/10 hover:text-destructive sm:w-auto"
                                                >
                                                    <Trash2 className="size-4" />
                                                    Supprimer
                                                </Button>
                                            </DialogTrigger>
                                            <DialogContent>
                                                <DialogHeader>
                                                    <DialogTitle>
                                                        Supprimer cette
                                                        croisière ?
                                                    </DialogTitle>
                                                    <DialogDescription>
                                                        « {cruise.title} » sera
                                                        supprimée
                                                        définitivement, avec son
                                                        itinéraire préparé.
                                                    </DialogDescription>
                                                </DialogHeader>
                                                <DialogFooter>
                                                    <DialogClose asChild>
                                                        <Button variant="outline">
                                                            Annuler
                                                        </Button>
                                                    </DialogClose>
                                                    <Button
                                                        variant="destructive"
                                                        onClick={() =>
                                                            router.delete(
                                                                `/croisieres/${cruise.slug}`,
                                                                {
                                                                    preserveScroll: true,
                                                                    onSuccess:
                                                                        () =>
                                                                            router.visit(
                                                                                '/croisieres',
                                                                                {
                                                                                    replace: true,
                                                                                },
                                                                            ),
                                                                },
                                                            )
                                                        }
                                                    >
                                                        <Trash2 className="size-4" />
                                                        Supprimer
                                                    </Button>
                                                </DialogFooter>
                                            </DialogContent>
                                        </Dialog>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>
        </>
    );
}

function Meta({
    icon: Icon,
    label,
    value,
}: {
    icon?: typeof Calendar;
    label: string;
    value: string;
}) {
    return (
        <div>
            <p className="flex items-center gap-1 text-xs text-muted-foreground">
                {Icon && <Icon className="size-3" />}
                {label}
            </p>
            <p className="font-semibold">{value}</p>
        </div>
    );
}

CruisesIndex.layout = () => ({
    breadcrumbs: [{ title: 'Mes croisières', href: appRoutes.cruises }],
});
