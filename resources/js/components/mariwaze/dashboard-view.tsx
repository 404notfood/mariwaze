import { Link } from '@inertiajs/react';
import {
    AlertTriangle,
    Anchor,
    ArrowRight,
    Compass,
    MapPin,
    Route,
    Ship,
    Waves,
} from 'lucide-react';
import CruiseMap from '@/components/mariwaze/cruise-map';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useMariwazeRoutes } from '@/hooks/use-mariwaze-routes';

type DashboardData = {
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
        departure_date: string;
        duration_days: number;
        recommended_duration_days: number;
        duration_warning: boolean;
        route_geometry: Array<{ latitude: number; longitude: number }>;
        route_available: boolean;
        crew_count: number;
        style: string;
        status: string;
    } | null;
};

export default function DashboardView({
    data,
    preview = false,
}: {
    data: DashboardData;
    preview?: boolean;
}) {
    const routes = useMariwazeRoutes(preview);
    const { boat, cruise } = data;

    return (
        <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
            <header className="flex flex-col gap-4 border-b border-border/60 pb-6 md:flex-row md:items-end md:justify-between">
                <div>
                    <p className="text-sm text-muted-foreground">
                        Préparation de navigation
                    </p>
                    <h1 className="mt-1 text-2xl font-bold md:text-3xl">
                        Tableau de bord
                    </h1>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Vos prochaines décisions, sans données décoratives.
                    </p>
                </div>

                <Button asChild className="bg-sea-blue hover:bg-sea-blue/90">
                    <Link href="/croisieres/nouvelle">
                        Nouvelle croisière
                        <ArrowRight className="size-4" />
                    </Link>
                </Button>
            </header>

            {cruise ? (
                <section className="grid gap-5 xl:grid-cols-[minmax(0,1.6fr)_minmax(300px,0.8fr)]">
                    <div className="min-w-0">
                        <CruiseMap
                            departure={cruise.departure_port}
                            destination={cruise.destination_port}
                            routeGeometry={cruise.route_geometry}
                            routeAvailable={cruise.route_available}
                        />
                    </div>

                    <Card className="border-border/60">
                        <CardHeader className="pb-3">
                            <Badge
                                variant="secondary"
                                className="mb-2 w-fit bg-sand/50"
                            >
                                {cruise.status}
                            </Badge>
                            <CardTitle className="text-xl">
                                {cruise.title}
                            </CardTitle>
                            <CardDescription className="flex items-center gap-1 text-base">
                                <MapPin className="size-4" />
                                {cruise.departure_port} →{' '}
                                {cruise.destination_port}
                            </CardDescription>
                        </CardHeader>

                        <CardContent className="space-y-5">
                            <div className="grid grid-cols-2 gap-4 border-y border-border/60 py-4 text-sm">
                                <Metric
                                    label="Départ"
                                    value={cruise.departure_date}
                                />
                                <Metric
                                    label="Durée"
                                    value={`${cruise.duration_days} jours`}
                                />
                                <Metric
                                    label="Équipage"
                                    value={`${cruise.crew_count} personnes`}
                                />
                                <Metric label="Style" value={cruise.style} />
                            </div>

                            {cruise.duration_warning && (
                                <p className="flex items-center gap-2 text-sm text-safety-orange">
                                    <AlertTriangle className="size-4" />
                                    Durée recommandée pour ce style : au moins{' '}
                                    {cruise.recommended_duration_days} jours.
                                </p>
                            )}

                            <Button
                                asChild
                                className="w-full bg-sea-blue hover:bg-sea-blue/90"
                            >
                                <Link href={routes.cruise(cruise.slug)}>
                                    Préparer l’itinéraire
                                    <ArrowRight className="size-4" />
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                </section>
            ) : (
                <section className="rounded-lg border border-border/70 bg-card px-6 py-12 text-center shadow-sm">
                    <div className="mx-auto flex size-12 items-center justify-center rounded-full bg-sea-blue/10 text-sea-blue">
                        <Route className="size-6" />
                    </div>
                    <h2 className="mt-4 text-xl font-semibold">
                        Aucune croisière planifiée
                    </h2>
                    <p className="mx-auto mt-2 max-w-lg text-sm text-muted-foreground">
                        Créez un trajet pour afficher sa carte, ses escales, son
                        budget et ses conditions de navigation.
                    </p>
                    <Button
                        asChild
                        className="mt-5 bg-sea-blue hover:bg-sea-blue/90"
                    >
                        <Link href="/croisieres/nouvelle">
                            Créer une croisière
                            <ArrowRight className="size-4" />
                        </Link>
                    </Button>
                </section>
            )}

            <section className="grid gap-4 lg:grid-cols-3">
                <Card className="border-border/60 lg:col-span-2">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Ship className="size-4 text-sea-blue" />
                            Mon bateau
                        </CardTitle>
                    </CardHeader>

                    <CardContent>
                        {boat ? (
                            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p className="text-lg font-semibold">
                                        {boat.name}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {boat.model || boat.type}
                                    </p>
                                </div>

                                <div className="grid grid-cols-2 gap-x-8 gap-y-2 text-sm">
                                    <Metric
                                        label="Tirant d’eau"
                                        value={`${boat.draft} m`}
                                    />
                                    <Metric
                                        label="Mât"
                                        value={
                                            boat.mast_height
                                                ? `${boat.mast_height} m`
                                                : 'Non renseigné'
                                        }
                                    />
                                </div>

                                <Button asChild variant="outline">
                                    <Link href={routes.boat}>
                                        Voir le profil
                                        <ArrowRight className="size-4" />
                                    </Link>
                                </Button>
                            </div>
                        ) : (
                            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                <p className="text-sm text-muted-foreground">
                                    Ajoutez les caractéristiques qui sécurisent
                                    le choix des ports et mouillages.
                                </p>
                                <Button asChild variant="outline">
                                    <Link href={routes.boatSetup}>
                                        Configurer le bateau
                                        <ArrowRight className="size-4" />
                                    </Link>
                                </Button>
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card className="border-border/60">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Waves className="size-4 text-lagoon" />
                            Préparer
                        </CardTitle>
                    </CardHeader>

                    <CardContent className="space-y-3 text-sm">
                        <Link
                            href={routes.explore}
                            className="flex items-center justify-between border-b border-border/60 pb-3 hover:text-sea-blue"
                        >
                            <span className="flex items-center gap-2">
                                <Anchor className="size-4" />
                                Explorer les escales
                            </span>
                            <ArrowRight className="size-4" />
                        </Link>
                        <Link
                            href={routes.map}
                            className="flex items-center justify-between hover:text-sea-blue"
                        >
                            <span className="flex items-center gap-2">
                                <Compass className="size-4" />
                                Carte marine
                            </span>
                            <ArrowRight className="size-4" />
                        </Link>
                    </CardContent>
                </Card>
            </section>
        </div>
    );
}

function Metric({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <p className="text-xs text-muted-foreground">{label}</p>
            <p className="mt-1 font-semibold">{value}</p>
        </div>
    );
}
