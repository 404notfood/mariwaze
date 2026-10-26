import { Head, Link, useForm } from '@inertiajs/react';
import ArrowLeft from 'lucide-react/icons/arrow-left';
import ArrowRight from 'lucide-react/icons/arrow-right';
import MapPin from 'lucide-react/icons/map-pin';
import Ship from 'lucide-react/icons/ship';
import { Children, cloneElement, isValidElement, useId } from 'react';
import { useEffect, useMemo, useState } from 'react';
import PlaceSearch from '@/components/mariwaze/place-search';
import type { Place } from '@/components/mariwaze/place-search';
import PlannerMap from '@/components/mariwaze/planner-map';
import type { NavigationPoint } from '@/components/mariwaze/planner-map';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { gpsPlace, gpsPlaceFromReference } from '@/lib/gps-place';
import { appRoutes } from '@/lib/mariwaze-routes';

type CruiseForm = {
    departure_place: string | null;
    destination_place: string | null;
    boat_id: number | null;
    navigation_area: string;
    title: string;
    departure_port: string;
    destination_port: string;
    departure_date: string;
    duration_days: number;
    auto_duration: boolean;
    crew_count: number;
    navigation_style: 'Rapide' | 'Confort' | 'Découverte' | 'Famille';
};
type ExistingCruise = Omit<CruiseForm, 'auto_duration'> & { slug: string };
type Prefill = {
    departure_port?: string;
    destination_port?: string;
    departure_place?: string | null;
    destination_place?: string | null;
};

export default function CreateCruise({
    cruise,
    prefill,
    boats = [],
}: {
    cruise?: ExistingCruise;
    prefill?: Prefill;
    boats?: { id: number; name: string; draft: number }[];
}) {
    const { data, setData, post, put, processing, errors } =
        useForm<CruiseForm>({
            departure_place:
                cruise?.departure_place ?? prefill?.departure_place ?? null,
            destination_place:
                cruise?.destination_place ?? prefill?.destination_place ?? null,
            boat_id: cruise ? cruise.boat_id : (boats[0]?.id ?? null),
            navigation_area: cruise?.navigation_area ?? 'coastal',
            title: cruise?.title ?? '',
            departure_port:
                cruise?.departure_port ?? prefill?.departure_port ?? '',
            destination_port:
                cruise?.destination_port ?? prefill?.destination_port ?? '',
            departure_date: cruise?.departure_date ?? '',
            duration_days: cruise?.duration_days ?? 7,
            auto_duration: cruise === undefined,
            crew_count: cruise?.crew_count ?? 2,
            navigation_style: cruise?.navigation_style ?? 'Confort',
        });
    const editing = cruise !== undefined;
    const [calculation, setCalculation] = useState<{
        key: string;
        failed: boolean;
    } | null>(null);
    const calculationKey = JSON.stringify([
        data.departure_port,
        data.destination_port,
        data.departure_place,
        data.destination_place,
        data.navigation_style,
    ]);
    const calculationError =
        calculation?.key === calculationKey && calculation.failed;
    const calculationPending =
        data.auto_duration &&
        data.departure_port.trim() !== '' &&
        data.destination_port.trim() !== '' &&
        calculation?.key !== calculationKey;
    const [picking, setPicking] = useState<'departure' | 'destination'>(
        'departure',
    );
    const [locations, setLocations] = useState<Record<string, Place>>(() => {
        const start = gpsPlaceFromReference(
            cruise?.departure_place ?? prefill?.departure_place,
        );
        const end = gpsPlaceFromReference(
            cruise?.destination_place ?? prefill?.destination_place,
        );

        return {
            ...(start ? { departure: start } : {}),
            ...(end ? { destination: end } : {}),
        };
    });
    const mapPoints = useMemo<NavigationPoint[]>(
        () =>
            Object.entries(locations).map(([side, place]) => ({
                name: place.name,
                latitude: place.latitude,
                longitude: place.longitude,
                kind: side === 'departure' ? 'depart' : 'arrivee',
            })),
        [locations],
    );

    useEffect(() => {
        if (
            !data.auto_duration ||
            data.departure_port.trim() === '' ||
            data.destination_port.trim() === ''
        ) {
            return;
        }

        const controller = new AbortController();
        const query = new URLSearchParams({
            departure_port: data.departure_port,
            destination_port: data.destination_port,
            navigation_style: data.navigation_style,
            ...(data.departure_place
                ? { departure_place: data.departure_place }
                : {}),
            ...(data.destination_place
                ? { destination_place: data.destination_place }
                : {}),
        });

        const timer = window.setTimeout(() => {
            fetch(`/api/cruises/duration?${query.toString()}`, {
                headers: { Accept: 'application/json' },
                signal: controller.signal,
            })
                .then(async (response) =>
                    response.ok
                        ? (response.json() as Promise<{ days: number }>)
                        : Promise.reject(),
                )
                .then(({ days }) => {
                    setData('duration_days', days);
                    setCalculation({ key: calculationKey, failed: false });
                })
                .catch((error: unknown) => {
                    if (
                        error instanceof DOMException &&
                        error.name === 'AbortError'
                    ) {
                        return;
                    }

                    setCalculation({ key: calculationKey, failed: true });
                });
        }, 450);

        return () => {
            window.clearTimeout(timer);
            controller.abort();
        };
    }, [
        data.auto_duration,
        data.departure_port,
        data.destination_port,
        data.navigation_style,
        data.departure_place,
        data.destination_place,
        calculationKey,
        setData,
    ]);

    return (
        <>
            <Head
                title={editing ? 'Modifier la croisière' : 'Nouvelle croisière'}
            />
            <main className="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex items-start gap-3">
                    <Button
                        asChild
                        variant="ghost"
                        size="icon"
                        aria-label="Retour aux croisières"
                    >
                        <Link href="/croisieres">
                            <ArrowLeft className="size-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 className="text-2xl font-bold md:text-3xl">
                            {editing
                                ? 'Modifier la croisière'
                                : 'Nouvelle croisière'}
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Départ, arrivée, durée et style de navigation.
                        </p>
                    </div>
                </div>
                <form
                    className="grid gap-6"
                    onSubmit={(event) => {
                        event.preventDefault();

                        if (editing) {
                            put(`/croisieres/${cruise.slug}`);
                        } else {
                            post('/croisieres');
                        }
                    }}
                >
                    <section className="grid gap-4 border-y border-border/60 py-6 sm:grid-cols-2">
                        <Field
                            label="Nom de la croisière"
                            error={errors.title}
                            className="sm:col-span-2"
                        >
                            <Input
                                value={data.title}
                                onChange={(event) =>
                                    setData('title', event.target.value)
                                }
                                autoFocus
                            />
                        </Field>
                        <Field
                            label="Port de départ"
                            error={errors.departure_port}
                        >
                            <PlaceSearch
                                label="Port de départ"
                                value={data.departure_port}
                                onChange={(value) => {
                                    setData((previous) => ({
                                        ...previous,
                                        departure_port: value,
                                        departure_place: null,
                                    }));
                                    setLocations((previous) => {
                                        const next = { ...previous };
                                        delete next.departure;

                                        return next;
                                    });
                                }}
                                onSelect={(place) => {
                                    setData((previous) => ({
                                        ...previous,
                                        departure_port: place.name,
                                        departure_place: place.reference,
                                    }));
                                    setLocations((previous) => ({
                                        ...previous,
                                        departure: place,
                                    }));
                                }}
                            />
                        </Field>
                        <Field
                            label="Destination"
                            error={errors.destination_port}
                        >
                            <PlaceSearch
                                label="Destination"
                                value={data.destination_port}
                                onChange={(value) => {
                                    setData((previous) => ({
                                        ...previous,
                                        destination_port: value,
                                        destination_place: null,
                                    }));
                                    setLocations((previous) => {
                                        const next = { ...previous };
                                        delete next.destination;

                                        return next;
                                    });
                                }}
                                onSelect={(place) => {
                                    setData((previous) => ({
                                        ...previous,
                                        destination_port: place.name,
                                        destination_place: place.reference,
                                    }));
                                    setLocations((previous) => ({
                                        ...previous,
                                        destination: place,
                                    }));
                                }}
                            />
                        </Field>
                        <Field
                            label="Date de départ"
                            error={errors.departure_date}
                        >
                            <Input
                                type="date"
                                value={data.departure_date}
                                onChange={(event) =>
                                    setData(
                                        'departure_date',
                                        event.target.value,
                                    )
                                }
                            />
                        </Field>
                        <Field
                            label="Durée (jours)"
                            error={errors.duration_days}
                        >
                            <Input
                                type="number"
                                min="1"
                                max="180"
                                disabled={data.auto_duration}
                                value={data.duration_days}
                                onChange={(event) =>
                                    setData(
                                        'duration_days',
                                        Number(event.target.value),
                                    )
                                }
                            />
                            <label className="mt-2 flex items-center gap-2 text-sm text-muted-foreground">
                                <input
                                    type="checkbox"
                                    checked={data.auto_duration}
                                    onChange={(event) =>
                                        setData(
                                            'auto_duration',
                                            event.target.checked,
                                        )
                                    }
                                />
                                Calculer automatiquement la durée
                            </label>
                            {data.auto_duration &&
                                !calculationError &&
                                !calculationPending &&
                                data.departure_port &&
                                data.destination_port && (
                                    <p className="mt-2 text-sm text-muted-foreground">
                                        Estimation selon le style choisi :{' '}
                                        {data.duration_days} jours.
                                    </p>
                                )}
                            {data.auto_duration && calculationError && (
                                <p className="mt-2 text-sm text-destructive">
                                    Le calcul de durée est momentanément
                                    indisponible.
                                </p>
                            )}
                            {calculationPending && (
                                <p
                                    role="status"
                                    className="mt-2 text-sm text-muted-foreground"
                                >
                                    Estimation de la durée en cours…
                                </p>
                            )}
                        </Field>
                        <Field label="Équipage" error={errors.crew_count}>
                            <Input
                                type="number"
                                min="1"
                                max="20"
                                value={data.crew_count}
                                onChange={(event) =>
                                    setData(
                                        'crew_count',
                                        Number(event.target.value),
                                    )
                                }
                            />
                        </Field>
                        <Field
                            label="Style de navigation"
                            error={errors.navigation_style}
                        >
                            <select
                                value={data.navigation_style}
                                onChange={(event) =>
                                    setData(
                                        'navigation_style',
                                        event.target
                                            .value as CruiseForm['navigation_style'],
                                    )
                                }
                                className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            >
                                <option value="Confort">Confort</option>
                                <option value="Découverte">Découverte</option>
                                <option value="Famille">Famille</option>
                                <option value="Rapide">Rapide</option>
                            </select>
                        </Field>
                        <Field label="Bateau" error={errors.boat_id}>
                            <select
                                aria-label="Bateau"
                                value={data.boat_id ?? ''}
                                onChange={(event) =>
                                    setData(
                                        'boat_id',
                                        event.target.value
                                            ? Number(event.target.value)
                                            : null,
                                    )
                                }
                                className="h-9 w-full rounded-md border bg-background px-3 text-sm"
                            >
                                <option value="">
                                    Sans bateau — compatibilité à confirmer
                                </option>
                                {boats.map((boat) => (
                                    <option key={boat.id} value={boat.id}>
                                        {boat.name} · tirant d’eau {boat.draft}{' '}
                                        m
                                    </option>
                                ))}
                            </select>
                            {boats.length === 0 && (
                                <Link
                                    href="/bateau/configurer"
                                    className="text-sm text-sea-blue underline"
                                >
                                    Configurer mon bateau
                                </Link>
                            )}
                        </Field>
                        <Field
                            label="Zone de navigation"
                            error={errors.navigation_area}
                        >
                            <select
                                aria-label="Zone de navigation"
                                value={data.navigation_area}
                                onChange={(event) =>
                                    setData(
                                        'navigation_area',
                                        event.target.value,
                                    )
                                }
                                className="h-9 w-full rounded-md border bg-background px-3 text-sm"
                            >
                                <option value="coastal">Côtière</option>
                                <option value="offshore">Hauturière</option>
                                <option value="inland">Fluviale</option>
                            </select>
                        </Field>
                    </section>
                    <section
                        className="space-y-3"
                        aria-label="Choisir les coordonnées sur la carte"
                    >
                        <div>
                            <h2 className="font-semibold">
                                Choisir directement sur la carte
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                Zoomez ou déplacez la carte dans le monde.
                                Chaque clic conserve les coordonnées GPS
                                exactes, même sans port répertorié.
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Button
                                type="button"
                                variant={
                                    picking === 'departure'
                                        ? 'default'
                                        : 'outline'
                                }
                                aria-pressed={picking === 'departure'}
                                onClick={() => setPicking('departure')}
                            >
                                <MapPin className="size-4" />
                                Placer le départ
                            </Button>
                            <Button
                                type="button"
                                variant={
                                    picking === 'destination'
                                        ? 'default'
                                        : 'outline'
                                }
                                aria-pressed={picking === 'destination'}
                                onClick={() => setPicking('destination')}
                            >
                                <MapPin className="size-4" />
                                Placer l’arrivée
                            </Button>
                        </div>
                        <p
                            role="status"
                            className="text-sm text-muted-foreground"
                        >
                            {picking === 'departure'
                                ? 'Cliquez sur la carte pour choisir le départ.'
                                : 'Cliquez sur la carte pour choisir l’arrivée.'}
                        </p>

                        <PlannerMap
                            points={mapPoints}
                            onMapClick={(point) => {
                                const place = gpsPlace(
                                    point.latitude,
                                    point.longitude,
                                );
                                setLocations((previous) => ({
                                    ...previous,
                                    [picking]: place,
                                }));
                                setData((previous) => ({
                                    ...previous,
                                    [`${picking}_port`]: place.name,
                                    [`${picking}_place`]: place.reference,
                                }));

                                if (picking === 'departure') {
                                    setPicking('destination');
                                }
                            }}
                            routeAvailable={false}
                            preserveViewport
                            className="h-[420px] w-full rounded-md border"
                        />
                        <div className="grid gap-2 text-sm sm:grid-cols-2">
                            {Object.entries(locations).map(([side, place]) => (
                                <p key={side}>
                                    <span className="font-medium">
                                        {side === 'departure'
                                            ? 'Départ'
                                            : 'Arrivée'}{' '}
                                        :
                                    </span>{' '}
                                    {place.latitude.toFixed(5)},{' '}
                                    {place.longitude.toFixed(5)}
                                </p>
                            ))}
                        </div>
                    </section>
                    <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <Button asChild variant="outline">
                            <Link href="/croisieres">Annuler</Link>
                        </Button>
                        <Button
                            type="submit"
                            className="bg-sea-blue hover:bg-sea-blue/90"
                            disabled={processing}
                        >
                            <Ship className="size-4" />
                            {editing
                                ? 'Enregistrer les modifications'
                                : 'Créer la croisière'}
                            <ArrowRight className="size-4" />
                        </Button>
                    </div>
                </form>
            </main>
        </>
    );
}

function Field({
    label,
    error,
    className,
    children,
}: {
    label: string;
    error?: string;
    className?: string;
    children: React.ReactNode;
}) {
    const fieldId = useId();

    return (
        <div className={className}>
            <Label htmlFor={fieldId} className="mb-2 block">
                {label}
            </Label>
            {Children.map(children, (child, index) =>
                index === 0 && isValidElement<{ id?: string }>(child)
                    ? cloneElement(child, { id: fieldId })
                    : child,
            )}
            {error && <p className="mt-1 text-xs text-destructive">{error}</p>}
        </div>
    );
}

CreateCruise.layout = () => ({
    breadcrumbs: [
        { title: 'Mes croisières', href: appRoutes.cruises },
        { title: 'Nouvelle croisière', href: '/croisieres/nouvelle' },
    ],
});
