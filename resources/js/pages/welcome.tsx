import { Head, Link, usePage } from '@inertiajs/react';
import ArrowRight from 'lucide-react/icons/arrow-right';
import MariwazeLogo from '@/components/mariwaze/mariwaze-logo';
import { Button } from '@/components/ui/button';
import { dashboard, home, login, register } from '@/routes';

const features = [
    {
        code: '01 / Bateau',
        title: 'Vos limites, pas des moyennes',
        description:
            'Tirant d’eau, vitesse et armement : chaque recommandation tient compte de votre bateau.',
    },
    {
        code: '02 / Itinéraire',
        title: 'Rapide, confort ou découverte',
        description:
            'Plusieurs routes par trajet, avec leurs escales et leurs mouillages.',
    },
    {
        code: '03 / Météo',
        title: 'Un avis par étape',
        description:
            'Vent et houle traduits en mots clairs : favorable, à surveiller, défavorable.',
    },
    {
        code: '04 / Sécurité',
        title: 'Rien d’oublié',
        description:
            'Checklist personnalisée, réglementation par pays et audit de l’armement.',
    },
];

const demoLinks = [
    { href: '/dashboard', label: 'Tableau de bord' },
    { href: '/croisieres/nouvelle', label: 'Nouvelle croisière' },
    { href: '/carte', label: 'Carte marine' },
    { href: '/mouillages/cala-macarella', label: 'Fiche mouillage' },
    { href: '/explorer', label: 'Ports & mouillages' },
    { href: '/bateau', label: 'Mon bateau' },
    { href: '/bateau/configurer', label: 'Config. bateau' },
    { href: '/securite', label: 'Sécurité' },
];

export default function Welcome() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Votre copilote de croisière" />
            <div className="min-h-svh bg-background text-foreground">
                <header className="sticky top-0 z-50 border-b border-border bg-background/95">
                    <div className="mx-auto flex max-w-7xl items-center justify-between px-6 py-3">
                        <MariwazeLogo href={home().url} />
                        <nav className="flex items-center gap-2">
                            {auth.user ? (
                                <Button asChild>
                                    <Link href={dashboard()}>Mon espace</Link>
                                </Button>
                            ) : (
                                <>
                                    <Button asChild variant="ghost">
                                        <Link href={login()}>Connexion</Link>
                                    </Button>
                                    <Button asChild variant="outline">
                                        <Link href={register()}>
                                            Créer un compte
                                        </Link>
                                    </Button>
                                </>
                            )}
                        </nav>
                    </div>
                </header>

                <section className="border-b border-border">
                    <div className="mx-auto grid max-w-7xl gap-12 px-6 py-16 lg:grid-cols-[minmax(0,1fr)_480px] lg:items-center lg:py-24">
                        <div>
                            <p className="pw-label">
                                Copilote de croisière · préparation
                            </p>
                            <h1 className="mt-4 text-5xl leading-[0.98] font-bold tracking-[0.005em] uppercase md:text-6xl">
                                Savoir si l’on part.{' '}
                                <span className="text-primary">
                                    Étape par étape.
                                </span>
                            </h1>
                            <p className="mt-6 max-w-xl text-lg leading-7 text-muted-foreground">
                                MariWaze n’est pas un GPS marin. Il prépare la
                                croisière : itinéraire adapté à votre bateau,
                                escales vérifiées, avis météo pour chaque étape
                                et liste de ce qu’il reste à régler.
                            </p>
                            <div className="mt-8 flex flex-wrap gap-3">
                                <Button asChild size="lg">
                                    <Link
                                        href={
                                            auth.user ? dashboard() : register()
                                        }
                                    >
                                        {auth.user
                                            ? 'Accéder à mon espace'
                                            : 'Préparer une croisière'}
                                        <ArrowRight className="size-4" />
                                    </Link>
                                </Button>
                                <Button asChild size="lg" variant="outline">
                                    <Link href="/dashboard">
                                        Ouvrir l’application
                                    </Link>
                                </Button>
                            </div>
                            <ul className="mt-8 flex flex-wrap gap-x-6 gap-y-2">
                                {[
                                    'Escales filtrées au tirant d’eau',
                                    'Avis météo par étape',
                                ].map((item) => (
                                    <li
                                        key={item}
                                        className="pw-label flex items-center gap-2"
                                    >
                                        <i className="size-1.5 bg-primary" />
                                        {item}
                                    </li>
                                ))}
                            </ul>
                        </div>

                        <PreviewPanel />
                    </div>
                </section>

                <section className="border-b border-border">
                    <div className="mx-auto grid max-w-7xl sm:grid-cols-2 lg:grid-cols-4">
                        {features.map((feature) => (
                            <div
                                key={feature.code}
                                className="border-border px-6 py-8 sm:border-r sm:last:border-r-0"
                            >
                                <p className="font-mono text-xs tracking-[0.08em] text-primary uppercase">
                                    {feature.code}
                                </p>
                                <h2 className="mt-3 text-xl leading-6 font-semibold tracking-[0.03em] uppercase">
                                    {feature.title}
                                </h2>
                                <p className="mt-2 text-muted-foreground">
                                    {feature.description}
                                </p>
                            </div>
                        ))}
                    </div>
                </section>

                <section className="border-b border-border">
                    <div className="mx-auto max-w-7xl px-6 py-10">
                        <div className="flex flex-wrap items-baseline justify-between gap-2">
                            <h2 className="pw-title">Explorer l’application</h2>
                            <p className="pw-label">
                                Pages de démonstration, données fictives
                            </p>
                        </div>
                        <div className="mt-4 grid gap-px border border-border bg-border sm:grid-cols-2 lg:grid-cols-4">
                            {demoLinks.map((link) => (
                                <Link
                                    key={link.href}
                                    href={link.href}
                                    className="flex items-center justify-between bg-background px-4 py-3 font-medium transition-colors hover:bg-raised hover:text-primary"
                                >
                                    {link.label}
                                    <ArrowRight className="size-4" />
                                </Link>
                            ))}
                        </div>
                    </div>
                </section>

                <section className="mariwaze-gradient-ocean">
                    <div className="mx-auto flex max-w-7xl flex-col items-start justify-between gap-6 px-6 py-14 md:flex-row md:items-center">
                        <p className="max-w-2xl text-2xl leading-8 font-semibold tracking-[0.02em] text-[#e6edf3] uppercase">
                            MariWaze prépare la croisière.{' '}
                            <span className="text-[#8fa1b3]">
                                La veille, la carte et les décisions restent à
                                bord, avec vous.
                            </span>
                        </p>
                        {!auth.user && (
                            <Button
                                asChild
                                size="lg"
                                className="bg-[#4cc9f0] text-[#04141c] hover:bg-[#4cc9f0]/90"
                            >
                                <Link href={register()}>
                                    Commencer gratuitement
                                </Link>
                            </Button>
                        )}
                    </div>
                </section>

                <footer className="border-t border-[#1f2d3a] bg-[#070d13] py-6">
                    <div className="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-6 md:flex-row">
                        <MariwazeLogo variant="light" />
                        <p className="font-mono text-[11px] tracking-[0.08em] text-[#8fa1b3] uppercase">
                            © 2027 MariWaze — copilote de croisière
                        </p>
                    </div>
                </footer>
            </div>
        </>
    );
}

/** Aperçu illustratif de l’application (exemple, pas des données réelles). */
function PreviewPanel() {
    const limits = [
        { label: 'Vent max', value: '15', limit: '22 nds', fill: 55, mark: 81 },
        { label: 'Mer', value: '0,8', limit: '2,0 m', fill: 32, mark: 80 },
        {
            label: 'Rafales',
            value: '21',
            limit: '28 nds',
            fill: 62,
            mark: 82,
            warn: true,
        },
    ];
    const legs = [
        {
            tone: 'text-good',
            status: 'Favorable',
            to: 'Roscoff',
            meta: 'J2 · 38 M',
        },
        {
            tone: 'text-warn',
            status: 'À surveiller',
            to: 'Aber Wrac’h',
            meta: 'J3 · courant',
        },
        {
            tone: 'text-good',
            status: 'Favorable',
            to: 'Camaret',
            meta: 'J4 · étale 06:40',
        },
    ];

    return (
        <figure
            className="rounded-sm border border-line-strong bg-raised"
            aria-label="Exemple d’étape jugée par MariWaze"
        >
            <div className="flex justify-between border-b border-border px-4 py-2.5">
                <span className="pw-label">J1 · Saint-Malo → Paimpol</span>
                <span className="pw-label">Exemple · 44 M · 6 h 20</span>
            </div>
            <div className="border-b border-border px-5 py-4">
                <p className="pw-label">Avis de l’étape</p>
                <p className="mt-1 flex items-baseline gap-3">
                    <span className="font-mono text-5xl font-semibold text-good">
                        GO
                    </span>
                    <span className="text-lg font-semibold">
                        Très favorable
                    </span>
                </p>
                {limits.map((item) => (
                    <div key={item.label} className="mt-3">
                        <div className="flex justify-between text-sm">
                            <span>{item.label}</span>
                            <span className="pw-figure text-[13px]">
                                {item.value}{' '}
                                <span className="text-muted-foreground">
                                    / {item.limit}
                                </span>
                            </span>
                        </div>
                        <div className="relative mt-1 h-1.5 border border-border bg-sunken">
                            <i
                                className={
                                    item.warn
                                        ? 'absolute inset-y-0 left-0 bg-warn'
                                        : 'absolute inset-y-0 left-0 bg-good'
                                }
                                style={{ width: `${item.fill}%` }}
                            />
                            <u
                                className="absolute -inset-y-1 w-0.5 bg-foreground"
                                style={{ left: `${item.mark}%` }}
                            />
                        </div>
                    </div>
                ))}
            </div>
            <div className="grid grid-cols-3 gap-px bg-border">
                {legs.map((leg) => (
                    <div key={leg.to} className="bg-raised px-3.5 py-3">
                        <span className={`pw-status ${leg.tone}`}>
                            {leg.status}
                        </span>
                        <p className="mt-1 font-semibold">→ {leg.to}</p>
                        <p className="pw-figure text-xs text-muted-foreground">
                            {leg.meta}
                        </p>
                    </div>
                ))}
            </div>
        </figure>
    );
}
