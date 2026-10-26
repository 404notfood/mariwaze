import { Link, usePage } from '@inertiajs/react';
import Anchor from 'lucide-react/icons/anchor';
import Compass from 'lucide-react/icons/compass';
import LayoutGrid from 'lucide-react/icons/layout-grid';
import Map from 'lucide-react/icons/map';
import Settings2 from 'lucide-react/icons/settings-2';
import Shield from 'lucide-react/icons/shield';
import Ship from 'lucide-react/icons/ship';
import AppLogo from '@/components/app-logo';
import ThemeToggle from '@/components/mariwaze/theme-toggle';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useMariwazeRoutes } from '@/hooks/use-mariwaze-routes';
import type { Auth, NavItem } from '@/types';

type PageProps = {
    auth: Auth;
};

export function AppSidebar() {
    const routes = useMariwazeRoutes();
    const { auth } = usePage<PageProps>().props;

    const mainNavItems: NavItem[] = [
        { title: 'Accueil', href: routes.dashboard, icon: LayoutGrid },
        {
            title: 'Mes croisières',
            href: routes.cruises,
            icon: Map,
            matchPrefix: true,
        },
        { title: 'Carte marine', href: routes.map, icon: Compass },
        {
            title: 'Explorer',
            href: routes.explore,
            icon: Anchor,
            matchPrefix: true,
        },
        {
            title: 'Mon bateau',
            href: routes.boat,
            icon: Ship,
            matchPrefix: true,
        },
        { title: 'Sécurité', href: routes.security, icon: Shield },
        ...(auth.can_admin
            ? [
                  {
                      title: 'Admin',
                      href: routes.adminHome,
                      icon: Settings2,
                      matchPrefix: true,
                  },
              ]
            : []),
    ];

    const footerNavItems: NavItem[] = [
        { title: 'Configurer bateau', href: routes.boatSetup, icon: Ship },
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={routes.dashboard} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <div className="flex items-center justify-between px-2 pb-2">
                    <ThemeToggle />
                </div>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
