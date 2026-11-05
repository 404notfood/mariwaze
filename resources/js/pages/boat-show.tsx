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

DemoBoatShow.layout = () => ({
    breadcrumbs: [{ title: 'Mon bateau', href: appRoutes.boat }],
});
