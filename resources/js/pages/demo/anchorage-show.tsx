import { Head, Link } from '@inertiajs/react';
import { Anchor, Camera, MapPin, Star, Users, Wind } from 'lucide-react';
import NauticalChart from '@/components/mariwaze/nautical-chart';
import PreviewBanner from '@/components/mariwaze/preview-banner';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { type ChartData, mariwazeRoutesForPreview } from '@/lib/mariwaze-routes';
import { useMariwazeRoutes } from '@/hooks/use-mariwaze-routes';

type Anchorage = {
    slug: string;
    name: string;
    region: string;
    rating: number;
    reviews: number;
    photos: number;
    compatibility: string;
    compatibility_score: number;
    summary: string;
    depth: string;
    bottom: string;
    holding: string;
    protection: string[];
    exposure: string;
    ideal_for: string[];
    warnings: string[];
    tags: string[];
    chart: ChartData;
    community: { author: string; rating: number; text: string; date: string }[];
};

type Props = {
    anchorage: Anchorage;
    preview?: boolean;
};

const compatibilityStyles: Record<string, string> = {
    recommandé: 'bg-green-500/15 text-green-700 dark:text-green-400',
    compatible: 'bg-sea-blue/15 text-sea-blue dark:text-lagoon',
};

export default function DemoAnchorageShow({ anchorage, preview = false }: Props) {
    const routes = useMariwazeRoutes(preview);

    return (
        <>
            <Head title={anchorage.name} />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PreviewBanner preview={preview} />

                <div className="grid gap-6 lg:grid-cols-5">
                    <div className="space-y-6 lg:col-span-3">
                        <div className="overflow-hidden rounded-2xl border border-sand/60 bg-gradient-to-br from-sand/30 to-lagoon/10 p-8 dark:from-sand/10 dark:to-lagoon/5">
                            <div className="mb-4 flex items-start justify-between gap-4">
                                <div>
                                    <p className="text-sm text-muted-foreground">Fiche mouillage</p>
                                    <h1 className="text-3xl font-bold">🏝 {anchorage.name}</h1>
                                    <p className="mt-1 flex items-center gap-1 text-muted-foreground">
                                        <MapPin className="size-4" />
                                        {anchorage.region}
                                    </p>
                                </div>
                                <div className="text-right">
                                    <p className="text-lg text-amber-500">
                                        {'★'.repeat(anchorage.rating)}
                                        {'☆'.repeat(5 - anchorage.rating)}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {anchorage.reviews} avis · {anchorage.photos} photos
                                    </p>
                                </div>
                            </div>

                            <p className="text-lg leading-relaxed">{anchorage.summary}</p>

                            <div className="mt-4 flex flex-wrap gap-2">
                                {anchorage.tags.map((tag) => (
                                    <Badge key={tag} variant="secondary" className="bg-white/60 dark:bg-white/10">
                                        {tag}
                                    </Badge>
                                ))}
                            </div>
                        </div>

                        <NauticalChart
                            data={anchorage.chart}
                            className="aspect-[2/1] min-h-[220px]"
                            highlightedMarker={anchorage.slug}
                            showRoute={false}
                        />

                        <Card className="border-border/60">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Users className="size-5" />
                                    Avis navigateurs
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {anchorage.community.map((review) => (
                                    <div key={review.author} className="rounded-xl border border-border/60 p-4">
                                        <div className="mb-2 flex items-center justify-between">
                                            <span className="font-medium">{review.author}</span>
                                            <span className="text-xs text-muted-foreground">{review.date}</span>
                                        </div>
                                        <p className="text-amber-500 text-sm">{'★'.repeat(review.rating)}</p>
                                        <p className="mt-1 text-sm text-muted-foreground">{review.text}</p>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                    </div>

                    <div className="space-y-4 lg:col-span-2">
                        <Card className="border-border/60">
                            <CardContent className="pt-6">
                                <p className="text-sm text-muted-foreground">Adapté à votre bateau</p>
                                <div
                                    className={cn(
                                        'mt-2 inline-flex rounded-lg px-3 py-1.5 text-sm font-semibold capitalize',
                                        compatibilityStyles[anchorage.compatibility] ??
                                            'bg-muted text-muted-foreground',
                                    )}
                                >
                                    🟢 {anchorage.compatibility} — {anchorage.compatibility_score}%
                                </div>
                            </CardContent>
                        </Card>

                        <InfoBlock title="Informations marines" items={[
                            ['Profondeur', anchorage.depth],
                            ['Nature du fond', anchorage.bottom],
                            ['Tenue ancre', anchorage.holding],
                            ['Protection', anchorage.protection.join(', ')],
                            ['Exposition', anchorage.exposure],
                        ]} />

                        <Card className="border-border/60">
                            <CardHeader className="pb-2">
                                <CardTitle className="text-base">Idéal pour</CardTitle>
                            </CardHeader>
                            <CardContent className="flex flex-wrap gap-2">
                                {anchorage.ideal_for.map((item) => (
                                    <Badge key={item} className="bg-lagoon/20 text-ocean-navy dark:text-lagoon">
                                        {item}
                                    </Badge>
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
                                    {anchorage.warnings.map((w) => (
                                        <p key={w}>⚠ {w}</p>
                                    ))}
                                </CardContent>
                            </Card>
                        )}

                        <div className="flex flex-col gap-2">
                            <Button className="bg-sea-blue hover:bg-sea-blue/90">
                                <Anchor className="size-4" />
                                Ajouter à ma croisière
                            </Button>
                            <Button asChild variant="outline">
                                <Link href={routes.cruise('tour-corse-2027')}>
                                    Voir la croisière liée
                                </Link>
                            </Button>
                            <Button variant="ghost" className="text-muted-foreground">
                                <Camera className="size-4" />
                                Voir les {anchorage.photos} photos
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

function InfoBlock({ title, items }: { title: string; items: [string, string][] }) {
    return (
        <Card className="border-border/60">
            <CardHeader className="pb-2">
                <CardTitle className="text-base">{title}</CardTitle>
            </CardHeader>
            <CardContent className="space-y-2 text-sm">
                {items.map(([label, value]) => (
                    <div key={label} className="flex justify-between gap-4">
                        <span className="text-muted-foreground">{label}</span>
                        <span className="text-right font-medium">{value}</span>
                    </div>
                ))}
            </CardContent>
        </Card>
    );
}

DemoAnchorageShow.layout = (props: Props) => {
    const routes = mariwazeRoutesForPreview(!!props.preview);

    return {
        breadcrumbs: [
            { title: 'Explorer', href: routes.explore },
            { title: props.anchorage.name, href: routes.anchorage(props.anchorage.slug) },
        ],
    };
};
