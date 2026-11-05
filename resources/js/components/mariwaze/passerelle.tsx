import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

/** Petits éléments récurrents du design system Passerelle. */

export type Tone = 'good' | 'warn' | 'bad' | 'muted' | 'accent';

export const toneText: Record<Tone, string> = {
    good: 'text-good',
    warn: 'text-warn',
    bad: 'text-bad',
    muted: 'text-muted-foreground',
    accent: 'text-primary',
};

/** Pastille à chasse fixe, bordée. `accent` pour la valeur mise en avant. */
export function Chip({
    children,
    accent = false,
    className,
}: {
    children: ReactNode;
    accent?: boolean;
    className?: string;
}) {
    return (
        <span
            className={cn(
                'inline-block rounded-sm border px-1.5 py-px font-mono text-[11px] tracking-[0.04em] uppercase',
                accent
                    ? 'border-primary text-primary'
                    : 'border-line-strong text-foreground',
                className,
            )}
        >
            {children}
        </span>
    );
}

/** Avis écrit en mot, coloré en renfort. */
export function Status({
    tone,
    children,
}: {
    tone: Tone;
    children: ReactNode;
}) {
    return <span className={cn('pw-status', toneText[tone])}>{children}</span>;
}

/** Jauge en 20 segments : la valeur reste toujours lisible à côté. */
export function SegmentBar({
    value,
    className,
}: {
    value: number;
    className?: string;
}) {
    const filled = Math.round(Math.max(0, Math.min(100, value)) / 5);

    return (
        <div
            className={cn('grid h-3 flex-1 grid-cols-20 gap-0.5', className)}
            role="img"
            aria-label={`${value} %`}
        >
            {Array.from({ length: 20 }, (_, i) => (
                <i
                    key={i}
                    className={cn(
                        'border',
                        i < filled
                            ? 'border-primary bg-primary'
                            : 'border-border bg-sunken',
                    )}
                />
            ))}
        </div>
    );
}

/** En-tête de page : libellé d'instrument, titre en capitales, actions à droite. */
export function PageHeader({
    label,
    title,
    children,
    actions,
}: {
    label: string;
    title: ReactNode;
    children?: ReactNode;
    actions?: ReactNode;
}) {
    return (
        <header className="flex flex-col gap-3 border-b border-border px-5 py-4 md:flex-row md:items-end md:justify-between">
            <div>
                <p className="pw-label">{label}</p>
                <h1 className="text-[24px] leading-7 font-semibold tracking-[0.02em] uppercase">
                    {title}
                </h1>
                {children}
            </div>
            {actions && <div className="flex gap-2">{actions}</div>}
        </header>
    );
}

/** Valeur d'instrument avec son libellé et son unité. */
export function Instrument({
    label,
    value,
    unit,
    className,
}: {
    label: string;
    value: ReactNode;
    unit?: string;
    className?: string;
}) {
    return (
        <div className={cn('px-5 py-3', className)}>
            <p className="pw-label">{label}</p>
            <p className="pw-figure text-lg leading-6 whitespace-nowrap">
                {value}
                {unit && (
                    <small className="ml-1 text-xs text-muted-foreground">
                        {unit}
                    </small>
                )}
            </p>
        </div>
    );
}
