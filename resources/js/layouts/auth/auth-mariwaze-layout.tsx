import { Link } from '@inertiajs/react';
import MariwazeLogo from '@/components/mariwaze/mariwaze-logo';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthMariwazeLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="grid min-h-svh lg:grid-cols-2">
            <aside className="relative hidden overflow-hidden lg:flex lg:flex-col lg:justify-between">
                <div className="absolute inset-0 mariwaze-gradient-ocean" />
                <div className="absolute inset-0 mariwaze-pattern-map opacity-80" />
                <svg
                    className="absolute inset-0 h-full w-full opacity-20"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <defs>
                        <pattern
                            id="grid"
                            width="40"
                            height="40"
                            patternUnits="userSpaceOnUse"
                        >
                            <path
                                d="M 40 0 L 0 0 0 40"
                                fill="none"
                                stroke="white"
                                strokeWidth="0.5"
                            />
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#grid)" />
                </svg>

                <div className="relative z-10 p-10">
                    <MariwazeLogo href={home().url} variant="light" showTagline />
                </div>

                <div className="relative z-10 space-y-6 p-10">
                    <blockquote className="max-w-md space-y-4">
                        <p className="text-2xl leading-snug font-semibold text-white">
                            « Je prépare une belle aventure en mer, avec un copilote fiable. »
                        </p>
                        <footer className="text-sm text-white/70">
                            L&apos;expérience MariWaze — carnet de navigation intelligent
                        </footer>
                    </blockquote>

                    <div className="flex gap-6 text-sm text-white/80">
                        <FeaturePill icon="🗺️" label="Itinéraires" />
                        <FeaturePill icon="🌊" label="Météo marine" />
                        <FeaturePill icon="🛟" label="Sécurité" />
                    </div>
                </div>

                <div className="relative z-10 p-10">
                    <DecorativeRoute />
                </div>
            </aside>

            <main className="flex flex-col items-center justify-center bg-foam p-6 md:p-10">
                <div className="w-full max-w-sm">
                    <div className="mb-8 flex flex-col items-center gap-4 lg:hidden">
                        <MariwazeLogo href={home().url} showTagline />
                    </div>

                    <div className="mb-8 space-y-2 text-center lg:text-left">
                        <h1 className="text-2xl font-bold tracking-tight text-ocean-navy">
                            {title}
                        </h1>
                        <p className="text-sm text-muted-foreground">{description}</p>
                    </div>

                    <div className="rounded-2xl border border-border/60 bg-white p-6 shadow-sm">
                        {children}
                    </div>
                </div>
            </main>
        </div>
    );
}

function FeaturePill({ icon, label }: { icon: string; label: string }) {
    return (
        <span className="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1.5 backdrop-blur-sm">
            <span>{icon}</span>
            <span>{label}</span>
        </span>
    );
}

function DecorativeRoute() {
    return (
        <svg viewBox="0 0 400 120" className="h-28 w-full text-white/40">
            <path
                d="M0 80 Q80 40 160 70 T320 50"
                fill="none"
                stroke="currentColor"
                strokeWidth="2"
                strokeDasharray="6 4"
            />
            <circle cx="0" cy="80" r="5" fill="currentColor" />
            <circle cx="160" cy="70" r="4" fill="currentColor" opacity="0.7" />
            <circle cx="320" cy="50" r="5" fill="currentColor" />
        </svg>
    );
}
