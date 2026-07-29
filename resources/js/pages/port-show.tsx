import { Head, Link, useForm } from '@inertiajs/react';
import { Anchor, MapPin, Phone, Radio, Ship, Users } from 'lucide-react';
import { type FormEvent } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { appRoutes } from '@/lib/mariwaze-routes';

type Review = {
    id: number;
    author: string;
    rating: number;
    body: string | null;
    visited_on: string | null;
};

type Port = {
    slug: string;
    name: string;
    region: string | null;
    country_code: string | null;
    latitude: number;
    longitude: number;
    rating: number | null;
    reviews_count: number;
    photos_count: number;
    min_depth_meters: number | null;
    max_length_meters: number | null;
    max_width_meters: number | null;
    berths: number | null;
    services: string[];
    vhf_channel: string | null;
    phone: string | null;
    website: string | null;
    night_price_eur: number | null;
    description: string | null;
    source: string;
    compatibility: { compatible: boolean; score: number; reasons: string[] } | null;
    reviews: Review[];
};

type Props = {
    port: Port;
};

export default function PortShow({ port }: Props) {
    const info: [string, string][] = [
        ['Profondeur', port.min_depth_meters !== null ? `${port.min_depth_meters} m` : 'Non renseignée'],
        [
            'Longueur max.',
            port.max_length_meters !== null ? `${port.max_length_meters} m` : 'Non renseignée',
        ],
        ['Largeur max.', port.max_width_meters !== null ? `${port.max_width_meters} m` : 'Non renseignée'],
        ['Places', port.berths !== null ? String(port.berths) : 'Non renseigné'],
        ['Position', `${port.latitude.toFixed(4)}, ${port.longitude.toFixed(4)}`],
    ];

    return (
        <>
            <Head title={port.name} />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="grid gap-6 lg:grid-cols-5">
                    <div className="space-y-6 lg:col-span-3">
                        <div className="overflow-hidden rounded-2xl border border-sand/60 bg-gradient-to-br from-sea-blue/10 to-lagoon/10 p-8">
                            <div className="mb-4 flex items-start justify-between gap-4">
                                <div>
                                    <p className="text-sm text-muted-foreground">Fiche port</p>
                                    <h1 className="text-3xl font-bold">⚓ {port.name}</h1>
                                    <p className="mt-1 flex items-center gap-1 text-muted-foreground">
                                        <MapPin className="size-4" />
                                        {port.region ?? 'Région non renseignée'}
                                    </p>
                                </div>
                                <div className="text-right">
                                    {port.rating !== null ? (
                                        <>
                                            <p className="text-lg text-amber-500">★ {port.rating}</p>
                                            <p className="text-xs text-muted-foreground">
                                                {port.reviews_count} avis
                                            </p>
                                        </>
                                    ) : (
                                        <p className="text-xs text-muted-foreground">Aucun avis</p>
                                    )}
                                </div>
                            </div>

                            {port.description && <p className="text-lg leading-relaxed">{port.description}</p>}

                            {port.services.length > 0 && (
                                <div className="mt-4 flex flex-wrap gap-2">
                                    {port.services.map((service) => (
                                        <Badge
                                            key={service}
                                            variant="secondary"
                                            className="bg-white/60 dark:bg-white/10"
                                        >
                                            {service}
                                        </Badge>
                                    ))}
                                </div>
                            )}

                            <p className="mt-4 text-xs text-muted-foreground">
                                Données issues d&apos;OpenStreetMap — services et tarifs à confirmer auprès
                                de la capitainerie.
                            </p>
                        </div>

                        <Card className="border-border/60">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Users className="size-5" />
                                    Avis navigateurs
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {port.reviews.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        Aucun avis pour l&apos;instant. Partagez votre expérience de ce port.
                                    </p>
                                ) : (
                                    port.reviews.map((review) => (
                                        <div
                                            key={review.id}
                                            className="rounded-xl border border-border/60 p-4"
                                        >
                                            <div className="mb-2 flex items-center justify-between">
                                                <span className="font-medium">{review.author}</span>
                                                {review.visited_on && (
                                                    <span className="text-xs text-muted-foreground">
                                                        {review.visited_on}
                                                    </span>
                                                )}
                                            </div>
                                            <p className="text-sm text-amber-500">
                                                {'★'.repeat(review.rating)}
                                            </p>
                                            {review.body && (
                                                <p className="mt-1 text-sm text-muted-foreground">
                                                    {review.body}
                                                </p>
                                            )}
                                        </div>
                                    ))
                                )}

                                <ReviewForm slug={port.slug} />
                            </CardContent>
                        </Card>
                    </div>

                    <div className="space-y-4 lg:col-span-2">
                        {port.compatibility && (
                            <Card className="border-border/60">
                                <CardContent className="pt-6">
                                    <p className="text-sm text-muted-foreground">Adapté à votre bateau</p>
                                    <div
                                        className={cn(
                                            'mt-2 inline-flex rounded-lg px-3 py-1.5 text-sm font-semibold',
                                            port.compatibility.compatible
                                                ? 'bg-green-500/15 text-green-700 dark:text-green-400'
                                                : 'bg-safety-orange/15 text-safety-orange',
                                        )}
                                    >
                                        {port.compatibility.compatible ? 'Compatible' : 'À vérifier'} —{' '}
                                        {port.compatibility.score}%
                                    </div>
                                    {port.compatibility.reasons.length > 0 && (
                                        <ul className="mt-3 space-y-1 text-sm text-muted-foreground">
                                            {port.compatibility.reasons.map((reason) => (
                                                <li key={reason}>• {reason}</li>
                                            ))}
                                        </ul>
                                    )}
                                </CardContent>
                            </Card>
                        )}

                        <Card className="border-border/60">
                            <CardHeader className="pb-2">
                                <CardTitle className="text-base">Caractéristiques</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-2 text-sm">
                                {info.map(([label, value]) => (
                                    <div key={label} className="flex justify-between gap-4">
                                        <span className="text-muted-foreground">{label}</span>
                                        <span className="text-right font-medium">{value}</span>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>

                        {(port.vhf_channel || port.phone || port.website) && (
                            <Card className="border-border/60">
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-base">Contact capitainerie</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-2 text-sm">
                                    {port.vhf_channel && (
                                        <p className="flex items-center gap-2">
                                            <Radio className="size-4 text-muted-foreground" />
                                            VHF {port.vhf_channel}
                                        </p>
                                    )}
                                    {port.phone && (
                                        <a
                                            href={`tel:${port.phone.replace(/\s/g, '')}`}
                                            className="flex items-center gap-2 hover:text-sea-blue"
                                        >
                                            <Phone className="size-4 text-muted-foreground" />
                                            {port.phone}
                                        </a>
                                    )}
                                    {port.website && (
                                        <a
                                            href={port.website}
                                            target="_blank"
                                            rel="noreferrer noopener"
                                            className="flex items-center gap-2 break-all hover:text-sea-blue"
                                        >
                                            <Ship className="size-4 shrink-0 text-muted-foreground" />
                                            {port.website}
                                        </a>
                                    )}
                                </CardContent>
                            </Card>
                        )}

                        <Button asChild variant="outline" className="w-full">
                            <Link href={appRoutes.explore}>
                                <Anchor className="size-4" />
                                Retour à l&apos;exploration
                            </Link>
                        </Button>
                    </div>
                </div>
            </div>
        </>
    );
}

function ReviewForm({ slug }: { slug: string }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        rating: 5,
        body: '',
        visited_on: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(`/ports/${slug}/avis`, { onSuccess: () => reset('body') });
    };

    return (
        <form onSubmit={submit} className="space-y-3 rounded-xl border border-dashed border-border/60 p-4">
            <p className="text-sm font-medium">Partager mon avis</p>

            <div className="grid gap-3 sm:grid-cols-2">
                <div>
                    <Label htmlFor="rating" className="mb-1 block text-xs">
                        Note (1 à 5)
                    </Label>
                    <Input
                        id="rating"
                        type="number"
                        min={1}
                        max={5}
                        value={data.rating}
                        onChange={(event) => setData('rating', Number(event.target.value))}
                    />
                    {errors.rating && <p className="mt-1 text-xs text-destructive">{errors.rating}</p>}
                </div>
                <div>
                    <Label htmlFor="visited_on" className="mb-1 block text-xs">
                        Date de passage
                    </Label>
                    <Input
                        id="visited_on"
                        type="date"
                        value={data.visited_on}
                        onChange={(event) => setData('visited_on', event.target.value)}
                    />
                </div>
            </div>

            <div>
                <Label htmlFor="body" className="mb-1 block text-xs">
                    Commentaire
                </Label>
                <textarea
                    id="body"
                    rows={3}
                    value={data.body}
                    onChange={(event) => setData('body', event.target.value)}
                    className="w-full rounded-md border border-border/60 bg-background px-3 py-2 text-sm"
                    placeholder="Accueil, services, accès, tarifs…"
                />
                {errors.body && <p className="mt-1 text-xs text-destructive">{errors.body}</p>}
            </div>

            <Button type="submit" disabled={processing} className="bg-sea-blue hover:bg-sea-blue/90">
                Publier mon avis
            </Button>
        </form>
    );
}

PortShow.layout = (props: Props) => ({
    breadcrumbs: [
        { title: 'Explorer', href: appRoutes.explore },
        { title: props.port.name, href: appRoutes.port(props.port.slug) },
    ],
});
