import { Head } from '@inertiajs/react';
import { AlertTriangle, CheckCircle2, Phone, Shield } from 'lucide-react';
import PreviewBanner from '@/components/mariwaze/preview-banner';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { mariwazeRoutesForPreview } from '@/lib/mariwaze-routes';

type SecurityData = {
    readiness: number;
    readiness_label: string;
    checklist: { label: string; done: boolean; category: string }[];
    countries: { name: string; flag: string; docs: string[]; rules: string }[];
    emergency: { label: string; value: string }[];
};

type Props = {
    securityData: SecurityData;
    preview?: boolean;
};

export default function DemoSecurity({ securityData, preview = false }: Props) {
    const done = securityData.checklist.filter((i) => i.done).length;

    return (
        <>
            <Head title="Sécurité" />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PreviewBanner preview={preview} />

                <div>
                    <h1 className="flex items-center gap-2 text-2xl font-bold md:text-3xl">
                        <Shield className="size-7 text-sea-blue" />
                        Sécurité maritime
                    </h1>
                    <p className="text-muted-foreground">
                        Préparez votre départ sereinement — checklist et réglementation
                    </p>
                </div>

                <Card className="overflow-hidden border-border/60">
                    <div className="h-1 bg-gradient-to-r from-green-500 via-sea-blue to-lagoon" />
                    <CardContent className="flex flex-col gap-4 pt-6 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p className="text-sm text-muted-foreground">Votre bateau est prêt à</p>
                            <p className="text-2xl font-bold">{securityData.readiness}%</p>
                            <p className="text-green-600 dark:text-green-400">{securityData.readiness_label}</p>
                        </div>
                        <Badge variant="secondary" className="text-base">
                            {done}/{securityData.checklist.length} points vérifiés
                        </Badge>
                    </CardContent>
                </Card>

                <div className="grid gap-4 lg:grid-cols-2">
                    <Card className="border-border/60">
                        <CardHeader>
                            <CardTitle className="text-base">Checklist départ</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            {securityData.checklist.map((item) => (
                                <div
                                    key={item.label}
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
                                    <div>
                                        <p className={item.done ? 'text-muted-foreground' : 'font-medium'}>
                                            {item.label}
                                        </p>
                                        <p className="text-xs text-muted-foreground">{item.category}</p>
                                    </div>
                                </div>
                            ))}
                        </CardContent>
                    </Card>

                    <div className="space-y-4">
                        <Card className="border-border/60">
                            <CardHeader>
                                <CardTitle className="text-base">Réglementation par pays</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {securityData.countries.map((country) => (
                                    <div key={country.name} className="rounded-xl border border-border/60 p-4">
                                        <p className="font-semibold">
                                            {country.flag} {country.name}
                                        </p>
                                        <p className="mt-1 text-xs text-muted-foreground">
                                            Documents : {country.docs.join(', ')}
                                        </p>
                                        <p className="mt-1 text-sm">{country.rules}</p>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>

                        <Card className="border-destructive/30 bg-destructive/5">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <Phone className="size-4" />
                                    Numéros d&apos;urgence
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-2">
                                {securityData.emergency.map((contact) => (
                                    <div key={contact.label} className="flex justify-between text-sm">
                                        <span className="text-muted-foreground">{contact.label}</span>
                                        <span className="font-mono font-bold">{contact.value}</span>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </>
    );
}

DemoSecurity.layout = (props: Props) => ({
    breadcrumbs: [{ title: 'Sécurité', href: mariwazeRoutesForPreview(!!props.preview).security }],
});
