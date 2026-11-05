import { Head, Link, router } from '@inertiajs/react';
import Anchor from 'lucide-react/icons/anchor';
import ArrowRight from 'lucide-react/icons/arrow-right';
import Compass from 'lucide-react/icons/compass';
import LoaderCircle from 'lucide-react/icons/loader-circle';
import MapPin from 'lucide-react/icons/map-pin';
import Route from 'lucide-react/icons/route';
import Search from 'lucide-react/icons/search';
import Ship from 'lucide-react/icons/ship';
import { useMemo, useState } from 'react';
import {
    Chip,
    Instrument,
    PageHeader,
    Status,
} from '@/components/mariwaze/passerelle';
import PlaceSearch from '@/components/mariwaze/place-search';
import type { Place } from '@/components/mariwaze/place-search';
import PlannerMap from '@/components/mariwaze/planner-map';
import type { NavigationPoint } from '@/components/mariwaze/planner-map';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useDesign } from '@/hooks/use-design';
import { appRoutes } from '@/lib/mariwaze-routes';
import { cn } from '@/lib/utils';

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
const nf = new Intl.NumberFormat('fr-FR');
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

    const { design } = useDesign();

    // « Au large » : carte plein écran, liste et fiche en feuilles flottantes.
    if (design === 'large') {
        return (
            <ExploreLarge
                key={JSON.stringify([filters, pagination.current_page])}
                filters={filters}
                spots={spots}
                boats={boats}
                pagination={pagination}
            />
        );
    }

    return (
        <>
            <Head title="Ports et mouillages" />
            <main className="flex w-full flex-1 flex-col">
                <PageHeader
                    label="Catalogue · OpenStreetMap et NGA World Port Index"
                    title="Ports & mouillages"
                />
                <div className="grid grid-cols-2 border-b border-border">
                    <Instrument
                        label="Ports"
                        value={nf.format(counts.ports)}
                        className="border-r border-border"
                    />
                    <Instrument
                        label="Mouillages"
                        value={nf.format(counts.anchorages)}
                    />
                </div>

                <section className="grid gap-4 border-b border-border px-5 py-4 md:grid-cols-[1fr_auto_1fr_auto] md:items-end">
                    <p className="pw-title flex items-center gap-2 md:col-span-4">
                        <Compass className="size-4 text-primary" />
                        Préparer un itinéraire
                    </p>
                    <div>
                        <Label
                            htmlFor="explorer-departure"
                            className="pw-label mb-1.5 block"
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
                            className="pw-label mb-1.5 block"
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
                    <div>
                        {canContinue ? (
                            <Button asChild>
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
                </section>

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
        <section id="escales" className="scroll-mt-6 space-y-4 px-5 py-4">
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
                <label className="flex flex-col gap-1.5">
                    <span className="pw-label">Profondeur minimale (m)</span>
                    <Input
                        type="number"
                        min="0"
                        max="100"
                        step="0.1"
                        value={depth}
                        onChange={(event) => setDepth(event.target.value)}
                    />
                </label>
                <label className="flex flex-col gap-1.5">
                    <span className="pw-label">Service au port</span>
                    <select
                        className="h-9 w-full rounded-sm border border-input bg-background px-2 text-sm"
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
                <label className="flex flex-col gap-1.5">
                    <span className="pw-label">Mouillage protégé du</span>
                    <select
                        className="h-9 w-full rounded-sm border border-input bg-background px-2 text-sm"
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
                <label className="flex flex-col gap-1.5">
                    <span className="pw-label">Compatibilité bateau</span>
                    <select
                        className="h-9 w-full rounded-sm border border-input bg-background px-2 text-sm"
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
                <label className="flex flex-col gap-1.5">
                    <span className="pw-label">Pays ou territoire</span>
                    <select
                        className="h-9 w-full rounded-sm border border-input bg-background px-2 text-sm"
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
                className="pw-label !text-primary hover:underline"
            >
                Réinitialiser les filtres
            </Link>
            <p role="status" className="pw-label">
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
                <ul className="divide-y divide-border border-y border-line-strong">
                    {spots.map((spot) => (
                        <li key={`${spot.type}-${spot.slug}`}>
                            <Link
                                href={
                                    spot.type === 'port'
                                        ? appRoutes.port(spot.slug)
                                        : appRoutes.anchorage(spot.slug)
                                }
                                className="grid gap-2 px-1 py-3 transition hover:bg-raised md:grid-cols-[minmax(0,2fr)_minmax(0,1.6fr)_auto_auto] md:items-center md:gap-4"
                            >
                                <div className="flex min-w-0 items-start gap-2.5">
                                    {spot.type === 'port' ? (
                                        <Ship className="mt-1 size-4 shrink-0 text-primary" />
                                    ) : (
                                        <Anchor className="mt-1 size-4 shrink-0 text-good" />
                                    )}
                                    <div className="min-w-0">
                                        <p className="truncate text-base font-semibold">
                                            {spot.name}
                                        </p>
                                        <p className="flex items-center gap-1 truncate text-sm text-muted-foreground">
                                            <MapPin className="size-3 shrink-0" />
                                            {spot.region ??
                                                `${spot.latitude.toFixed(3)}, ${spot.longitude.toFixed(3)}`}
                                            {spot.country_code
                                                ? ` · ${spot.country_code}`
                                                : ''}
                                        </p>
                                    </div>
                                </div>

                                <div className="flex flex-wrap gap-1">
                                    {(spot.services ?? spot.tags ?? [])
                                        .slice(0, 3)
                                        .map((item) => (
                                            <Chip key={item}>{item}</Chip>
                                        ))}
                                    {!!spot.protected_from?.length && (
                                        <Chip>
                                            Abri {spot.protected_from.join(' ')}
                                        </Chip>
                                    )}
                                </div>

                                <div className="md:text-right">
                                    <span className="pw-label md:hidden">
                                        Profondeur{' '}
                                    </span>
                                    {spot.depth === null ? (
                                        <Status tone="warn">
                                            Fond à vérifier
                                        </Status>
                                    ) : (
                                        <span className="pw-figure text-sm">
                                            {typeof spot.depth === 'number'
                                                ? `${nf.format(spot.depth)} m`
                                                : spot.depth}
                                        </span>
                                    )}
                                </div>

                                <span className="pw-figure w-12 text-sm text-muted-foreground md:text-right">
                                    {spot.rating !== null
                                        ? `★ ${spot.rating}`
                                        : '—'}
                                </span>
                            </Link>
                        </li>
                    ))}
                </ul>
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
                    ? 'rounded-sm border border-primary bg-accent px-3 py-1 font-mono text-[11px] tracking-[0.06em] text-primary uppercase'
                    : 'rounded-sm border border-line-strong px-3 py-1 font-mono text-[11px] tracking-[0.06em] text-muted-foreground uppercase hover:text-foreground'
            }
        >
            {children}
        </button>
    );
}

Explore.layout = () => ({
    breadcrumbs: [{ title: 'Ports & mouillages', href: appRoutes.explore }],
});

/* ------------------------------------------------------------------
 * Design « Au large » : la carte des escales en plein écran,
 * la liste à gauche et la fiche de l'escale choisie à droite.
 * ------------------------------------------------------------------ */

// Cadrage des escales hors des feuilles (liste à gauche, fiche à droite).
const EXPLORE_FIT_PADDING = {
    paddingTopLeft: [440, 110] as [number, number],
    paddingBottomRight: [390, 40] as [number, number],
};

const largeSheet =
    'relative rounded-[24px] bg-card shadow-[var(--soft-shadow)] lg:absolute';

const serviceLabels: Record<string, string> = {
    has_fuel: 'Carburant',
    has_water: 'Eau',
    has_provisions: 'Avitaillement',
};

function depthLabel(spot: Spot): string | null {
    if (spot.depth === null) {
        return null;
    }

    return typeof spot.depth === 'number'
        ? `${nf.format(spot.depth)} m`
        : String(spot.depth);
}

function ExploreLarge({
    filters,
    spots,
    boats,
    pagination,
}: Pick<Props, 'filters' | 'spots' | 'boats' | 'pagination'>) {
    const [search, setSearch] = useState(filters.q ?? '');
    const [pending, setPending] = useState(false);
    const [selectedKey, setSelectedKey] = useState<string | null>(
        spots[0] ? `${spots[0].type}-${spots[0].slug}` : null,
    );

    const selected =
        spots.find((spot) => `${spot.type}-${spot.slug}` === selectedKey) ??
        null;
    const boat = boats[0] ?? null;
    const boatFilterOn =
        boat !== null && filters.boat_id?.toString() === boat.id.toString();

    const visit = (changes: Partial<Record<string, string | null>>) => {
        const next: Record<string, string | undefined> = {
            q: search || undefined,
            type: filters.type ?? undefined,
            min_depth: filters.min_depth || undefined,
            service: filters.service || undefined,
            protection: filters.protection || undefined,
            boat_id: filters.boat_id?.toString() || undefined,
            country: filters.country || undefined,
        };

        Object.entries(changes).forEach(([key, value]) => {
            next[key] = value || undefined;
        });

        router.get(appRoutes.explore, next, {
            preserveState: true,
            preserveScroll: true,
            onStart: () => setPending(true),
            onFinish: () => setPending(false),
        });
    };

    const points = useMemo(
        (): NavigationPoint[] =>
            spots.map((spot) => ({
                name: spot.name,
                latitude: spot.latitude,
                longitude: spot.longitude,
                kind: spot.type === 'port' ? 'port' : 'mouillage',
            })),
        [spots],
    );

    const selectFromMap = (point: NavigationPoint) => {
        const spot = spots.find(
            (item) =>
                item.name === point.name &&
                Math.abs(item.latitude - point.latitude) < 1e-6,
        );

        if (spot) {
            setSelectedKey(`${spot.type}-${spot.slug}`);
        }
    };

    const typeChip = (type: string | null, label: string) => {
        const active = (filters.type ?? null) === type;

        return (
            <button
                type="button"
                disabled={pending}
                aria-pressed={active}
                onClick={() => visit({ type })}
                className={cn(
                    'rounded-full px-3 py-1 text-[13px] font-medium transition-colors',
                    active
                        ? 'bg-primary text-primary-foreground'
                        : 'bg-background hover:bg-accent',
                )}
            >
                {label}
            </button>
        );
    };

    return (
        <>
            <Head title="Ports et mouillages" />
            <div className="relative flex flex-col gap-3 pb-4 lg:fixed lg:inset-0 lg:block lg:pb-0">
                <div className="mw-fullbleed-map relative isolate h-[55vh] bg-[var(--chart-water-1)] lg:absolute lg:inset-0 lg:h-auto">
                    <PlannerMap
                        points={points}
                        routeAvailable={false}
                        onMapClick={selectFromMap}
                        className="absolute inset-0"
                        zoomPosition="topright"
                        fitPadding={EXPLORE_FIT_PADDING}
                    />
                </div>

                {/* Liste */}
                <aside
                    className={cn(
                        largeSheet,
                        'mx-4 flex flex-col gap-3 p-5 lg:top-[88px] lg:bottom-5 lg:left-5 lg:mx-0 lg:w-[380px]',
                    )}
                >
                    <h1 className="text-[22px] leading-7 font-semibold tracking-[-0.01em]">
                        Escales et mouillages
                    </h1>
                    <form
                        onSubmit={(event) => {
                            event.preventDefault();
                            visit({});
                        }}
                        className="flex h-11 items-center gap-2.5 rounded-full bg-background px-4 text-muted-foreground"
                    >
                        {pending ? (
                            <LoaderCircle className="size-4 shrink-0 animate-spin" />
                        ) : (
                            <Search className="size-4 shrink-0" />
                        )}
                        <input
                            aria-label="Rechercher une escale"
                            maxLength={120}
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Un port, une baie, une île…"
                            className="h-full min-w-0 flex-1 bg-transparent text-sm text-foreground outline-none placeholder:text-muted-foreground"
                        />
                    </form>

                    <div className="flex flex-wrap gap-1.5">
                        {typeChip(null, 'Tout')}
                        {typeChip('port', 'Ports')}
                        {typeChip('anchorage', 'Mouillages')}
                        {boat && (
                            <button
                                type="button"
                                disabled={pending}
                                aria-pressed={boatFilterOn}
                                onClick={() =>
                                    visit({
                                        boat_id: boatFilterOn
                                            ? null
                                            : boat.id.toString(),
                                    })
                                }
                                className={cn(
                                    'rounded-full px-3 py-1 text-[13px] font-medium transition-colors',
                                    boatFilterOn
                                        ? 'bg-primary text-primary-foreground'
                                        : 'bg-background hover:bg-accent',
                                )}
                            >
                                Accessible à {nf.format(boat.draft)} m
                            </button>
                        )}
                        {Object.entries(serviceLabels).map(([value, label]) => {
                            const active = filters.service === value;

                            return (
                                <button
                                    key={value}
                                    type="button"
                                    disabled={pending}
                                    aria-pressed={active}
                                    onClick={() =>
                                        visit({
                                            service: active ? null : value,
                                            protection: null,
                                        })
                                    }
                                    className={cn(
                                        'rounded-full px-3 py-1 text-[13px] font-medium transition-colors',
                                        active
                                            ? 'bg-primary text-primary-foreground'
                                            : 'bg-background hover:bg-accent',
                                    )}
                                >
                                    {label}
                                </button>
                            );
                        })}
                    </div>

                    <p role="status" className="text-xs text-muted-foreground">
                        {pagination.total === 0
                            ? 'Aucune escale trouvée'
                            : `${pagination.from}–${pagination.to} sur ${nf.format(pagination.total)} escale${pagination.total > 1 ? 's' : ''}`}
                    </p>

                    <ul className="-mx-2 min-h-0 flex-1 space-y-0.5 overflow-y-auto">
                        {spots.map((spot) => {
                            const key = `${spot.type}-${spot.slug}`;
                            const depth = depthLabel(spot);

                            return (
                                <li key={key}>
                                    <button
                                        type="button"
                                        onClick={() => setSelectedKey(key)}
                                        aria-current={
                                            key === selectedKey
                                                ? 'true'
                                                : undefined
                                        }
                                        className={cn(
                                            'grid w-full grid-cols-[10px_minmax(0,1fr)_auto] items-center gap-3 rounded-2xl px-3 py-2.5 text-left transition-colors',
                                            key === selectedKey
                                                ? 'bg-accent'
                                                : 'hover:bg-background',
                                        )}
                                    >
                                        <i
                                            className={cn(
                                                'size-2.5 rounded-full',
                                                depth === null
                                                    ? 'bg-warn'
                                                    : 'bg-good',
                                            )}
                                        />
                                        <span className="min-w-0">
                                            <span className="block truncate text-[15px] font-semibold">
                                                {spot.name}
                                            </span>
                                            <span className="block truncate text-xs text-muted-foreground">
                                                {spot.type === 'port'
                                                    ? 'Port'
                                                    : 'Mouillage'}
                                                {spot.region
                                                    ? ` · ${spot.region}`
                                                    : ''}
                                            </span>
                                        </span>
                                        <span className="text-right text-xs text-muted-foreground tabular-nums">
                                            {depth ?? 'Fond ?'}
                                        </span>
                                    </button>
                                </li>
                            );
                        })}
                        {spots.length === 0 && (
                            <li className="px-3 py-6 text-sm text-muted-foreground">
                                Aucune escale ne correspond à cette recherche.
                            </li>
                        )}
                    </ul>

                    {pagination.last_page > 1 && (
                        <nav
                            aria-label="Pagination des escales"
                            className="flex items-center justify-between gap-2 border-t border-border pt-3"
                        >
                            {pagination.previous_url ? (
                                <Link
                                    href={pagination.previous_url}
                                    preserveState
                                    className="rounded-full bg-background px-3 py-1.5 text-sm font-medium hover:bg-accent"
                                >
                                    ← Précédent
                                </Link>
                            ) : (
                                <span />
                            )}
                            <span className="text-xs text-muted-foreground">
                                Page {pagination.current_page} sur{' '}
                                {pagination.last_page}
                            </span>
                            {pagination.next_url ? (
                                <Link
                                    href={pagination.next_url}
                                    preserveState
                                    className="rounded-full bg-background px-3 py-1.5 text-sm font-medium hover:bg-accent"
                                >
                                    Suivant →
                                </Link>
                            ) : (
                                <span />
                            )}
                        </nav>
                    )}
                </aside>

                {/* Fiche de l'escale choisie */}
                {selected && (
                    <section
                        className={cn(
                            largeSheet,
                            'mx-4 p-6 lg:right-5 lg:bottom-8 lg:mx-0 lg:w-[350px]',
                        )}
                    >
                        <span
                            className={cn(
                                'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                depthLabel(selected) === null
                                    ? 'bg-warn-soft'
                                    : 'bg-good-soft',
                            )}
                        >
                            <i
                                className={cn(
                                    'size-1.5 rounded-full',
                                    depthLabel(selected) === null
                                        ? 'bg-warn'
                                        : 'bg-good',
                                )}
                            />
                            {depthLabel(selected) === null
                                ? 'Profondeur à vérifier'
                                : selected.type === 'port'
                                  ? 'Port'
                                  : 'Mouillage'}
                        </span>
                        <h2 className="mt-2 text-[22px] leading-7 font-semibold tracking-[-0.01em]">
                            {selected.name}
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            {selected.region ??
                                `${selected.latitude.toFixed(3)}, ${selected.longitude.toFixed(3)}`}
                            {selected.country_code
                                ? ` · ${selected.country_code}`
                                : ''}
                        </p>

                        <div className="my-4 grid grid-cols-3 gap-2">
                            <div className="rounded-2xl bg-background px-3 py-2">
                                <p className="font-medium tabular-nums">
                                    {depthLabel(selected) ?? '—'}
                                </p>
                                <p className="text-[11px] text-muted-foreground">
                                    profondeur
                                </p>
                            </div>
                            <div className="rounded-2xl bg-background px-3 py-2">
                                <p className="font-medium tabular-nums">
                                    {selected.rating !== null
                                        ? `★ ${selected.rating}`
                                        : '—'}
                                </p>
                                <p className="text-[11px] text-muted-foreground">
                                    {selected.reviews_count} avis
                                </p>
                            </div>
                            <div className="rounded-2xl bg-background px-3 py-2">
                                <p className="truncate font-medium">
                                    {selected.protected_from?.length
                                        ? selected.protected_from.join(' ')
                                        : '—'}
                                </p>
                                <p className="text-[11px] text-muted-foreground">
                                    abri
                                </p>
                            </div>
                        </div>

                        {!!(
                            selected.services?.length || selected.tags?.length
                        ) && (
                            <div className="mb-4 flex flex-wrap gap-1.5">
                                {(selected.services ?? selected.tags ?? [])
                                    .slice(0, 5)
                                    .map((item) => (
                                        <span
                                            key={item}
                                            className="rounded-full bg-accent px-2.5 py-0.5 text-xs font-medium"
                                        >
                                            {item}
                                        </span>
                                    ))}
                            </div>
                        )}

                        <div className="flex flex-wrap gap-2">
                            <Button asChild>
                                <Link
                                    href={
                                        selected.type === 'port'
                                            ? appRoutes.port(selected.slug)
                                            : appRoutes.anchorage(selected.slug)
                                    }
                                >
                                    Voir la fiche
                                </Link>
                            </Button>
                            <Button asChild variant="secondary">
                                <Link
                                    href={`/croisieres/nouvelle?${new URLSearchParams({ destination: selected.name }).toString()}`}
                                >
                                    Y aller
                                </Link>
                            </Button>
                        </div>
                    </section>
                )}
            </div>
        </>
    );
}
