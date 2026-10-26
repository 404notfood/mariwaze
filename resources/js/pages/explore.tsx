import { Head, Link, router } from '@inertiajs/react';
import Anchor from 'lucide-react/icons/anchor';
import ArrowRight from 'lucide-react/icons/arrow-right';
import Compass from 'lucide-react/icons/compass';
import LoaderCircle from 'lucide-react/icons/loader-circle';
import MapPin from 'lucide-react/icons/map-pin';
import Route from 'lucide-react/icons/route';
import Search from 'lucide-react/icons/search';
import Ship from 'lucide-react/icons/ship';
import { useState } from 'react';
import PlaceSearch from '@/components/mariwaze/place-search';
import type { Place } from '@/components/mariwaze/place-search';
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
    protected_from?: string[];
};

type Props = {
    filters: {
        q: string;
        type: string | null;
        min_depth?: string;
        service?: string;
        protection?: string;
        boat_id?: number;
        country?: string;
    };
    boats: { id: number; name: string; draft: number }[];
    countries: string[];
    pagination: {
        total: number;
        from: number | null;
        to: number | null;
        current_page: number;
        last_page: number;
        previous_url: string | null;
        next_url: string | null;
    };
    spots: Spot[];
    counts: { ports: number; anchorages: number };
};
const countryNames = new Intl.DisplayNames(['fr'], { type: 'region' });

export default function Explore({
    filters,
    spots,
    counts,
    boats,
    pagination,
    countries,
}: Props) {
    const [departure, setDeparture] = useState('');
    const [destination, setDestination] = useState('');
    const [departurePlace, setDeparturePlace] = useState<Place | null>(null);
    const [destinationPlace, setDestinationPlace] = useState<Place | null>(
        null,
    );

    const canContinue =
        departure.trim() !== '' &&
        destination.trim() !== '' &&
        (departurePlace?.reference && destinationPlace?.reference
            ? departurePlace.reference !== destinationPlace.reference
            : departure.trim().toLocaleLowerCase() !==
              destination.trim().toLocaleLowerCase());

    return (
        <>
            <Head title="Explorer" />
            <main className="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-8 p-4 md:p-6">
                <header>
                    <div className="mb-2 flex items-center gap-2 text-sea-blue">
                        <Compass className="size-5" />
                        <span className="text-sm font-medium">
                            Préparer un itinéraire
                        </span>
                    </div>
                    <h1 className="text-2xl font-bold md:text-3xl">
                        Explorer les escales
                    </h1>
                    <p className="mt-2 text-muted-foreground">
                        {counts.ports} ports et {counts.anchorages} mouillages
                        dans le catalogue mondial.
                        <span className="block text-xs">
                            Sources : OpenStreetMap et NGA World Port Index.
                        </span>
                    </p>
                </header>

                <section className="grid gap-5 border-y border-border/60 py-6 md:grid-cols-[1fr_auto_1fr] md:items-end">
                    <div>
                        <Label
                            htmlFor="explorer-departure"
                            className="mb-2 block"
                        >
                            Départ
                        </Label>
                        <PlaceSearch
                            id="explorer-departure"
                            label="Départ"
                            value={departure}
                            onChange={(value) => {
                                setDeparture(value);
                                setDeparturePlace(null);
                            }}
                            onSelect={(place) => {
                                setDeparture(place.name);
                                setDeparturePlace(place);
                            }}
                        />
                    </div>
                    <Route className="mb-2 hidden size-5 text-muted-foreground md:block" />
                    <div>
                        <Label
                            htmlFor="explorer-destination"
                            className="mb-2 block"
                        >
                            Arrivée
                        </Label>
                        <PlaceSearch
                            id="explorer-destination"
                            label="Arrivée"
                            value={destination}
                            onChange={(value) => {
                                setDestination(value);
                                setDestinationPlace(null);
                            }}
                            onSelect={(place) => {
                                setDestination(place.name);
                                setDestinationPlace(place);
                            }}
                        />
                    </div>
                </section>

                <div className="flex justify-end">
                    {canContinue ? (
                        <Button
                            asChild
                            className="bg-sea-blue hover:bg-sea-blue/90"
                        >
                            <Link
                                href={`/croisieres/nouvelle?${new URLSearchParams({ departure: departure.trim(), destination: destination.trim(), ...(departurePlace?.reference ? { departure_place: departurePlace.reference } : {}), ...(destinationPlace?.reference ? { destination_place: destinationPlace.reference } : {}) }).toString()}`}
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

                <ExploreCatalogue
                    key={JSON.stringify([filters, pagination.current_page])}
                    filters={filters}
                    boats={boats}
                    spots={spots}
                    pagination={pagination}
                    countries={countries}
                />
            </main>
        </>
    );
}

function ExploreCatalogue({
    filters,
    boats,
    spots,
    pagination,
    countries,
}: Pick<Props, 'filters' | 'boats' | 'spots' | 'pagination' | 'countries'>) {
    const [search, setSearch] = useState(filters.q ?? '');
    const [depth, setDepth] = useState(filters.min_depth ?? '');
    const [service, setService] = useState(filters.service ?? '');
    const [protection, setProtection] = useState(filters.protection ?? '');
    const [boat, setBoat] = useState(filters.boat_id?.toString() ?? '');
    const [pending, setPending] = useState(false);
    const [country, setCountry] = useState(filters.country ?? '');

    const visit = (type: string | null = filters.type) => {
        router.get(
            appRoutes.explore,
            {
                q: search,
                type: type ?? undefined,
                min_depth: depth || undefined,
                service: service || undefined,
                protection: protection || undefined,
                boat_id: boat || undefined,
                country: country || undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
                onStart: () => setPending(true),
                onFinish: () => setPending(false),
            },
        );
    };

    const submitSearch = (event: React.FormEvent) => {
        event.preventDefault();
        visit();
    };
    const filterType = (type: string | null) => visit(type);

    return (
        <section
            id="escales"
            className="scroll-mt-6 space-y-4 border-t border-border/60 pt-6"
        >
            <form
                onSubmit={submitSearch}
                className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3"
            >
                <div className="relative flex-1">
                    <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        aria-label="Rechercher une escale"
                        maxLength={120}
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Rechercher un port ou un mouillage"
                        className="pl-9"
                    />
                </div>
                <label className="text-sm">
                    Profondeur minimale (m)
                    <Input
                        type="number"
                        min="0"
                        max="100"
                        step="0.1"
                        value={depth}
                        onChange={(event) => setDepth(event.target.value)}
                    />
                </label>
                <label className="text-sm">
                    Service au port
                    <select
                        className="mt-1 h-9 w-full rounded-md border bg-background px-2"
                        value={service}
                        onChange={(event) => {
                            setService(event.target.value);

                            if (event.target.value) {
                                setProtection('');
                            }
                        }}
                    >
                        <option value="">Tous les services</option>
                        <option value="has_fuel">Carburant</option>
                        <option value="has_water">Eau</option>
                        <option value="has_electricity">Électricité</option>
                        <option value="has_shower">Douches</option>
                        <option value="has_repair">Réparation</option>
                        <option value="has_provisions">Avitaillement</option>
                    </select>
                </label>
                <label className="text-sm">
                    Mouillage protégé du
                    <select
                        className="mt-1 h-9 w-full rounded-md border bg-background px-2"
                        value={protection}
                        onChange={(event) => {
                            setProtection(event.target.value);

                            if (event.target.value) {
                                setService('');
                            }
                        }}
                    >
                        <option value="">Toutes les orientations</option>
                        {Object.entries({
                            N: 'Nord',
                            NE: 'Nord-est',
                            E: 'Est',
                            SE: 'Sud-est',
                            S: 'Sud',
                            SW: 'Sud-ouest',
                            W: 'Ouest',
                            NW: 'Nord-ouest',
                        }).map(([value, label]) => (
                            <option key={value} value={value}>
                                {label}
                            </option>
                        ))}
                    </select>
                </label>
                <label className="text-sm">
                    Compatibilité bateau
                    <select
                        className="mt-1 h-9 w-full rounded-md border bg-background px-2"
                        value={boat}
                        onChange={(event) => setBoat(event.target.value)}
                    >
                        <option value="">Tous les bateaux</option>
                        {boats.map((item) => (
                            <option key={item.id} value={item.id}>
                                {item.name} · {item.draft} m
                            </option>
                        ))}
                    </select>
                </label>
                <label className="text-sm">
                    Pays ou territoire
                    <select
                        className="mt-1 h-9 w-full rounded-md border bg-background px-2"
                        value={country}
                        onChange={(event) => setCountry(event.target.value)}
                    >
                        <option value="">Tous les pays et territoires</option>
                        {countries.map((code) => (
                            <option key={code} value={code}>
                                {countryNames.of(code) ?? code} ({code})
                            </option>
                        ))}
                    </select>
                </label>
                <Button type="submit" variant="outline" disabled={pending}>
                    {pending && (
                        <LoaderCircle className="size-4 animate-spin" />
                    )}
                    {pending ? 'Recherche…' : 'Rechercher'}
                </Button>
            </form>
            <p className="text-xs text-muted-foreground">
                Les filtres de profondeur excluent les fonds non renseignés. La
                compatibilité utilise le tirant d’eau + 0,5 m et les dimensions
                connues ; elle reste à confirmer auprès de l’escale.
            </p>
            <Link
                href="/explorer"
                preserveState
                preserveScroll
                className="text-sm text-sea-blue underline"
            >
                Réinitialiser les filtres
            </Link>
            <p role="status" className="text-sm text-muted-foreground">
                {pagination.total === 0
                    ? 'Aucune escale trouvée'
                    : `${pagination.from}–${pagination.to} sur ${pagination.total} escale${pagination.total > 1 ? 's' : ''}`}
            </p>

            <div className="flex flex-wrap gap-2">
                <FilterChip
                    disabled={pending}
                    active={!filters.type}
                    onClick={() => filterType(null)}
                >
                    Tout
                </FilterChip>
                <FilterChip
                    disabled={pending}
                    active={filters.type === 'port'}
                    onClick={() => filterType('port')}
                >
                    Ports
                </FilterChip>
                <FilterChip
                    disabled={pending}
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
                                                <p className="font-medium">
                                                    {spot.name}
                                                </p>
                                                <p className="flex items-center gap-1 text-xs text-muted-foreground">
                                                    <MapPin className="size-3" />
                                                    {spot.region ??
                                                        `${spot.latitude.toFixed(3)}, ${spot.longitude.toFixed(3)}`}
                                                    {spot.country_code
                                                        ? ` · ${spot.country_code}`
                                                        : ''}
                                                </p>
                                            </div>
                                        </div>
                                        {spot.rating !== null && (
                                            <span className="text-sm text-amber-500">
                                                ★ {spot.rating}
                                            </span>
                                        )}
                                    </div>

                                    <p className="mt-2 text-xs text-muted-foreground">
                                        Profondeur :{' '}
                                        {spot.depth === null
                                            ? 'non renseignée'
                                            : typeof spot.depth === 'number'
                                              ? `${spot.depth} m`
                                              : spot.depth}
                                    </p>
                                    {!!spot.protected_from?.length && (
                                        <p className="mt-1 text-xs text-muted-foreground">
                                            Protection :{' '}
                                            {spot.protected_from.join(', ')}
                                        </p>
                                    )}
                                    {(spot.services?.length ||
                                        spot.tags?.length) && (
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
            {pagination.last_page > 1 && (
                <nav
                    aria-label="Pagination des escales"
                    className="flex flex-wrap items-center justify-between gap-3 border-t border-border/60 pt-4"
                >
                    <Button
                        asChild={pagination.previous_url !== null}
                        variant="outline"
                        disabled={pagination.previous_url === null}
                    >
                        {pagination.previous_url ? (
                            <Link
                                href={`${pagination.previous_url}#escales`}
                                preserveState
                            >
                                Précédent
                            </Link>
                        ) : (
                            <span>Précédent</span>
                        )}
                    </Button>
                    <span
                        className="text-sm text-muted-foreground"
                        aria-current="page"
                    >
                        Page {pagination.current_page} sur{' '}
                        {pagination.last_page}
                    </span>
                    <Button
                        asChild={pagination.next_url !== null}
                        variant="outline"
                        disabled={pagination.next_url === null}
                    >
                        {pagination.next_url ? (
                            <Link
                                href={`${pagination.next_url}#escales`}
                                preserveState
                            >
                                Suivant
                            </Link>
                        ) : (
                            <span>Suivant</span>
                        )}
                    </Button>
                </nav>
            )}
        </section>
    );
}

function FilterChip({
    active,
    onClick,
    children,
    disabled,
}: {
    active: boolean;
    onClick: () => void;
    children: React.ReactNode;
    disabled: boolean;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            disabled={disabled}
            aria-pressed={active}
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

Explore.layout = () => ({
    breadcrumbs: [{ title: 'Explorer', href: appRoutes.explore }],
});
