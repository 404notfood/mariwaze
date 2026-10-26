import { Head, Link, useForm } from '@inertiajs/react';
import Anchor from 'lucide-react/icons/anchor';
import MapPin from 'lucide-react/icons/map-pin';
import Users from 'lucide-react/icons/users';
import Wind from 'lucide-react/icons/wind';
import type { FormEvent } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { appRoutes } from '@/lib/mariwaze-routes';
import { cn } from '@/lib/utils';

type Review = {
    id: number;
    author: string;
    rating: number;
    body: string | null;
    visited_on: string | null;
};

type Anchorage = {
    slug: string;
    name: string;
    region: string | null;
    country_code: string | null;
    latitude: number;
    longitude: number;
    rating: number | null;
    reviews_count: number;
    photos_count: number;
    depth: string | null;
    bottom: string | null;
    holding: string | null;
    protection: string[];
    exposure: string | null;
    tags: string[];
    warnings: string[];
    description: string | null;
    source: string;
    compatibility: {
        compatible: boolean;
        score: number;
        reasons: string[];
    } | null;
    reviews: Review[];
};

type Props = {
    anchorage: Anchorage;
};

export default function AnchorageShow({ anchorage }: Props) {
    const info: [string, string][] = [
        ['Profondeur', anchorage.depth ?? 'Non renseignée'],
        ['Nature du fond', anchorage.bottom ?? 'Non renseignée'],
        ['Tenue ancre', anchorage.holding ?? 'Non renseignée'],
        [
            'Protection',
            anchorage.protection.length > 0
                ? anchorage.protection.join(', ')
                : 'Non renseignée',
        ],
        ['Exposition', anchorage.exposure ?? 'Non renseignée'],
        [
            'Position',
            `${anchorage.latitude.toFixed(4)}, ${anchorage.longitude.toFixed(4)}`,
        ],
    ];

    return (
        <>
            <Head title={anchorage.name} />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="grid gap-6 lg:grid-cols-5">
                    <div className="space-y-6 lg:col-span-3">
                        <div className="overflow-hidden rounded-2xl border border-sand/60 bg-gradient-to-br from-sand/30 to-lagoon/10 p-8 dark:from-sand/10 dark:to-lagoon/5">
                            <div className="mb-4 flex items-start justify-between gap-4">
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Fiche mouillage
                                    </p>
                                    <h1 className="text-3xl font-bold">
                                        🏝 {anchorage.name}
                                    </h1>
                                    <p className="mt-1 flex items-center gap-1 text-muted-foreground">
                                        <MapPin className="size-4" />
                                        {anchorage.region ??
                                            'Région non renseignée'}
                                    </p>
                                </div>
                                <div className="text-right">
                                    {anchorage.rating !== null ? (
                                        <>
                                            <p className="text-lg text-amber-500">
                                                ★ {anchorage.rating}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {anchorage.reviews_count} avis
                                            </p>
                                        </>
                                    ) : (
                                        <p className="text-xs text-muted-foreground">
                                            Aucun avis
                                        </p>
                                    )}
                                </div>
                            </div>

                            {anchorage.description && (
                                <p className="text-lg leading-relaxed">
                                    {anchorage.description}
                                </p>
                            )}

                            {anchorage.tags.length > 0 && (
                                <div className="mt-4 flex flex-wrap gap-2">
                                    {anchorage.tags.map((tag) => (
                                        <Badge
                                            key={tag}
                                            variant="secondary"
                                            className="bg-white/60 dark:bg-white/10"
                                        >
                                            {tag}
                                        </Badge>
                                    ))}
                                </div>
                            )}

                            <p className="mt-4 text-xs text-muted-foreground">
                                Données issues d&apos;OpenStreetMap — à
                                confirmer avec la cartographie officielle et les
                                instructions nautiques.
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
                                {anchorage.reviews.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        Aucun avis pour l&apos;instant. Partagez
                                        votre expérience de ce mouillage.
                                    </p>
                                ) : (
                                    anchorage.reviews.map((review) => (
                                        <div
                                            key={review.id}
                                            className="rounded-xl border border-border/60 p-4"
                                        >
                                            <div className="mb-2 flex items-center justify-between">
                                                <span className="font-medium">
                                                    {review.author}
                                                </span>
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

                                <ReviewForm slug={anchorage.slug} />
                            </CardContent>
                        </Card>
                    </div>

                    <div className="space-y-4 lg:col-span-2">
                        {anchorage.compatibility && (
                            <Card className="border-border/60">
                                <CardContent className="pt-6">
                                    <p className="text-sm text-muted-foreground">
                                        Adapté à votre bateau
                                    </p>
                                    <div
                                        className={cn(
                                            'mt-2 inline-flex rounded-lg px-3 py-1.5 text-sm font-semibold',
                                            anchorage.compatibility.compatible
                                                ? 'bg-green-500/15 text-green-700 dark:text-green-400'
                                                : 'bg-safety-orange/15 text-safety-orange',
                                        )}
                                    >
                                        {anchorage.compatibility.compatible
                                            ? 'Compatible'
                                            : 'À vérifier'}{' '}
                                        — {anchorage.compatibility.score}%
                                    </div>
                                    {anchorage.compatibility.reasons.length >
                                        0 && (
                                        <ul className="mt-3 space-y-1 text-sm text-muted-foreground">
                                            {anchorage.compatibility.reasons.map(
                                                (reason) => (
                                                    <li key={reason}>
                                                        • {reason}
                                                    </li>
                                                ),
                                            )}
                                        </ul>
                                    )}
                                </CardContent>
                            </Card>
                        )}

                        <Card className="border-border/60">
                            <CardHeader className="pb-2">
                                <CardTitle className="text-base">
                                    Informations marines
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-2 text-sm">
                                {info.map(([label, value]) => (
                                    <div
                                        key={label}
                                        className="flex justify-between gap-4"
                                    >
                                        <span className="text-muted-foreground">
                                            {label}
                                        </span>
                                        <span className="text-right font-medium">
                                            {value}
                                        </span>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>

                        {anchorage.warnings.length > 0 && (
                            <Card className="border-safety-orange/30 bg-safety-orange/5">
                                <CardHeader className="pb-2">
                                    <CardTitle className="flex items-center gap-2 text-base text-safety-orange">
                                        <Wind className="size-4" />
                                        Points d&apos;attention
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-1 text-sm">
                                    {anchorage.warnings.map((warning) => (
                                        <p key={warning}>⚠ {warning}</p>
                                    ))}
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
        post(`/mouillages/${slug}/avis`, { onSuccess: () => reset('body') });
    };

    return (
        <form
            onSubmit={submit}
            className="space-y-3 rounded-xl border border-dashed border-border/60 p-4"
        >
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
                        onChange={(event) =>
                            setData('rating', Number(event.target.value))
                        }
                    />
                    {errors.rating && (
                        <p className="mt-1 text-xs text-destructive">
                            {errors.rating}
                        </p>
                    )}
                </div>
                <div>
                    <Label htmlFor="visited_on" className="mb-1 block text-xs">
                        Date de passage
                    </Label>
                    <Input
                        id="visited_on"
                        type="date"
                        value={data.visited_on}
                        onChange={(event) =>
                            setData('visited_on', event.target.value)
                        }
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
                    placeholder="Tenue de l'ancre, protection, fréquentation…"
                />
                {errors.body && (
                    <p className="mt-1 text-xs text-destructive">
                        {errors.body}
                    </p>
                )}
            </div>

            <Button
                type="submit"
                disabled={processing}
                className="bg-sea-blue hover:bg-sea-blue/90"
            >
                Publier mon avis
            </Button>
        </form>
    );
}

AnchorageShow.layout = (props: Props) => ({
    breadcrumbs: [
        { title: 'Explorer', href: appRoutes.explore },
        {
            title: props.anchorage.name,
            href: appRoutes.anchorage(props.anchorage.slug),
        },
    ],
});
