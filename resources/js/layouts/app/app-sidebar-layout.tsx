import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { useDesign } from '@/hooks/use-design';
import AppLargeLayout from '@/layouts/app/app-large-layout';
import type { AppLayoutProps } from '@/types';

export default function AppSidebarLayout({
    children,
    breadcrumbs = [],
}: AppLayoutProps) {
    const { design } = useDesign();

    // « Au large » : barre flottante et carte en plein écran, sans barre latérale.
    if (design === 'large') {
        return <AppLargeLayout>{children}</AppLargeLayout>;
    }

    return (
        <AppShell variant="sidebar">
            <AppSidebar />
            <AppContent variant="sidebar" className="overflow-x-hidden">
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                {children}
            </AppContent>
        </AppShell>
    );
}
