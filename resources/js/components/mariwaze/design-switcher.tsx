import { designs, useDesign } from '@/hooks/use-design';
import type { Design } from '@/hooks/use-design';
import { cn } from '@/lib/utils';

/** Interrupteur compact Passerelle / Au large (barre latérale). */
export function DesignSwitcher({ className }: { className?: string }) {
    const { design, updateDesign } = useDesign();

    return (
        <div
            role="radiogroup"
            aria-label="Design de l’interface"
            className={cn(
                'grid flex-1 grid-cols-2 gap-0.5 rounded-md border border-sidebar-border p-0.5',
                className,
            )}
        >
            {designs.map((item) => (
                <button
                    key={item.value}
                    type="button"
                    role="radio"
                    aria-checked={design === item.value}
                    title={item.description}
                    onClick={() => updateDesign(item.value)}
                    className={cn(
                        'pw-label truncate rounded-sm px-2 py-1 transition-colors',
                        design === item.value
                            ? 'bg-sidebar-accent !text-sidebar-primary'
                            : '!text-sidebar-foreground/60 hover:!text-sidebar-foreground',
                    )}
                >
                    {item.label}
                </button>
            ))}
        </div>
    );
}

/** Aperçu miniature de chaque design, aux couleurs fixes du design concerné. */
const previews: Record<
    Design,
    {
        bg: string;
        panel: string;
        ink: string;
        accent: string;
        route: string;
        radius: string;
    }
> = {
    passerelle: {
        bg: '#0a1118',
        panel: '#111b25',
        ink: '#e6edf3',
        accent: '#4cc9f0',
        route: '#ffb020',
        radius: '2px',
    },
    large: {
        bg: '#eef3f1',
        panel: '#ffffff',
        ink: '#10302f',
        accent: '#0f766e',
        route: '#c9432c',
        radius: '10px',
    },
};

/** Cartes de choix du design (page Apparence). */
export function DesignCards() {
    const { design, updateDesign } = useDesign();

    return (
        <div
            role="radiogroup"
            aria-label="Design de l’interface"
            className="grid gap-3 sm:grid-cols-2"
        >
            {designs.map((item) => {
                const p = previews[item.value];
                const active = design === item.value;

                return (
                    <button
                        key={item.value}
                        type="button"
                        role="radio"
                        aria-checked={active}
                        onClick={() => updateDesign(item.value)}
                        className={cn(
                            'overflow-hidden rounded-lg border-2 text-left transition-colors',
                            active
                                ? 'border-primary'
                                : 'border-border hover:border-line-strong',
                        )}
                    >
                        <svg
                            viewBox="0 0 240 110"
                            className="block h-auto w-full"
                            aria-hidden="true"
                        >
                            <rect width="240" height="110" fill={p.bg} />
                            <rect
                                x="12"
                                y="12"
                                width="80"
                                height="86"
                                rx={p.radius}
                                fill={p.panel}
                            />
                            <rect
                                x="22"
                                y="24"
                                width="44"
                                height="6"
                                rx="2"
                                fill={p.ink}
                                opacity="0.85"
                            />
                            <rect
                                x="22"
                                y="38"
                                width="60"
                                height="4"
                                rx="2"
                                fill={p.ink}
                                opacity="0.35"
                            />
                            <rect
                                x="22"
                                y="74"
                                width="60"
                                height="14"
                                rx={p.radius === '2px' ? '2' : '7'}
                                fill={p.accent}
                            />
                            <rect
                                x="104"
                                y="12"
                                width="124"
                                height="86"
                                rx={p.radius}
                                fill={p.panel}
                            />
                            <path
                                d="M118 80 C140 40 170 70 190 44 S220 30 220 26"
                                fill="none"
                                stroke={p.route}
                                strokeWidth="3"
                                strokeLinecap="round"
                            />
                            <circle cx="118" cy="80" r="4" fill={p.accent} />
                            <circle cx="220" cy="26" r="4" fill={p.accent} />
                        </svg>
                        <div className="px-4 py-3">
                            <p className="font-semibold">{item.label}</p>
                            <p className="text-sm text-muted-foreground">
                                {item.description}
                            </p>
                        </div>
                    </button>
                );
            })}
        </div>
    );
}
