import { Link } from '@inertiajs/react';
import MariwazeLogoIcon from '@/components/mariwaze/mariwaze-logo-icon';
import { cn } from '@/lib/utils';

type Props = {
    className?: string;
    showTagline?: boolean;
    href?: string;
    variant?: 'light' | 'dark';
};

export default function MariwazeLogo({
    className,
    showTagline = false,
    href,
    variant = 'dark',
}: Props) {
    const content = (
        <>
            <div
                className={cn(
                    'flex size-10 items-center justify-center rounded-xl',
                    variant === 'light'
                        ? 'bg-white/15 text-white'
                        : 'bg-ocean-navy text-lagoon',
                )}
            >
                <MariwazeLogoIcon className="size-6" />
            </div>
            <div className="grid flex-1 text-left leading-tight">
                <span
                    className={cn(
                        'truncate text-base font-bold tracking-tight',
                        variant === 'light' ? 'text-white' : 'text-ocean-navy',
                    )}
                >
                    MariWaze
                </span>
                {showTagline && (
                    <span
                        className={cn(
                            'truncate text-xs',
                            variant === 'light'
                                ? 'text-white/70'
                                : 'text-muted-foreground',
                        )}
                    >
                        Copilote maritime
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
