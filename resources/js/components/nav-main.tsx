import { Link } from '@inertiajs/react';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import type { NavItem } from '@/types';

export function NavMain({ items = [] }: { items: NavItem[] }) {
    const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();

    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarGroupLabel className="pw-label !text-sidebar-foreground/50">
                Navigation
            </SidebarGroupLabel>
            <SidebarMenu>
                {items.map((item) => (
                    <SidebarMenuItem key={item.title}>
                        <SidebarMenuButton
                            asChild
                            className="h-9 rounded-sm text-[15px] font-medium text-sidebar-foreground/75 hover:text-sidebar-foreground data-[active=true]:font-semibold data-[active=true]:text-sidebar-primary data-[active=true]:shadow-[inset_2px_0_0_var(--sidebar-primary)]"
                            isActive={
                                item.isActive ??
                                (item.matchPrefix
                                    ? isCurrentOrParentUrl(item.href)
                                    : isCurrentUrl(item.href))
                            }
                            tooltip={{ children: item.title }}
                        >
                            <Link href={item.href} prefetch>
                                {item.icon && <item.icon />}
                                <span>{item.title}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                ))}
            </SidebarMenu>
        </SidebarGroup>
    );
}
