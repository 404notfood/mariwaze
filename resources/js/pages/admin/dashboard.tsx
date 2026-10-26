import { Head, Link } from '@inertiajs/react';
import ArrowRight from 'lucide-react/icons/arrow-right';
import Building2 from 'lucide-react/icons/building-2';
import CreditCard from 'lucide-react/icons/credit-card';
import Map from 'lucide-react/icons/map';
import Settings2 from 'lucide-react/icons/settings-2';
import Ship from 'lucide-react/icons/ship';
import Users from 'lucide-react/icons/users';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type Props = {
    stats: {
        users: number;
        organizations: number;
        boats: number;
        cruises: number;
    };
    catalog: {
        plans: number;
        active_plans: number;
        features: number;
        billable_options: number;
    };
    recentUsers: Array<{
        id: number;
        name: string;
        email: string;
        role: 'user' | 'admin';
        created_at: string;
    }>;
};

const numberFormatter = new Intl.NumberFormat('fr-FR');
const dateFormatter = new Intl.DateTimeFormat('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
});

export default function AdminDashboard({ stats, catalog, recentUsers }: Props) {
    const metrics = [
        { label: 'Utilisateurs', value: stats.users, icon: Users },
        { label: 'Organisations', value: stats.organizations, icon: Building2 },
        { label: 'Bateaux', value: stats.boats, icon: Ship },
        { label: 'Croisières', value: stats.cruises, icon: Map },
    ];

    return (
        <>
            <Head title="Admin - Vue d'ensemble" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <Heading
                    title="Vue d'ensemble"
                    description="Suivez l'activité de MariWaze et gérez les accès et le catalogue commercial."
                />

                <section className="grid gap-px overflow-hidden rounded-lg border bg-border sm:grid-cols-2 xl:grid-cols-4">
                    {metrics.map((metric) => {
                        const Icon = metric.icon;

                        return (
                            <div key={metric.label} className="bg-card p-5">
                                <div className="flex items-center justify-between gap-3">
                                    <p className="text-sm text-muted-foreground">
                                        {metric.label}
                                    </p>
                                    <Icon className="size-4 text-muted-foreground" />
                                </div>
                                <p className="mt-3 text-2xl font-semibold tabular-nums">
                                    {numberFormatter.format(metric.value)}
                                </p>
                            </div>
                        );
                    })}
                </section>

                <div className="grid gap-6 xl:grid-cols-[minmax(0,1.4fr)_minmax(320px,0.6fr)]">
                    <section className="overflow-hidden rounded-lg border bg-card">
                        <div className="flex items-center justify-between gap-4 border-b px-5 py-4">
                            <div>
                                <h2 className="font-semibold">
                                    Dernières inscriptions
                                </h2>
                                <p className="text-sm text-muted-foreground">
                                    Les comptes créés le plus récemment.
                                </p>
                            </div>
                            <Button variant="outline" size="sm" asChild>
                                <Link href="/admin/utilisateurs">
                                    Voir tout
                                    <ArrowRight className="size-4" />
                                </Link>
                            </Button>
                        </div>

                        <div className="divide-y">
                            {recentUsers.map((user) => (
                                <div
                                    key={user.id}
                                    className="flex items-center justify-between gap-4 px-5 py-3"
                                >
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-medium">
                                            {user.name}
                                        </p>
                                        <p className="truncate text-xs text-muted-foreground">
                                            {user.email}
                                        </p>
                                    </div>
                                    <div className="shrink-0 text-right">
                                        {user.role === 'admin' ? (
                                            <Badge variant="secondary">
                                                Admin
                                            </Badge>
                                        ) : (
                                            <span className="text-xs text-muted-foreground">
                                                {dateFormatter.format(
                                                    new Date(user.created_at),
                                                )}
                                            </span>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </section>

                    <section className="rounded-lg border bg-card p-5">
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <h2 className="font-semibold">Catalogue</h2>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Offres et modules commercialisés.
                                </p>
                            </div>
                            <CreditCard className="size-5 text-muted-foreground" />
                        </div>

                        <dl className="mt-5 divide-y border-y">
                            <CatalogRow
                                label="Offres actives"
                                value={`${catalog.active_plans}/${catalog.plans}`}
                            />
                            <CatalogRow
                                label="Fonctionnalités"
                                value={String(catalog.features)}
                            />
                            <CatalogRow
                                label="Options payantes"
                                value={String(catalog.billable_options)}
                            />
                        </dl>

                        <Button className="mt-5 w-full" asChild>
                            <Link href="/admin/tarifs">
                                <Settings2 className="size-4" />
                                Gérer le catalogue
                            </Link>
                        </Button>
                    </section>
                </div>
            </div>
        </>
    );
}

AdminDashboard.layout = {
    breadcrumbs: [{ title: 'Admin', href: '/admin' }],
};

function CatalogRow({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex items-center justify-between gap-3 py-3 text-sm">
            <dt className="text-muted-foreground">{label}</dt>
            <dd className="font-semibold tabular-nums">{value}</dd>
        </div>
    );
}
