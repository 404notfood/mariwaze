import Crosshair from 'lucide-react/icons/crosshair';
import Fish from 'lucide-react/icons/fish';
import Leaf from 'lucide-react/icons/leaf';
import Ship from 'lucide-react/icons/ship';
import Skull from 'lucide-react/icons/skull';
import TriangleAlert from 'lucide-react/icons/triangle-alert';
import type { HazardAlert } from '@/components/mariwaze/planner-map';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

const categoryIcons: Record<string, typeof Ship> = {
    military: Crosshair,
    orca: Fish,
    piracy: Skull,
    traffic: Ship,
    nature: Leaf,
};

const severityStyles: Record<
    HazardAlert['severity'],
    { card: string; icon: string; badge: string; label: string }
> = {
    danger: {
        card: 'border-destructive/40 bg-destructive/5',
        icon: 'text-destructive',
        badge: 'bg-destructive/15 text-destructive',
        label: 'Danger',
    },
    warning: {
        card: 'border-safety-orange/40 bg-safety-orange/5',
        icon: 'text-safety-orange',
        badge: 'bg-safety-orange/15 text-safety-orange',
        label: 'Vigilance',
    },
    info: {
        card: 'border-lagoon/40 bg-lagoon/5',
        icon: 'text-sea-blue dark:text-lagoon',
        badge: 'bg-lagoon/15 text-sea-blue dark:text-lagoon',
        label: 'À connaître',
    },
};

export default function HazardAlerts({
    hazards,
    className,
}: {
    hazards: HazardAlert[];
    className?: string;
}) {
    if (hazards.length === 0) {
        return null;
    }

    return (
        <div className={cn('space-y-2.5', className)}>
            {hazards.map((zone) => {
                const style =
                    severityStyles[zone.severity] ?? severityStyles.info;
                const Icon = categoryIcons[zone.category] ?? TriangleAlert;

                return (
                    <div
                        key={zone.slug}
                        className={cn('rounded-xl border p-4', style.card)}
                    >
                        <div className="flex flex-wrap items-start justify-between gap-2">
                            <p className="flex items-center gap-2 font-semibold">
                                <Icon
                                    className={cn(
                                        'size-4 shrink-0',
                                        style.icon,
                                    )}
                                />
                                {zone.name}
                            </p>
                            <div className="flex flex-wrap items-center gap-1.5">
                                <Badge
                                    variant="secondary"
                                    className={style.badge}
                                >
                                    {style.label}
                                </Badge>
                                <Badge variant="secondary">
                                    {zone.category_label}
                                </Badge>
                                {!zone.in_season && (
                                    <Badge variant="outline">
                                        Hors saison principale
                                    </Badge>
                                )}
                            </div>
                        </div>
                        <p className="mt-1 text-xs font-medium text-muted-foreground">
                            {zone.on_route
                                ? 'Le trajet traverse cette zone.'
                                : `À environ ${zone.distance_nm} M du trajet.`}
                        </p>
                        <p className="mt-1.5 text-sm">{zone.description}</p>
                        {zone.advice && (
                            <p className="mt-1.5 text-sm text-muted-foreground">
                                <span className="font-medium text-foreground">
                                    Conseil :{' '}
                                </span>
                                {zone.advice}
                            </p>
                        )}
                    </div>
                );
            })}
        </div>
    );
}
