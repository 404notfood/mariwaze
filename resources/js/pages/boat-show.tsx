import { Head, Link, router } from '@inertiajs/react';
import {
    Anchor,
    CheckCircle2,
    Circle,
    Gauge,
    Pencil,
    Ruler,
    Ship,
} from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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

export default function DemoBoatShow({ boat }: Props) {
    const routes = useMariwazeRoutes();
    const [pending, setPending] = useState<string | null>(null);

    const equipmentPresent = boat.equipment.filter((e) => e.present).length;

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
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">

                <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div className="flex items-start gap-4">
                        <div className="flex size-16 items-center justify-center rounded-2xl bg-ocean-navy text-3xl text-white dark:bg-sidebar-accent">
                            ⛵
                        </div>
                        <div>
                            <p className="text-sm text-muted-foreground">Mon bateau</p>
                            <h1 className="text-2xl font-bold md:text-3xl">{boat.name}</h1>
                            <p className="text-muted-foreground">
                                {boat.type_label} · Pavillon {boat.flag}
                            </p>
                        </div>
                    </div>
                    <Button asChild className="bg-sea-blue hover:bg-sea-blue/90">
                        <Link href={routes.boatSetup}>
                            <Pencil className="size-4" />
                            Modifier le profil
                        </Link>
                    </Button>
                </div>

                <Card className="overflow-hidden border-border/60">
                    <div className="h-1 bg-gradient-to-r from-ocean-navy to-lagoon" />
                    <CardContent className="flex flex-col gap-4 pt-6 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p className="text-sm text-muted-foreground">Audit sécurité</p>
                            <p className="text-xl font-bold">{boat.readiness_label}</p>
                        </div>
                        <div className="flex items-center gap-4">
                            <div className="text-right">
                                <p className="text-3xl font-bold text-sea-blue">{boat.readiness}%</p>
                                <p className="text-xs text-muted-foreground">prêt au départ</p>
                            </div>
                            <div className="h-16 w-16">
                                <svg viewBox="0 0 36 36" className="size-full -rotate-90">
                                    <circle cx="18" cy="18" r="15.5" fill="none" stroke="currentColor" strokeWidth="3" className="text-muted" />
                                    <circle
                                        cx="18"
                                        cy="18"
                                        r="15.5"
                                        fill="none"
                                        stroke="currentColor"
                                        strokeWidth="3"
                                        strokeDasharray={`${boat.readiness} 100`}
                                        className="text-sea-blue"
                                    />
                                </svg>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <DimCard icon={Ruler} label="Longueur" value={`${boat.length} m`} />
                    <DimCard icon={Ship} label="Largeur" value={`${boat.width} m`} />
                    <DimCard icon={Anchor} label="Tirant d'eau" value={`${boat.draft} m`} highlight />
                    <DimCard icon={Gauge} label="Hauteur mât" value={`${boat.mast_height} m`} />
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    <Card className="border-border/60">
                        <CardHeader>
                            <CardTitle className="text-base">Navigation</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2 text-sm">
                            <Row label="Vitesse moyenne" value={`${boat.avg_speed} nds`} />
                            <Row label="Autonomie carburant" value={`${boat.fuel_range} M`} />
                            <Row label="MMSI" value={boat.mmsi} />
                            <Row label="Pavillon" value={boat.flag} />
                        </CardContent>
                    </Card>

                    <Card className="border-border/60">
                        <CardHeader>
                            <CardTitle className="text-base">
                                Équipements sécurité ({equipmentPresent}/{boat.equipment.length})
                            </CardTitle>
                            <p className="text-xs text-muted-foreground">
                                Cochez ce qui est à bord : l&apos;audit sécurité se met à jour aussitôt.
                            </p>
                        </CardHeader>
                        <CardContent className="grid gap-2 sm:grid-cols-2">
                            {boat.equipment.map((item) => (
                                <button
                                    key={item.key}
                                    type="button"
                                    onClick={() => toggleEquipment(item.key, item.present)}
                                    disabled={pending !== null}
                                    aria-pressed={item.present}
                                    className={cn(
                                        'flex items-center gap-2 rounded-lg border border-transparent px-2 py-1.5 text-left text-sm transition',
                                        'hover:border-border focus-visible:outline-2 focus-visible:outline-sea-blue',
                                        !item.present && 'bg-safety-orange/10',
                                        pending === item.key && 'opacity-50',
                                    )}
                                >
                                    {item.present ? (
                                        <CheckCircle2 className="size-4 shrink-0 text-green-600" />
                                    ) : (
                                        <Circle className="size-4 shrink-0 text-safety-orange" />
                                    )}
                                    <span className={!item.present ? 'font-medium' : 'text-muted-foreground'}>
                                        {item.label}
                                    </span>
                                </button>
                            ))}
                        </CardContent>
                    </Card>
                </div>

                <Card className="border-lagoon/30 bg-lagoon/5 dark:bg-lagoon/5">
                    <CardContent className="pt-6 text-sm">
                        <p className="font-medium">Impact sur les recommandations</p>
                        <p className="mt-1 text-muted-foreground">
                            Avec un tirant d&apos;eau de <strong>{boat.draft} m</strong>, MariWaze adapte automatiquement
                            les mouillages et ports suggérés. Les spots de moins de 2,5 m de profondeur sont filtrés ou signalés.
                        </p>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

function DimCard({
    icon: Icon,
    label,
    value,
    highlight,
}: {
    icon: typeof Ruler;
    label: string;
    value: string;
    highlight?: boolean;
}) {
    return (
        <Card className={cn('border-border/60', highlight && 'ring-1 ring-sea-blue/30')}>
            <CardContent className="flex items-center gap-3 pt-6">
                <div className="flex size-10 items-center justify-center rounded-xl bg-muted text-sea-blue">
                    <Icon className="size-5" />
                </div>
                <div>
                    <p className="text-xs text-muted-foreground">{label}</p>
                    <p className="text-lg font-bold">{value}</p>
                </div>
            </CardContent>
        </Card>
    );
}

function Row({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex justify-between">
            <span className="text-muted-foreground">{label}</span>
            <span className="font-medium">{value}</span>
        </div>
    );
}

DemoBoatShow.layout = () => ({
    breadcrumbs: [{ title: 'Mon bateau', href: appRoutes.boat }],
});
