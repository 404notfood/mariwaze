import { Head } from '@inertiajs/react';
import DashboardView from '@/components/mariwaze/dashboard-view';
import type { DashboardData } from '@/components/mariwaze/dashboard-view';
import { appRoutes } from '@/lib/mariwaze-routes';

type Props = {
    dashboardData: DashboardData;
};

export default function Dashboard({ dashboardData }: Props) {
    return (
        <>
            <Head title="Tableau de bord" />
            <DashboardView data={dashboardData} />
        </>
    );
}

Dashboard.layout = () => ({
    breadcrumbs: [
        {
            title: 'Accueil',
            href: appRoutes.dashboard,
        },
    ],
});
