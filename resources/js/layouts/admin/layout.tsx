import { Link, usePage } from '@inertiajs/react';
import { CreditCard, LayoutDashboard, Users } from 'lucide-react';
import type { PropsWithChildren } from 'react';
import { cn } from '@/lib/utils';

const adminNavigation = [
    { title: "Vue d'ensemble", href: '/admin', icon: LayoutDashboard },
    { title: 'Utilisateurs', href: '/admin/utilisateurs', icon: Users },
    { title: 'Tarifs et options', href: '/admin/tarifs', icon: CreditCard },
];

export default function AdminLayout({ children }: PropsWithChildren) {
    const { url } = usePage();

    return (
        <div className="flex min-h-0 flex-1 flex-col">
            <nav
                aria-label="Administration"
                className="overflow-x-auto border-b bg-background px-4 md:px-6"
            >
                <div className="flex w-max min-w-full gap-1 py-2">
                    {adminNavigation.map((item) => {
                        const active =
                            item.href === '/admin'
                                ? url === '/admin' || url === '/admin/'
                                : url.startsWith(item.href);
                        const Icon = item.icon;

                        return (
                            <Link
                                key={item.href}
                                href={item.href}
                                className={cn(
                                    'flex h-9 items-center gap-2 rounded-md px-3 text-sm font-medium transition-colors',
                                    active
                                        ? 'bg-accent text-accent-foreground'
                                        : 'text-muted-foreground hover:bg-accent/60 hover:text-foreground',
                                )}
                            >
                                <Icon className="size-4" />
                                {item.title}
                            </Link>
                        );
                    })}
                </div>
            </nav>
            {children}
        </div>
    );
}
