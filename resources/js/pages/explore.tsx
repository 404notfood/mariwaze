import { Head, Link, router } from '@inertiajs/react';
import { Anchor, ArrowRight, Compass, MapPin, Route, Search, Ship } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { appRoutes } from '@/lib/mariwaze-routes';

type Spot = {
    slug: string;
    name: string;
    type: 'port' | 'anchorage';
    region: string | null;
    country_code: string | null;
    latitude: number;
    longitude: number;
    depth: string | number | null;
    rating: number | null;
    reviews_count: number;
    services?: string[];
    tags?: string[];
};

type Props = {
    filters: { q: string; type: string | null };
    spots: Spot[];
    counts: { ports: number; anchorages: number };
};

export default function Explore({ filters, spots, counts }: Props) {
    const [search, setSearch] = useState(filters.q ?? '');
    const [departure, setDeparture] = useState('');
    const [destination, setDestination] = useState('');

    const canContinue =
        departure.trim() !== '' &&
        destination.trim() !== '' &&
        departure.trim().toLocaleLowerCase() !== destination.trim().toLocaleLowerCase();

    const submitSearch = (event: React.FormEvent) => {
        event.preventDefault();
        router.get(appRoutes.explore, { q: search, type: filters.type ?? undefined }, { preserveState: true });
    };

    const filterType = (type: string | null) => {
        router.get(appRoutes.explore, { q: search, type: type ?? undefined }, { preserveState: true });
    };

    return (
        <>
            <Head title="Explorer" />
            <main className="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-8 p-4 md:p-6">
                <header>
                    <div className="mb-2 flex items-center gap-2 text-sea-blue">
                        <Compass className="size-5" />
                        <span className="text-sm font-medium">Préparer un itinéraire</span>
                    </div>
                    <h1 className="text-2xl font-bold md:text-3xl">Explorer les escales</h1>
                    <p className="mt-2 text-muted-foreground">
                        {counts.ports} ports et {counts.anchorages} mouillages issus d&apos;OpenStreetMap.
                    </p>
                </header>

                <section className="grid gap-5 border-y border-border/60 py-6 md:grid-cols-[1fr_auto_1fr] md:items-end">
                    <div>
                        <Label htmlFor="explorer-departure" className="mb-2 block">
                            Départ
                        </Label>
                        <Input
                            id="explorer-departure"
                            list="known-spots"
                            value={departure}
                            onChange={(event) => setDeparture(event.target.value)}
                            placeholder="Ex. Saint-Malo"
                            autoComplete="off"
                        />
                    </div>
                    <Route className="mb-2 hidden size-5 text-muted-foreground md:block" />
                    <div>
                        <Label htmlFor="explorer-destination" className="mb-2 block">
                            Arrivée
                        </Label>
                        <Input
                            id="explorer-destination"
                            list="known-spots"
                            value={destination}
                            onChange={(event) => setDestination(event.target.value)}
                            placeholder="Ex. Marseille"
                            autoComplete="off"
                        />
                    </div>
                </section>

                <datalist id="known-spots">
                    {spots.map((spot) => (
                        <option key={`${spot.type}-${spot.slug}`} value={spot.name} />
                    ))}
                </datalist>

                <div className="flex justify-end">
                    {canContinue ? (
                        <Button asChild className="bg-sea-blue hover:bg-sea-blue/90">
                            <Link
                                href={`/croisieres/nouvelle?departure=${encodeURIComponent(departure.trim())}&destination=${encodeURIComponent(destination.trim())}`}
                            >
                                Créer la croisière
                                <ArrowRight className="size-4" />
                            </Link>
                        </Button>
                    ) : (
                        <Button disabled>
                            Créer la croisière
                            <ArrowRight className="size-4" />
                        </Button>
                    )}
                </div>

                <section className="space-y-4 border-t border-border/60 pt-6">
                    <form onSubmit={submitSearch} className="flex flex-col gap-3 sm:flex-row">
                        <div className="relative flex-1">
                            <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                                placeholder="Rechercher un port ou un mouillage"
                                className="pl-9"
                            />
                        </div>
                        <Button type="submit" variant="outline">
                            Rechercher
                        </Button>
                    </form>

                    <div className="flex flex-wrap gap-2">
                        <FilterChip active={!filters.type} onClick={() => filterType(null)}>
                            Tout
                        </FilterChip>
                        <FilterChip active={filters.type === 'port'} onClick={() => filterType('port')}>
                            Ports
                        </FilterChip>
                        <FilterChip
                            active={filters.type === 'anchorage'}
                            onClick={() => filterType('anchorage')}
                        >
                            Mouillages
                        </FilterChip>
                    </div>

                    {spots.length === 0 ? (
                        <p className="py-8 text-center text-sm text-muted-foreground">
                            Aucune escale ne correspond à cette recherche.
                        </p>
                    ) : (
                        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            {spots.map((spot) => (
                                <Link
                                    key={`${spot.type}-${spot.slug}`}
                                    href={
                                        spot.type === 'port'
                                            ? appRoutes.port(spot.slug)
                                            : appRoutes.anchorage(spot.slug)
                                    }
                                >
                                    <Card className="h-full border-border/60 transition hover:border-sea-blue/60">
                                        <CardContent className="pt-5">
                                            <div className="flex items-start justify-between gap-2">
                                                <div className="flex items-start gap-2">
                                                    {spot.type === 'port' ? (
                                                        <Ship className="mt-0.5 size-4 shrink-0 text-sea-blue" />
                                                    ) : (
                                                        <Anchor className="mt-0.5 size-4 shrink-0 text-lagoon" />
                                                    )}
                                                    <div>
                                                        <p className="font-medium">{spot.name}</p>
                                                        <p className="flex items-center gap-1 text-xs text-muted-foreground">
                                                            <MapPin className="size-3" />
                                                            {spot.region ??
                                                                `${spot.latitude.toFixed(3)}, ${spot.longitude.toFixed(3)}`}
                                                        </p>
                                                    </div>
                                                </div>
                                                {spot.rating !== null && (
                                                    <span className="text-sm text-amber-500">
                                                        ★ {spot.rating}
                                                    </span>
                                                )}
                                            </div>

                                            {(spot.services?.length || spot.tags?.length) && (
                                                <div className="mt-3 flex flex-wrap gap-1">
                                                    {(spot.services ?? spot.tags ?? [])
                                                        .slice(0, 3)
                                                        .map((item) => (
                                                            <Badge
                                                                key={item}
                                                                variant="secondary"
                                                                className="text-xs"
                                                            >
                                                                {item}
                                                            </Badge>
                                                        ))}
                                                </div>
                                            )}
                                        </CardContent>
                                    </Card>
                                </Link>
                            ))}
                        </div>
                    )}
                </section>
            </main>
        </>
    );
}

function FilterChip({
    active,
    onClick,
    children,
}: {
    active: boolean;
    onClick: () => void;
    children: React.ReactNode;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={
                active
                    ? 'rounded-full bg-sea-blue px-4 py-1.5 text-sm font-medium text-white'
                    : 'rounded-full border border-border/60 px-4 py-1.5 text-sm text-muted-foreground hover:border-sea-blue/60'
            }
        >
            {children}
        </button>
    );
}

Explore.layout = () => ({ breadcrumbs: [{ title: 'Explorer', href: appRoutes.explore }] });
