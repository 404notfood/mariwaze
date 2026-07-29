import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, Ship } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { appRoutes } from '@/lib/mariwaze-routes';

type CruiseForm = {
    title: string; departure_port: string; destination_port: string; departure_date: string;
    duration_days: number; auto_duration: boolean; crew_count: number;
    navigation_style: 'Rapide' | 'Confort' | 'Découverte' | 'Famille';
};
type ExistingCruise = Omit<CruiseForm, 'auto_duration'> & { slug: string };
type Prefill = { departure_port?: string; destination_port?: string };

export default function CreateCruise({ cruise, prefill }: { cruise?: ExistingCruise; prefill?: Prefill }) {
    const { data, setData, post, put, processing, errors } = useForm<CruiseForm>({
        title: cruise?.title ?? '', departure_port: cruise?.departure_port ?? prefill?.departure_port ?? '', destination_port: cruise?.destination_port ?? prefill?.destination_port ?? '',
        departure_date: cruise?.departure_date ?? '', duration_days: cruise?.duration_days ?? 7, auto_duration: cruise === undefined,
        crew_count: cruise?.crew_count ?? 2, navigation_style: cruise?.navigation_style ?? 'Confort',
    });
    const editing = cruise !== undefined;
    const [calculationError, setCalculationError] = useState(false);

    useEffect(() => {
        if (!data.auto_duration || data.departure_port.trim() === '' || data.destination_port.trim() === '') {
            return;
        }

        const controller = new AbortController();
        const query = new URLSearchParams({
            departure_port: data.departure_port,
            destination_port: data.destination_port,
            navigation_style: data.navigation_style,
        });

        const timer = window.setTimeout(() => {
            fetch(`/api/cruises/duration?${query.toString()}`, { headers: { Accept: 'application/json' }, signal: controller.signal })
                .then(async (response) => response.ok ? response.json() as Promise<{ days: number }> : Promise.reject())
                .then(({ days }) => {
                    setData('duration_days', days);
                    setCalculationError(false);
                })
                .catch((error: unknown) => {
                    if (error instanceof DOMException && error.name === 'AbortError') return;
                    setCalculationError(true);
                });
        }, 450);

        return () => { window.clearTimeout(timer); controller.abort(); };
    }, [data.auto_duration, data.departure_port, data.destination_port, data.navigation_style, setData]);

    return <>
        <Head title={editing ? 'Modifier la croisière' : 'Nouvelle croisière'} />
        <main className="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-6 p-4 md:p-6">
            <div className="flex items-start gap-3">
                <Button asChild variant="ghost" size="icon" aria-label="Retour aux croisières"><Link href="/croisieres"><ArrowLeft className="size-4" /></Link></Button>
                <div><h1 className="text-2xl font-bold md:text-3xl">{editing ? 'Modifier la croisière' : 'Nouvelle croisière'}</h1><p className="mt-1 text-sm text-muted-foreground">Départ, arrivée, durée et style de navigation.</p></div>
            </div>
            <form className="grid gap-6" onSubmit={(event) => { event.preventDefault(); editing ? put(`/croisieres/${cruise.slug}`) : post('/croisieres'); }}>
                <section className="grid gap-4 border-y border-border/60 py-6 sm:grid-cols-2">
                    <Field label="Nom de la croisière" error={errors.title} className="sm:col-span-2"><Input value={data.title} onChange={(event) => setData('title', event.target.value)} autoFocus /></Field>
                    <Field label="Port de départ" error={errors.departure_port}><Input value={data.departure_port} onChange={(event) => setData('departure_port', event.target.value)} placeholder="Saint-Malo" /></Field>
                    <Field label="Destination" error={errors.destination_port}><Input value={data.destination_port} onChange={(event) => setData('destination_port', event.target.value)} placeholder="Québec" /></Field>
                    <Field label="Date de départ" error={errors.departure_date}><Input type="date" value={data.departure_date} onChange={(event) => setData('departure_date', event.target.value)} /></Field>
                    <Field label="Durée (jours)" error={errors.duration_days}>
                        <Input type="number" min="1" max="180" disabled={data.auto_duration} value={data.duration_days} onChange={(event) => setData('duration_days', Number(event.target.value))} />
                        <label className="mt-2 flex items-center gap-2 text-sm text-muted-foreground"><input type="checkbox" checked={data.auto_duration} onChange={(event) => setData('auto_duration', event.target.checked)} />Calculer automatiquement la durée</label>
                        {data.auto_duration && !calculationError && data.departure_port && data.destination_port && <p className="mt-2 text-sm text-muted-foreground">Estimation selon le style choisi : {data.duration_days} jours.</p>}
                        {data.auto_duration && calculationError && <p className="mt-2 text-sm text-destructive">Le calcul de durée est momentanément indisponible.</p>}
                    </Field>
                    <Field label="Équipage" error={errors.crew_count}><Input type="number" min="1" max="20" value={data.crew_count} onChange={(event) => setData('crew_count', Number(event.target.value))} /></Field>
                    <Field label="Style de navigation" error={errors.navigation_style}><select value={data.navigation_style} onChange={(event) => setData('navigation_style', event.target.value as CruiseForm['navigation_style'])} className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"><option value="Confort">Confort</option><option value="Découverte">Découverte</option><option value="Famille">Famille</option><option value="Rapide">Rapide</option></select></Field>
                </section>
                <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"><Button asChild variant="outline"><Link href="/croisieres">Annuler</Link></Button><Button type="submit" className="bg-sea-blue hover:bg-sea-blue/90" disabled={processing}><Ship className="size-4" />{editing ? 'Enregistrer les modifications' : 'Créer la croisière'}<ArrowRight className="size-4" /></Button></div>
            </form>
        </main>
    </>;
}

function Field({ label, error, className, children }: { label: string; error?: string; className?: string; children: React.ReactNode }) { return <div className={className}><Label className="mb-2 block">{label}</Label>{children}{error && <p className="mt-1 text-xs text-destructive">{error}</p>}</div>; }

CreateCruise.layout = () => ({ breadcrumbs: [{ title: 'Mes croisières', href: appRoutes.cruises }, { title: 'Nouvelle croisière', href: '/croisieres/nouvelle' }] });
