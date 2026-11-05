import MariwazeLogo from '@/components/mariwaze/mariwaze-logo';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

/** Écran de connexion Passerelle : panneau d'instruments à gauche, formulaire à droite. */
export default function AuthMariwazeLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="grid min-h-svh lg:grid-cols-2">
            <aside className="mariwaze-gradient-ocean relative hidden flex-col justify-between border-r border-[var(--hero-line)] p-10 lg:flex">
                <MariwazeLogo href={home().url} variant="light" showTagline />

                <div className="max-w-md">
                    <p className="font-mono text-[10px] tracking-[0.1em] text-[var(--hero-muted)] uppercase">
                        Copilote de croisière · préparation
                    </p>
                    <p className="mt-3 text-4xl leading-[1.02] font-bold tracking-[0.01em] text-[var(--hero-fg)] uppercase">
                        Savoir si l’on part.{' '}
                        <span className="text-[var(--hero-accent)]">
                            Étape par étape.
                        </span>
                    </p>
                </div>

                <dl className="grid grid-cols-3 border border-[var(--hero-line)]">
                    {[
                        ['Itinéraires', 'Par bateau'],
                        ['Météo', 'Par étape'],
                        ['Sécurité', 'Checklist'],
                    ].map(([label, value]) => (
                        <div
                            key={label}
                            className="border-r border-[var(--hero-line)] px-4 py-3 last:border-r-0"
                        >
                            <dt className="font-mono text-[10px] tracking-[0.1em] text-[var(--hero-muted)] uppercase">
                                {label}
                            </dt>
                            <dd className="mt-0.5 text-lg font-semibold text-[var(--hero-fg)] uppercase">
                                {value}
                            </dd>
                        </div>
                    ))}
                </dl>
            </aside>

            <main className="flex flex-col items-center justify-center bg-background p-6 md:p-10">
                <div className="w-full max-w-sm">
                    <div className="mb-8 flex flex-col items-center gap-4 lg:hidden">
                        <MariwazeLogo href={home().url} showTagline />
                    </div>

                    <div className="mb-6 space-y-1 text-center lg:text-left">
                        <h1 className="text-2xl font-semibold tracking-[0.02em] text-foreground uppercase">
                            {title}
                        </h1>
                        <p className="text-muted-foreground">{description}</p>
                    </div>

                    <div className="rounded-sm border border-border bg-raised p-6">
                        {children}
                    </div>
                </div>
            </main>
        </div>
    );
}
