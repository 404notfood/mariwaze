import { Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useMainNavItems } from '@/components/app-sidebar';
import { DesignSwitcher } from '@/components/mariwaze/design-switcher';
import MariwazeLogoIcon from '@/components/mariwaze/mariwaze-logo-icon';
import ThemeToggle from '@/components/mariwaze/theme-toggle';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { UserMenuContent } from '@/components/user-menu-content';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { useInitials } from '@/hooks/use-initials';
import { useMariwazeRoutes } from '@/hooks/use-mariwaze-routes';
import { cn } from '@/lib/utils';

/** Écrans « carte d'abord » : la carte occupe tout l'écran, l'interface flotte dessus. */
const FULL_BLEED_PAGES = ['dashboard', 'explore'];

/** Écrans qui posent leurs propres cartes sur le fond (pas de grande feuille autour). */
const OWN_SURFACE_PAGES = ['cruises/index', 'boat-show'];

/**
 * Coque du design « Au large » : barre de navigation flottante en pilules,
 * contenu posé sur une grande feuille arrondie (ou directement sur la carte).
 */
export default function AppLargeLayout({ children }: { children: ReactNode }) {
    const { component } = usePage();
    const fullBleed = FULL_BLEED_PAGES.includes(component);
    const ownSurface = OWN_SURFACE_PAGES.includes(component);

    return (
        <div className="relative min-h-svh bg-background">
            <LargeTopBar />
            {fullBleed ? (
                children
            ) : ownSurface ? (
                <main className="px-4 pt-[84px] pb-8 lg:px-8">{children}</main>
            ) : (
                <main className="px-4 pt-[84px] pb-6 lg:px-5">
                    <div className="overflow-hidden rounded-[24px] bg-card shadow-[var(--soft-shadow)]">
                        {children}
                    </div>
                </main>
            )}
        </div>
    );
}

function LargeTopBar() {
    const routes = useMariwazeRoutes();
    const items = useMainNavItems();
    const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();
    const { auth } = usePage().props;
    const getInitials = useInitials();

    const pill =
        'flex h-12 items-center rounded-full bg-card shadow-[var(--soft-shadow)]';

    return (
        <header className="pointer-events-none fixed inset-x-0 top-0 z-[1000] flex items-center gap-2 px-4 pt-4 lg:gap-3 lg:px-5 lg:pt-5">
            <Link
                href={routes.dashboard}
                className={cn(
                    pill,
                    'pointer-events-auto shrink-0 gap-2.5 pr-4 pl-2 font-bold',
                )}
            >
                <span className="flex size-8 items-center justify-center rounded-full bg-[var(--hero-bg)] text-[var(--hero-accent)]">
                    <MariwazeLogoIcon className="size-[18px]" />
                </span>
                <span className="hidden sm:inline">MariWaze</span>
            </Link>

            <nav
                aria-label="Navigation principale"
                className={cn(
                    pill,
                    'pointer-events-auto min-w-0 gap-0.5 overflow-x-auto p-1',
                )}
            >
                {items.map((item) => {
                    const active =
                        item.isActive ??
                        (item.matchPrefix
                            ? isCurrentOrParentUrl(item.href)
                            : isCurrentUrl(item.href));

                    return (
                        <Link
                            key={item.title}
                            href={item.href}
                            prefetch
                            aria-current={active ? 'page' : undefined}
                            className={cn(
                                'flex h-10 shrink-0 items-center rounded-full px-4 text-sm font-medium whitespace-nowrap transition-colors',
                                active
                                    ? 'bg-accent font-semibold text-foreground'
                                    : 'text-muted-foreground hover:text-foreground',
                            )}
                        >
                            {item.title}
                        </Link>
                    );
                })}
            </nav>

            <div className="flex-1" />

            <div
                className={cn(
                    pill,
                    'pointer-events-auto hidden shrink-0 gap-1 px-1.5 md:flex',
                )}
            >
                <ThemeToggle />
                <DesignSwitcher className="min-w-[170px] border-0" />
            </div>

            {auth.user && (
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <button
                            type="button"
                            className={cn(
                                pill,
                                'pointer-events-auto size-12 shrink-0 justify-center',
                            )}
                            aria-label="Menu du compte"
                        >
                            <Avatar className="size-9">
                                <AvatarImage
                                    src={auth.user.avatar}
                                    alt={auth.user.name}
                                />
                                <AvatarFallback className="bg-accent text-sm font-semibold text-foreground">
                                    {getInitials(auth.user.name)}
                                </AvatarFallback>
                            </Avatar>
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        className="w-60 rounded-2xl"
                        align="end"
                    >
                        <UserMenuContent user={auth.user} />
                    </DropdownMenuContent>
                </DropdownMenu>
            )}
        </header>
    );
}
