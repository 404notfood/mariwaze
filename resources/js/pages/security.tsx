import { Head, Link } from '@inertiajs/react';
import AlertTriangle from 'lucide-react/icons/alert-triangle';
import CheckCircle2 from 'lucide-react/icons/check-circle-2';
import Circle from 'lucide-react/icons/circle';
import Phone from 'lucide-react/icons/phone';
import Radio from 'lucide-react/icons/radio';
import Shield from 'lucide-react/icons/shield';
import ShieldAlert from 'lucide-react/icons/shield-alert';
import HazardAlerts from '@/components/mariwaze/hazard-alerts';
import type { HazardAlert } from '@/components/mariwaze/planner-map';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { boat as boatRoute } from '@/routes';

type ChecklistItem = {
    key: string;
    label: string;
    done: boolean;
    required: boolean;
};

type Country = {
    code: string;
    name: string;
    flag: string | null;
    docs: string[];
    rules: string | null;
    anchoring_rules: string | null;
};

type EmergencyContact = {
    country_code: string;
    country_name: string;
    authority: string;
    label: string;
    phone: string | null;
    vhf_channel: string | null;
    note: string | null;
};

type SecurityData = {
    boat: {
        name: string;
        type: string;
        length: number;
        draft: number;
        mmsi: string | null;
        flag: string | null;
    } | null;
    navigation_area: string;
    readiness: number;
    readiness_label: string;
    missing: string[];
    checklist: ChecklistItem[];
    countries: Country[];
    emergency: EmergencyContact[];
    hazards: HazardAlert[];
    route_available: boolean;
};

type Props = {
    securityData: SecurityData;
    cruise: {
        slug: string;
        title: string;
        departure_port: string;
        destination_port: string;
    } | null;
};

function readinessTone(readiness: number) {
    if (readiness >= 100) {
        return 'text-green-600 dark:text-green-400';
    }

    if (readiness >= 75) {
        return 'text-lagoon';
    }

    if (readiness >= 40) {
        return 'text-safety-orange';
    }

    return 'text-destructive';
}

export default function Security({ securityData, cruise }: Props) {
    const required = securityData.checklist.filter((item) => item.required);
    const optional = securityData.checklist.filter((item) => !item.required);
    const done = required.filter((item) => item.done).length;

    return (
        <>
            <Head title="Sécurité" />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="flex items-center gap-2 text-2xl font-bold md:text-3xl">
                        <Shield className="size-7 text-sea-blue" />
                        Sécurité maritime
                    </h1>
                    <p className="text-muted-foreground">
                        {cruise
                            ? `Préparation de « ${cruise.title} » — ${cruise.departure_port} → ${cruise.destination_port}`
                            : 'Préparez votre départ sereinement — équipement et réglementation'}
                    </p>
                </div>

                {securityData.boat === null ? (
                    <Card className="border-safety-orange/40 bg-safety-orange/5">
                        <CardContent className="flex flex-col gap-3 pt-6 md:flex-row md:items-center md:justify-between">
                            <div className="flex items-start gap-3">
                                <AlertTriangle className="mt-0.5 size-5 shrink-0 text-safety-orange" />
                                <div>
                                    <p className="font-medium">
                                        Aucun bateau enregistré
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        L&apos;audit de sécurité s&apos;appuie
                                        sur les équipements déclarés à bord.
                                    </p>
                                </div>
                            </div>
                            <Link
                                href={boatRoute.url()}
                                className="rounded-lg bg-sea-blue px-4 py-2 text-sm font-medium text-white"
                            >
                                Configurer mon bateau
                            </Link>
                        </CardContent>
                    </Card>
                ) : (
                    <Card className="overflow-hidden border-border/60">
                        <div className="h-1 bg-gradient-to-r from-green-500 via-sea-blue to-lagoon" />
                        <CardContent className="flex flex-col gap-4 pt-6 md:flex-row md:items-center md:justify-between">
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    {securityData.boat.name} — équipement
                                    réglementaire
                                </p>
                                <p className="text-2xl font-bold">
                                    {securityData.readiness}%
                                </p>
                                <p
                                    className={readinessTone(
                                        securityData.readiness,
                                    )}
                                >
                                    {securityData.readiness_label}
                                </p>
                            </div>
                            <Badge variant="secondary" className="text-base">
                                {done}/{required.length} équipements requis
                            </Badge>
                        </CardContent>
                    </Card>
                )}

                {cruise && (
                    <Card className="border-border/60">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <ShieldAlert className="size-4 text-safety-orange" />
                                Dangers et vigilance sur le trajet
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {!securityData.route_available ? (
                                <p className="text-sm text-muted-foreground">
                                    Calculez l&apos;itinéraire de la croisière
                                    pour analyser les zones traversées (zones
                                    militaires, interactions orques, piraterie,
                                    trafic dense…).
                                </p>
                            ) : securityData.hazards.length === 0 ? (
                                <p className="flex items-center gap-2 text-sm text-muted-foreground">
                                    <CheckCircle2 className="size-4 text-green-600" />
                                    Aucune zone de vigilance connue sur le
                                    trajet retenu.
                                </p>
                            ) : (
                                <HazardAlerts hazards={securityData.hazards} />
                            )}
                        </CardContent>
                    </Card>
                )}

                {securityData.missing.length > 0 && (
                    <Card className="border-destructive/30 bg-destructive/5">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <AlertTriangle className="size-4 text-destructive" />
                                Équipements manquants
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ul className="list-inside list-disc space-y-1 text-sm">
                                {securityData.missing.map((label) => (
                                    <li key={label}>{label}</li>
                                ))}
                            </ul>
                        </CardContent>
                    </Card>
                )}

                <div className="grid gap-4 lg:grid-cols-2">
                    <Card className="border-border/60">
                        <CardHeader>
                            <CardTitle className="text-base">
                                Équipement réglementaire
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            {required.map((item) => (
                                <div
                                    key={item.key}
                                    className={cn(
                                        'flex items-center gap-3 rounded-lg px-3 py-2 text-sm',
                                        !item.done && 'bg-safety-orange/10',
                                    )}
                                >
                                    {item.done ? (
                                        <CheckCircle2 className="size-4 shrink-0 text-green-600" />
                                    ) : (
                                        <AlertTriangle className="size-4 shrink-0 text-safety-orange" />
                                    )}
                                    <p
                                        className={
                                            item.done
                                                ? 'text-muted-foreground'
                                                : 'font-medium'
                                        }
                                    >
                                        {item.label}
                                    </p>
                                </div>
                            ))}

                            {optional.length > 0 && (
                                <>
                                    <p className="pt-3 text-xs font-medium text-muted-foreground uppercase">
                                        Équipements complémentaires
                                    </p>
                                    {optional.map((item) => (
                                        <div
                                            key={item.key}
                                            className="flex items-center gap-3 rounded-lg px-3 py-2 text-sm"
                                        >
                                            {item.done ? (
                                                <CheckCircle2 className="size-4 shrink-0 text-green-600" />
                                            ) : (
                                                <Circle className="size-4 shrink-0 text-muted-foreground/40" />
                                            )}
                                            <p className="text-muted-foreground">
                                                {item.label}
                                            </p>
                                        </div>
                                    ))}
                                </>
                            )}
                        </CardContent>
                    </Card>

                    <div className="space-y-4">
                        <Card className="border-border/60">
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Réglementation par pays
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {securityData.countries.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        Les pays traversés sont déduits de
                                        l&apos;itinéraire : calculez la route de
                                        votre croisière pour adapter la
                                        réglementation et les numéros
                                        d&apos;urgence.
                                    </p>
                                ) : (
                                    securityData.countries.map((country) => (
                                        <div
                                            key={country.code}
                                            className="rounded-xl border border-border/60 p-4"
                                        >
                                            <p className="font-semibold">
                                                {country.flag} {country.name}
                                            </p>
                                            {country.docs.length > 0 && (
                                                <p className="mt-1 text-xs text-muted-foreground">
                                                    Documents :{' '}
                                                    {country.docs.join(', ')}
                                                </p>
                                            )}
                                            {country.rules && (
                                                <p className="mt-1 text-sm">
                                                    {country.rules}
                                                </p>
                                            )}
                                            {country.anchoring_rules && (
                                                <p className="mt-1 text-sm text-muted-foreground">
                                                    {country.anchoring_rules}
                                                </p>
                                            )}
                                        </div>
                                    ))
                                )}
                            </CardContent>
                        </Card>

                        <Card className="border-destructive/30 bg-destructive/5">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <Phone className="size-4" />
                                    Numéros d&apos;urgence
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {securityData.emergency.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        Aucun contact enregistré pour cette
                                        zone.
                                    </p>
                                ) : (
                                    securityData.emergency.map((contact) => (
                                        <div
                                            key={`${contact.country_code}-${contact.label}`}
                                            className="flex items-start justify-between gap-3 text-sm"
                                        >
                                            <div>
                                                <p className="font-medium">
                                                    {contact.label}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {contact.country_name} —{' '}
                                                    {contact.authority}
                                                </p>
                                            </div>
                                            <div className="text-right">
                                                {contact.phone && (
                                                    <a
                                                        href={`tel:${contact.phone.replace(/\s/g, '')}`}
                                                        className="font-mono font-bold text-destructive"
                                                    >
                                                        {contact.phone}
                                                    </a>
                                                )}
                                                {contact.vhf_channel && (
                                                    <p className="flex items-center justify-end gap-1 font-mono font-bold">
                                                        <Radio className="size-3" />
                                                        VHF{' '}
                                                        {contact.vhf_channel}
                                                    </p>
                                                )}
                                            </div>
                                        </div>
                                    ))
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </>
    );
}
