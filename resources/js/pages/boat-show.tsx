import { Head, Link, router } from '@inertiajs/react';
import AlertTriangle from 'lucide-react/icons/alert-triangle';
import Check from 'lucide-react/icons/check';
import Pencil from 'lucide-react/icons/pencil';
import { useState } from 'react';
import {
    Chip,
    PageHeader,
    SegmentBar,
    Status,
} from '@/components/mariwaze/passerelle';
import { Button } from '@/components/ui/button';
import { useDesign } from '@/hooks/use-design';
import { useMariwazeRoutes } from '@/hooks/use-mariwaze-routes';
import { appRoutes } from '@/lib/mariwaze-routes';
import { cn } from '@/lib/utils';

type Boat = {
    name: string;
    type: string;
    type_label: string;
    flag: string;
    mmsi: string;
    length: number;
    width: number;
    draft: number;
    mast_height: number;
    avg_speed: number;
    fuel_range: number;
    readiness: number;
    readiness_label: string;
    equipment: { key: string; label: string; present: boolean }[];
};

type Props = {
    boat: Boat;
};

const nf = new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 1 });

export default function DemoBoatShow({ boat }: Props) {
    const routes = useMariwazeRoutes();
    const [pending, setPending] = useState<string | null>(null);

    const equipmentPresent = boat.equipment.filter((e) => e.present).length;
    const hasMmsi = Boolean(boat.mmsi && boat.mmsi.trim() !== '');

    const toggleEquipment = (key: string, present: boolean) => {
        setPending(key);
        router.patch(
            '/bateau/equipements',
            { equipment: { [key]: !present } },
            { preserveScroll: true, onFinish: () => setPending(null) },
        );
    };

    const { design } = useDesign();

    if (design === 'large') {
        return (
            <BoatLarge
                boat={boat}
                pending={pending}
                hasMmsi={hasMmsi}
                onToggle={toggleEquipment}
            />
        );
    }

    return (
        <>
            <Head title={`Bateau — ${boat.name}`} />
            <div className="flex flex-1 flex-col">
                <PageHeader
                    label={`Bateau · ${boat.type_label} · ${boat.flag}`}
                    title={boat.name}
                    actions={
                        <Button asChild variant="outline">
                            <Link href={routes.boatSetup}>
                                <Pencil className="size-4" />
                                Modifier la fiche
                            </Link>
                        </Button>
                    }
                />

                <div className="grid flex-1 gap-px bg-border lg:grid-cols-[340px_minmax(0,1fr)_380px]">
                    {/* Caractéristiques */}
                    <section className="bg-background px-5 py-4">
                        <h2 className="pw-title">Caractéristiques</h2>
                        <div className="mt-3 grid grid-cols-2 gap-px border border-border bg-border">
                            <Tile
                                label="Longueur"
                                value={boat.length}
                                unit="m"
                            />
                            <Tile label="Largeur" value={boat.width} unit="m" />
                            <Tile
                                label="Tirant d’eau"
                                value={boat.draft}
                                unit="m"
                                accent
                            />
                            <Tile
                                label="Tirant d’air"
                                value={boat.mast_height}
                                unit="m"
                            />
                            <Tile
                                label="Vitesse moy."
                                value={boat.avg_speed}
                                unit="nds"
                            />
                            <Tile
                                label="Autonomie"
                                value={boat.fuel_range}
                                unit="M"
                            />
                        </div>
                        <BoatProfile boat={boat} />
                    </section>

                    {/* Audit et règles */}
                    <section className="flex flex-col bg-background px-5 py-4">
                        <div className="flex items-baseline justify-between">
                            <h2 className="pw-title">Audit sécurité</h2>
                            <span className="pw-label">
                                {equipmentPresent}/{boat.equipment.length}{' '}
                                équipements
                            </span>
                        </div>
                        <div className="mt-3 flex items-center gap-3">
                            <span className="pw-figure text-[32px] leading-none">
                                {boat.readiness}%
                            </span>
                            <SegmentBar
                                value={boat.readiness}
                                className="h-3.5"
                            />
                        </div>
                        <p className="mt-2 text-lg font-semibold">
                            {boat.readiness_label}
                        </p>

                        <div className="mt-6 border-t border-border pt-4">
                            <h3 className="pw-title">
                                Ce que ça change pour vos itinéraires
                            </h3>
                            <ul className="mt-2 divide-y divide-dashed divide-border">
                                <li className="py-3">
                                    <div className="flex items-baseline justify-between gap-3">
                                        <span className="font-semibold">
                                            Fonds trop faibles écartés
                                        </span>
                                        <Chip accent>
                                            TE {nf.format(boat.draft)} m
                                        </Chip>
                                    </div>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        Les mouillages et ports de moins de 2,5
                                        m de profondeur sont filtrés ou
                                        signalés.
                                    </p>
                                </li>
                                <li className="py-3">
                                    <div className="flex items-baseline justify-between gap-3">
                                        <span className="font-semibold">
                                            Durées d’étape
                                        </span>
                                        <Chip>
                                            {nf.format(boat.avg_speed)} nds
                                        </Chip>
                                    </div>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        Les temps de navigation sont estimés à
                                        partir de votre vitesse moyenne.
                                    </p>
                                </li>
                            </ul>
                        </div>
                    </section>

                    {/* Équipements */}
                    <section className="flex flex-col bg-background px-5 py-4">
                        <h2 className="pw-title">Équipements à bord</h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Cochez ce qui est à bord : l’audit se met à jour
                            aussitôt.
                        </p>
                        <ul className="mt-2 divide-y divide-dashed divide-border">
                            {boat.equipment.map((item) => (
                                <li key={item.key}>
                                    <button
                                        type="button"
                                        onClick={() =>
                                            toggleEquipment(
                                                item.key,
                                                item.present,
                                            )
                                        }
                                        disabled={pending !== null}
                                        aria-pressed={item.present}
                                        className={cn(
                                            'flex w-full items-center gap-3 py-2 text-left transition hover:text-primary',
                                            pending === item.key &&
                                                'opacity-50',
                                        )}
                                    >
                                        <span
                                            className={cn(
                                                'flex size-4 shrink-0 items-center justify-center rounded-[2px] border',
                                                item.present
                                                    ? 'border-good bg-good text-background'
                                                    : 'border-line-strong',
                                            )}
                                        >
                                            {item.present && (
                                                <Check className="size-3" />
                                            )}
                                        </span>
                                        <span className="flex-1">
                                            {item.label}
                                        </span>
                                        <Status
                                            tone={
                                                item.present ? 'good' : 'warn'
                                            }
                                        >
                                            {item.present ? 'OK' : 'Manquant'}
                                        </Status>
                                    </button>
                                </li>
                            ))}
                        </ul>

                        <div
                            className={cn(
                                'mt-5 rounded-sm border px-4 py-3',
                                hasMmsi ? 'border-border' : 'border-bad',
                            )}
                        >
                            <div className="flex items-center justify-between">
                                <span className="pw-title">MMSI</span>
                                {hasMmsi ? (
                                    <span className="pw-figure">
                                        {boat.mmsi}
                                    </span>
                                ) : (
                                    <Status tone="bad">Manquant</Status>
                                )}
                            </div>
                            {!hasMmsi && (
                                <>
                                    <p className="mt-2 flex gap-2 text-sm text-muted-foreground">
                                        <AlertTriangle className="mt-0.5 size-4 shrink-0 text-bad" />
                                        Sans MMSI, l’appel de détresse ASN de la
                                        VHF et l’AIS n’identifient pas{' '}
                                        {boat.name}.
                                    </p>
                                    <Button asChild size="sm" className="mt-3">
                                        <Link href={routes.boatSetup}>
                                            Renseigner le MMSI
                                        </Link>
                                    </Button>
                                </>
                            )}
                        </div>
                    </section>
                </div>
            </div>
        </>
    );
}

function Tile({
    label,
    value,
    unit,
    accent = false,
}: {
    label: string;
    value: number;
    unit: string;
    accent?: boolean;
}) {
    return (
        <div className="bg-raised px-3.5 py-3">
            <p className={cn('pw-label', accent && '!text-primary')}>{label}</p>
            <p className="pw-figure text-2xl leading-7">
                {nf.format(value)}
                <small className="ml-1 text-xs text-muted-foreground">
                    {unit}
                </small>
            </p>
        </div>
    );
}

/** Profil coté, schématique : seules les cotes viennent des données. */
function BoatProfile({ boat }: { boat: Boat }) {
    return (
        <figure className="mt-4 overflow-hidden rounded-sm border border-border bg-[var(--chart-water-1)]">
            <svg
                viewBox="0 0 290 170"
                className="block h-auto w-full"
                role="img"
                aria-label={`Profil : ${nf.format(boat.length)} m de long, ${nf.format(boat.mast_height)} m de tirant d’air, ${nf.format(boat.draft)} m de tirant d’eau`}
            >
                <rect
                    x="0"
                    y="122"
                    width="290"
                    height="48"
                    fill="var(--chart-water-3)"
                />
                <path
                    d="M140 18 V112 M140 18 L214 112 M138 100 L84 100"
                    fill="none"
                    stroke="var(--muted-foreground)"
                    strokeWidth="1"
                />
                <path
                    d="M70 112 L222 112 L216 120 L212 132 L92 132 Q80 128 76 120 Z"
                    fill="var(--chart-land-1)"
                    stroke="var(--line-strong)"
                />
                <g fill="none" stroke="var(--primary)" strokeWidth="0.8">
                    <path d="M250 18 V122 M246 18 H254 M246 122 H254" />
                    <path d="M40 122 V132 M36 122 H44 M36 132 H44" />
                    <path d="M70 150 H222 M70 146 V154 M222 146 V154" />
                </g>
                <g
                    fill="var(--primary)"
                    fontFamily="var(--font-mono)"
                    fontSize="10"
                >
                    <text x="232" y="74" transform="rotate(-90 232 74)">
                        {nf.format(boat.mast_height)} m
                    </text>
                    <text x="8" y="131">
                        {nf.format(boat.draft)}
                    </text>
                    <text x="128" y="165">
                        {nf.format(boat.length)} m
                    </text>
                </g>
            </svg>
        </figure>
    );
}

/* ------------------------------------------------------------------
 * Design « Au large » : grande carte illustrée du bateau,
 * équipement et identité radio dans la colonne de droite.
 * ------------------------------------------------------------------ */

function BoatLarge({
    boat,
    pending,
    hasMmsi,
    onToggle,
}: {
    boat: Boat;
    pending: string | null;
    hasMmsi: boolean;
    onToggle: (key: string, present: boolean) => void;
}) {
    const routes = useMariwazeRoutes();
    const present = boat.equipment.filter((item) => item.present).length;
    const specs: [string, string][] = [
        [`${nf.format(boat.length)} m`, 'longueur'],
        [`${nf.format(boat.width)} m`, 'largeur'],
        [`${nf.format(boat.draft)} m`, 'tirant d’eau'],
        [`${nf.format(boat.mast_height)} m`, 'tirant d’air'],
        [`${nf.format(boat.avg_speed)} nds`, 'vitesse moyenne'],
        [`${nf.format(boat.fuel_range)} M`, 'autonomie'],
    ];

    return (
        <>
            <Head title={`Bateau — ${boat.name}`} />
            <div className="grid gap-5 pt-4 xl:grid-cols-[minmax(0,1fr)_420px]">
                <section className="overflow-hidden rounded-[24px] bg-card shadow-[var(--soft-shadow)]">
                    <div className="relative h-[300px] bg-[var(--chart-water-1)]">
                        <BoatSilhouette boat={boat} />
                        <div className="absolute top-6 left-7">
                            <h1 className="text-[34px] leading-10 font-semibold tracking-[-0.02em]">
                                {boat.name}
                            </h1>
                            <p className="text-[15px] text-muted-foreground">
                                {boat.type_label} · pavillon {boat.flag}
                            </p>
                        </div>
                        <Button
                            asChild
                            variant="secondary"
                            className="absolute top-5 right-5 bg-card"
                        >
                            <Link href={routes.boatSetup}>
                                <Pencil className="size-4" />
                                Modifier
                            </Link>
                        </Button>
                    </div>

                    <div className="grid grid-cols-2 gap-2.5 p-5 sm:grid-cols-3 lg:grid-cols-6">
                        {specs.map(([value, label]) => (
                            <div
                                key={label}
                                className="rounded-2xl bg-background px-3.5 py-3"
                            >
                                <p className="text-[22px] leading-7 font-light tracking-[-0.02em] tabular-nums">
                                    {value}
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    {label}
                                </p>
                            </div>
                        ))}
                    </div>

                    <div className="grid gap-2.5 px-5 pb-5 md:grid-cols-2">
                        <div className="rounded-2xl border border-border px-4 py-3.5">
                            <p className="font-semibold">
                                Fonds trop faibles écartés
                            </p>
                            <p className="mt-0.5 text-sm text-muted-foreground">
                                Avec {nf.format(boat.draft)} m de tirant d’eau,
                                les ports et mouillages de moins de 2,5 m sont
                                filtrés ou signalés.
                            </p>
                        </div>
                        <div className="rounded-2xl border border-border px-4 py-3.5">
                            <p className="font-semibold">Durées d’étape</p>
                            <p className="mt-0.5 text-sm text-muted-foreground">
                                Les temps de navigation sont estimés à partir de
                                vos {nf.format(boat.avg_speed)} nœuds de
                                moyenne.
                            </p>
                        </div>
                    </div>
                </section>

                <div className="flex flex-col gap-5">
                    <section className="rounded-[24px] bg-card p-6 shadow-[var(--soft-shadow)]">
                        <div className="flex items-center gap-4">
                            <ReadinessDial value={boat.readiness} />
                            <div>
                                <h2 className="font-semibold">
                                    Équipement de sécurité
                                </h2>
                                <p className="text-sm text-muted-foreground">
                                    {present} sur {boat.equipment.length} à bord
                                    · {boat.readiness_label}
                                </p>
                            </div>
                        </div>
                        <ul className="mt-4">
                            {boat.equipment.map((item) => (
                                <li key={item.key}>
                                    <button
                                        type="button"
                                        onClick={() =>
                                            onToggle(item.key, item.present)
                                        }
                                        disabled={pending !== null}
                                        aria-pressed={item.present}
                                        className={cn(
                                            'flex w-full items-center gap-3 border-b border-border py-2.5 text-left transition-colors last:border-b-0 hover:text-primary',
                                            pending === item.key &&
                                                'opacity-50',
                                        )}
                                    >
                                        <span
                                            className={cn(
                                                'flex size-[22px] shrink-0 items-center justify-center rounded-full',
                                                item.present
                                                    ? 'bg-good-soft text-good'
                                                    : 'bg-warn-soft text-warn',
                                            )}
                                        >
                                            {item.present ? (
                                                <Check className="size-3" />
                                            ) : (
                                                <span className="text-xs font-bold">
                                                    !
                                                </span>
                                            )}
                                        </span>
                                        <span className="flex-1">
                                            {item.label}
                                        </span>
                                        <span
                                            className={cn(
                                                'text-xs font-semibold',
                                                item.present
                                                    ? 'text-muted-foreground'
                                                    : 'text-warn',
                                            )}
                                        >
                                            {item.present
                                                ? 'À bord'
                                                : 'Manquant'}
                                        </span>
                                    </button>
                                </li>
                            ))}
                        </ul>
                    </section>

                    <section
                        className={cn(
                            'rounded-[24px] p-6',
                            hasMmsi
                                ? 'bg-card shadow-[var(--soft-shadow)]'
                                : 'bg-bad-soft',
                        )}
                    >
                        <h2 className="font-semibold">
                            {hasMmsi ? 'Identité radio' : 'Il manque le MMSI'}
                        </h2>
                        {hasMmsi ? (
                            <p className="mt-1 text-[22px] font-light tabular-nums">
                                {boat.mmsi}
                            </p>
                        ) : (
                            <>
                                <p className="mt-1 text-sm">
                                    Sans lui, l’appel de détresse de la VHF et
                                    l’AIS n’identifient pas {boat.name}.
                                </p>
                                <Button asChild className="mt-4">
                                    <Link href={routes.boatSetup}>
                                        Renseigner le MMSI
                                    </Link>
                                </Button>
                            </>
                        )}
                    </section>
                </div>
            </div>
        </>
    );
}

function ReadinessDial({ value }: { value: number }) {
    const circumference = 2 * Math.PI * 27;
    const clamped = Math.max(0, Math.min(100, value));

    return (
        <svg
            width="64"
            height="64"
            viewBox="0 0 64 64"
            className="shrink-0"
            role="img"
            aria-label={`${clamped} % de l’équipement à bord`}
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

/** Silhouette schématique ; seules les cotes viennent de la fiche. */
function BoatSilhouette({ boat }: { boat: Boat }) {
    return (
        <svg
            viewBox="0 0 780 300"
            preserveAspectRatio="xMaxYMid meet"
            className="absolute inset-0 size-full"
            role="img"
            aria-label={`${boat.name} : ${nf.format(boat.length)} m de long, ${nf.format(boat.mast_height)} m de tirant d’air, ${nf.format(boat.draft)} m de tirant d’eau`}
        >
            <g
                fill="none"
                stroke="var(--chart-water-3)"
                strokeWidth="2"
                strokeLinecap="round"
            >
                <path d="M0 250 q20 -8 40 0 t40 0 t40 0 t40 0 t40 0 t40 0 t40 0 t40 0 t40 0 t40 0 t40 0 t40 0 t40 0 t40 0 t40 0 t40 0 t40 0 t40 0 t40 0 t40 0" />
                <path
                    d="M20 272 q20 -8 40 0 t40 0 t40 0 t40 0 t40 0 t40 0 t40 0 t40 0 t40 0 t40 0 t40 0 t40 0 t40 0 t40 0 t40 0 t40 0 t40 0 t40 0 t40 0"
                    opacity="0.6"
                />
            </g>
            <g stroke="var(--deep)" strokeWidth="1.5" strokeLinejoin="round">
                <path d="M486 46 L486 210 L392 210 Z" fill="var(--card)" />
                <path
                    d="M494 54 L494 214 L606 214 Z"
                    fill="var(--chart-route-glow)"
                />
                <path
                    d="M490 36 V228 M490 36 L620 230 M486 210 L384 210"
                    fill="none"
                    strokeLinecap="round"
                />
                <path
                    d="M372 228 L640 228 L632 240 L626 254 L398 254 Q382 250 378 240 Z"
                    fill="var(--card)"
                />
                <path
                    d="M440 228 L440 214 L560 214 L572 228"
                    fill="var(--card)"
                />
            </g>
            <g fill="none" stroke="var(--primary)" strokeWidth="1.2">
                <path d="M680 36 V246 M674 36 H686 M674 246 H686" />
                <path d="M372 286 H640 M372 280 V292 M640 280 V292" />
                <path d="M350 246 V258 M344 246 H356 M344 258 H356" />
            </g>
            <g fill="var(--primary)" fontSize="13" fontWeight="600">
                <text x="694" y="146">
                    {nf.format(boat.mast_height)} m
                </text>
                <text x="484" y="282">
                    {nf.format(boat.length)} m
                </text>
                <text x="296" y="257">
                    {nf.format(boat.draft)} m
                </text>
            </g>
        </svg>
    );
}

DemoBoatShow.layout = () => ({
    breadcrumbs: [{ title: 'Mon bateau', href: appRoutes.boat }],
});
