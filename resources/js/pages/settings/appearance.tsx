import { Head } from '@inertiajs/react';
import AppearanceTabs from '@/components/appearance-tabs';
import Heading from '@/components/heading';
import { DesignCards } from '@/components/mariwaze/design-switcher';
import { edit as editAppearance } from '@/routes/appearance';

export default function Appearance() {
    return (
        <>
            <Head title="Apparence" />

            <h1 className="sr-only">Apparence</h1>

            <div className="space-y-10">
                <section className="space-y-6">
                    <Heading
                        variant="small"
                        title="Design"
                        description="Choisissez l’allure de votre espace MariWaze. Le changement est immédiat et mémorisé sur cet appareil."
                    />
                    <DesignCards />
                </section>

                <section className="space-y-6">
                    <Heading
                        variant="small"
                        title="Mode jour / nuit"
                        description="Les deux designs existent en version jour et en version nuit."
                    />
                    <AppearanceTabs />
                </section>
            </div>
        </>
    );
}

Appearance.layout = {
    breadcrumbs: [
        {
            title: 'Apparence',
            href: editAppearance(),
        },
    ],
};
