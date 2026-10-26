import { Head, Link, usePage } from '@inertiajs/react';
import Anchor from 'lucide-react/icons/anchor';
import ArrowRight from 'lucide-react/icons/arrow-right';
import Compass from 'lucide-react/icons/compass';
import Map from 'lucide-react/icons/map';
import Shield from 'lucide-react/icons/shield';
import Sparkles from 'lucide-react/icons/sparkles';
import Wind from 'lucide-react/icons/wind';
import MariwazeLogo from '@/components/mariwaze/mariwaze-logo';
import { Button } from '@/components/ui/button';
import { dashboard, home, login, register } from '@/routes';

const features = [
    {
        icon: Map,
        title: 'Itinéraires intelligents',
        description:
            'Routes rapide, confort ou découverte — adaptées à votre bateau et à vos envies.',
    },
    {
        icon: Wind,
        title: 'Météo marine traduite',
        description:
            'Vent, houle et conditions expliqués pour plaisanciers, pas en jargon météo.',
    },
    {
        icon: Anchor,
        title: 'Ports & mouillages',
        description:
            'Fiches détaillées avec profondeur, protection et avis de la communauté.',
    },
    {
        icon: Shield,
        title: 'Sécurité rassurante',
        description:
            'Checklist personnalisée, réglementation par pays et audit de votre bateau.',
    },
];

export default function Welcome() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Votre copilote maritime" />
            <div className="min-h-svh bg-foam">
                <header className="sticky top-0 z-50 border-b border-border/40 bg-foam/80 backdrop-blur-md">
                    <div className="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
                        <MariwazeLogo href={home().url} showTagline />
                        <nav className="flex items-center gap-3">
                            {auth.user ? (
                                <Button
                                    asChild
                                    className="bg-sea-blue hover:bg-sea-blue/90"
                                >
                                    <Link href={dashboard()}>Mon espace</Link>
                                </Button>
                            ) : (
                                <>
                                    <Button
                                        asChild
                                        variant="ghost"
                                        className="text-ocean-navy"
                                    >
                                        <Link href={login()}>Connexion</Link>
                                    </Button>
                                    <Button
                                        asChild
                                        className="bg-sea-blue hover:bg-sea-blue/90"
                                    >
                                        <Link href={register()}>Commencer</Link>
                                    </Button>
                                </>
                            )}
                        </nav>
                    </div>
                </header>

                <section className="relative overflow-hidden">
                    <div className="mariwaze-gradient-ocean absolute inset-0 opacity-[0.04]" />
                    <div className="absolute -top-24 -right-24 size-96 rounded-full bg-lagoon/10 blur-3xl" />
                    <div className="absolute -bottom-24 -left-24 size-96 rounded-full bg-sand/30 blur-3xl" />

                    <div className="relative mx-auto grid max-w-6xl gap-12 px-6 py-20 lg:grid-cols-2 lg:items-center lg:py-28">
                        <div className="space-y-8">
                            <div className="inline-flex items-center gap-2 rounded-full border border-sea-blue/20 bg-white px-4 py-1.5 text-sm text-sea-blue">
                                <Sparkles className="size-4" />
                                Assistant de croisière maritime
                            </div>

                            <h1 className="text-4xl leading-tight font-bold tracking-tight text-ocean-navy md:text-5xl lg:text-6xl">
                                Préparez votre voyage en mer avec un{' '}
                                <span className="bg-gradient-to-r from-sea-blue to-lagoon bg-clip-text text-transparent">
                                    copilote fiable
                                </span>
                            </h1>

                            <p className="max-w-lg text-lg text-muted-foreground">
                                MariWaze n&apos;est pas un GPS marin. C&apos;est
                                votre carnet de navigation intelligent : routes,
                                météo, mouillages et sécurité — pensé par des
                                marins, pour les plaisanciers.
                            </p>

                            <div className="flex flex-wrap gap-4">
                                <Button
                                    asChild
                                    size="lg"
                                    className="bg-sea-blue hover:bg-sea-blue/90"
                                >
                                    <Link
                                        href={
                                            auth.user ? dashboard() : register()
                                        }
                                    >
                                        {auth.user
                                            ? 'Accéder à mon espace'
                                            : 'Créer mon compte'}
                                        <ArrowRight className="size-4" />
                                    </Link>
                                </Button>
                                <Button asChild size="lg" variant="outline">
                                    <Link href="/dashboard">
                                        Ouvrir l&apos;application
                                    </Link>
                                </Button>
                            </div>
                        </div>

                        <HeroPreviewCard />
                    </div>
                </section>

                <section className="border-y border-border/40 bg-white py-20">
                    <div className="mx-auto max-w-6xl px-6">
                        <div className="mb-12 text-center">
                            <h2 className="text-3xl font-bold text-ocean-navy">
                                Un carnet de voyage numérique
                            </h2>
                            <p className="mt-3 text-muted-foreground">
                                La technologie vous aide sans prendre la place
                                du marin.
                            </p>
                        </div>

                        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                            {features.map((feature) => (
                                <div
                                    key={feature.title}
                                    className="group rounded-2xl border border-border/60 bg-foam/50 p-6 transition-shadow hover:shadow-lg"
                                >
                                    <div className="mb-4 flex size-12 items-center justify-center rounded-xl bg-ocean-navy/5 text-sea-blue transition-colors group-hover:bg-sea-blue group-hover:text-white">
                                        <feature.icon className="size-6" />
                                    </div>
                                    <h3 className="mb-2 font-semibold text-ocean-navy">
                                        {feature.title}
                                    </h3>
                                    <p className="text-sm text-muted-foreground">
                                        {feature.description}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                <section className="border-t border-border/40 bg-muted/30 py-16">
                    <div className="mx-auto max-w-6xl px-6">
                        <h2 className="mb-2 text-center text-2xl font-bold text-ocean-navy dark:text-foreground">
                            Explorer l&apos;application
                        </h2>
                        <p className="mb-8 text-center text-muted-foreground">
                            Toutes les pages ci-dessous fonctionnent avec des
                            données fictives
                        </p>
                        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            {[
                                {
                                    href: '/dashboard',
                                    label: 'Tableau de bord',
                                },
                                {
                                    href: '/croisieres/nouvelle',
                                    label: 'Nouvelle croisière',
                                },
                                { href: '/carte', label: 'Carte marine' },
                                {
                                    href: '/mouillages/cala-macarella',
                                    label: 'Fiche mouillage',
                                },
                                { href: '/explorer', label: 'Explorer' },
                                { href: '/bateau', label: 'Mon bateau' },
                                {
                                    href: '/bateau/configurer',
                                    label: 'Config. bateau',
                                },
                                { href: '/securite', label: 'Sécurité' },
                            ].map((link) => (
                                <Link
                                    key={link.href}
                                    href={link.href}
                                    className="rounded-xl border border-border/60 bg-white px-4 py-3 text-sm font-medium transition-colors hover:border-sea-blue hover:text-sea-blue dark:bg-card dark:hover:border-lagoon dark:hover:text-lagoon"
                                >
                                    {link.label} →
                                </Link>
                            ))}
                        </div>
                    </div>
                </section>

                <section className="mariwaze-gradient-ocean py-20 text-white">
                    <div className="mx-auto max-w-3xl px-6 text-center">
                        <Compass className="mx-auto mb-6 size-12 text-lagoon" />
                        <blockquote className="text-2xl leading-relaxed font-medium md:text-3xl">
                            « Je prépare une belle aventure en mer, avec un
                            copilote fiable. »
                        </blockquote>
                        <p className="mt-6 text-white/70">
                            Rejoignez les navigateurs qui préparent leurs
                            croisières sereinement.
                        </p>
                        <Button
                            asChild
                            size="lg"
                            className="mt-8 bg-white text-ocean-navy hover:bg-white/90"
                        >
                            <Link href={register()}>
                                Commencer gratuitement
                            </Link>
                        </Button>
                    </div>
                </section>

                <footer className="border-t border-border/40 bg-ocean-navy py-8 text-white/60">
                    <div className="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-6 md:flex-row">
                        <MariwazeLogo variant="light" showTagline />
                        <p className="text-sm">
                            © 2027 MariWaze — Copilote maritime intelligent
                        </p>
                    </div>
                </footer>
            </div>
        </>
    );
}

function HeroPreviewCard() {
    return (
        <div className="relative">
            <div className="absolute -inset-4 rounded-3xl bg-gradient-to-br from-sea-blue/20 to-lagoon/20 blur-2xl" />
            <div className="relative overflow-hidden rounded-2xl border border-border/60 bg-white shadow-2xl">
                <div className="h-1.5 bg-gradient-to-r from-ocean-navy via-sea-blue to-lagoon" />
                <div className="p-6">
                    <p className="text-xs font-medium tracking-wide text-sea-blue uppercase">
                        Ma prochaine croisière
                    </p>
                    <h3 className="mt-1 text-xl font-bold text-ocean-navy">
                        Votre prochaine croisière
                    </h3>
                    <p className="text-sm text-muted-foreground">
                        Biarritz → Bonifacio
                    </p>

                    <div className="mt-6 grid grid-cols-3 gap-4 rounded-xl bg-muted/50 p-4 text-center">
                        <div>
                            <p className="text-2xl font-bold text-sea-blue">
                                350
                            </p>
                            <p className="text-xs text-muted-foreground">
                                milles
                            </p>
                        </div>
                        <div>
                            <p className="text-2xl font-bold text-sea-blue">
                                18
                            </p>
                            <p className="text-xs text-muted-foreground">
                                escales
                            </p>
                        </div>
                        <div>
                            <p className="text-2xl font-bold text-green-600">
                                92%
                            </p>
                            <p className="text-xs text-muted-foreground">
                                confiance
                            </p>
                        </div>
                    </div>

                    <div className="mt-4 rounded-xl bg-green-500/10 px-4 py-3 text-sm text-green-700">
                        🟢 Navigation recommandée — Vent favorable pour votre
                        voilier
                    </div>

                    <div className="mt-4 flex items-center gap-2 text-xs text-muted-foreground">
                        <div className="h-2 flex-1 overflow-hidden rounded-full bg-muted">
                            <div className="h-full w-[68%] rounded-full bg-gradient-to-r from-sea-blue to-lagoon" />
                        </div>
                        <span>68% préparé</span>
                    </div>
                </div>
            </div>
        </div>
    );
}
