import { Link } from '@inertiajs/react';
import MariwazeLogoIcon from '@/components/mariwaze/mariwaze-logo-icon';
import { cn } from '@/lib/utils';

type Props = {
    className?: string;
    showTagline?: boolean;
    href?: string;
    /** `light` : posé sur un fond sombre fixe (bandeau, pied de page). */
    variant?: 'light' | 'dark';
};

export default function MariwazeLogo({
    className,
    showTagline = false,
    href,
    variant = 'dark',
}: Props) {
    const onDark = variant === 'light';

    const content = (
        <>
            <div
                className={cn(
                    'flex size-9 items-center justify-center rounded-sm border-[1.5px]',
                    onDark
                        ? 'border-[var(--hero-accent)] text-[var(--hero-accent)]'
                        : 'border-primary text-primary',
                )}
            >
                <MariwazeLogoIcon className="size-5" />
            </div>
            <div className="grid flex-1 text-left leading-tight">
                <span
                    className={cn(
                        'truncate text-lg font-bold tracking-[0.08em] uppercase',
                        onDark ? 'text-[var(--hero-fg)]' : 'text-foreground',
                    )}
                >
                    MariWaze
                </span>
                {showTagline && (
                    <span
                        className={cn(
                            'pw-label truncate',
                            onDark && '!text-[var(--hero-muted)]',
                        )}
                    >
                        Copilote de croisière
                    </span>
                )}
            </div>
        </>
    );

    if (href) {
        return (
            <Link
                href={href}
                className={cn('flex items-center gap-3', className)}
            >
                {content}
            </Link>
        );
    }

    return (
        <div className={cn('flex items-center gap-3', className)}>
            {content}
        </div>
    );
}
