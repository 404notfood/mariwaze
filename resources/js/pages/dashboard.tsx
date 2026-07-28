import { Head } from '@inertiajs/react';
import DashboardView from '@/components/mariwaze/dashboard-view';
import { mariwazeRoutesForPreview } from '@/lib/mariwaze-routes';

type Props = {
    dashboardData: {
        boat: { name: string; model: string | null; type: string; draft: number; mast_height: number | null } | null;
        cruise: { slug: string; title: string; departure_port: string; destination_port: string; departure_date: string; duration_days: number; recommended_duration_days: number; duration_warning: boolean; route_geometry: Array<{ latitude: number; longitude: number }>; route_available: boolean; crew_count: number; style: string; status: string } | null;
    };
    preview?: boolean;
};

export default function Dashboard({ dashboardData, preview = false }: Props) {
    return (
        <>
            <Head title="Tableau de bord" />
            <DashboardView data={dashboardData} preview={preview} />
        </>
    );
}

Dashboard.layout = (props: Props) => ({
    breadcrumbs: [
        {
            title: 'Accueil',
            href: mariwazeRoutesForPreview(!!props.preview).dashboard,
        },
    ],
});
