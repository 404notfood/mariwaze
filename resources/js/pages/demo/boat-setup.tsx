import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowRight, Save, Ship } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { mariwazeRoutesForPreview } from '@/lib/mariwaze-routes';
import { useMariwazeRoutes } from '@/hooks/use-mariwaze-routes';

type BoatType = { id: string; label: string; description: string };
type Boat = {
    name: string; model: string; type: string; flag: string; mmsi: string;
    length: number; width: number; draft: number; mast_height: number;
    avg_speed: number; fuel_range: number; types: BoatType[];
};
type CatalogBoat = {
    model: string; boat_type: string; length: number; width: number; draft: number;
    mast_height: number; avg_speed: number; fuel_range: number;
};
type BoatForm = {
    name: string; model: string; boat_type: string; flag: string; mmsi: string;
    length: number; width: number; draft: number; mast_height: number;
    avg_speed: number; fuel_range: number;
};
type Props = { boat: Boat; catalog: CatalogBoat[]; preview?: boolean };

export default function DemoBoatSetup({ boat, catalog, preview = false }: Props) {
    const routes = useMariwazeRoutes(preview);
    const { data, setData, post, processing, errors } = useForm<BoatForm>({
        name: boat.name,
        model: boat.model,
        boat_type: boat.type,
        flag: boat.flag,
        mmsi: boat.mmsi,
        length: boat.length,
        width: boat.width,
        draft: boat.draft,
        mast_height: boat.mast_height,
        avg_speed: boat.avg_speed,
        fuel_range: boat.fuel_range,
    });

    const applyModel = (model: string) => {
        const suggestion = catalog.find((item) => item.model === model);

        if (suggestion === undefined) {
            setData('model', model);
            return;
        }

        setData({
            ...data,
            model: suggestion.model,
            boat_type: suggestion.boat_type,
            length: suggestion.length,
            width: suggestion.width,
            draft: suggestion.draft,
            mast_height: suggestion.mast_height,
            avg_speed: suggestion.avg_speed,
            fuel_range: suggestion.fuel_range,
        });
    };

    return (
        <>
            <Head title="Configurer mon bateau" />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold md:text-3xl">Configurer mon bateau</h1>
                    <p className="text-muted-foreground">Les dimensions du bateau déterminent les ports et mouillages accessibles.</p>
                </div>

                <form className="grid gap-6 lg:grid-cols-3" onSubmit={(event) => { event.preventDefault(); post('/bateau'); }}>
                    <div className="space-y-6 lg:col-span-2">
                        <Card className="border-border/60">
                            <CardHeader><CardTitle className="text-base">Identité du bateau</CardTitle></CardHeader>
                            <CardContent className="grid gap-4 sm:grid-cols-2">
                                <Field label="Nom du bateau" error={errors.name} className="sm:col-span-2"><Input value={data.name} onChange={(event) => setData('name', event.target.value)} autoFocus /></Field>
                                <Field label="Modèle"><Input list="boat-models" value={data.model} onChange={(event) => applyModel(event.target.value)} placeholder="Dufour 390" /></Field>
                                <datalist id="boat-models">{catalog.map((item) => <option key={item.model} value={item.model} />)}</datalist>
                                <Field label="Pavillon" error={errors.flag}><Input value={data.flag} onChange={(event) => setData('flag', event.target.value)} /></Field>
                                <Field label="MMSI" error={errors.mmsi}><Input inputMode="numeric" maxLength={9} value={data.mmsi} onChange={(event) => setData('mmsi', event.target.value.replace(/\D/g, ''))} placeholder="9 chiffres" /></Field>
                            </CardContent>
                        </Card>

                        <Card className="border-border/60">
                            <CardHeader><CardTitle className="text-base">Type de bateau</CardTitle></CardHeader>
                            <CardContent className="grid gap-3 sm:grid-cols-2">
                                {boat.types.map((type) => (
                                    <label key={type.id} className="flex cursor-pointer gap-3 rounded-lg border border-border/60 p-4 has-[:checked]:border-sea-blue has-[:checked]:bg-sea-blue/5">
                                        <input type="radio" name="type" value={type.id} checked={data.boat_type === type.id} onChange={() => setData('boat_type', type.id)} />
                                        <span><strong className="block">{type.label}</strong><span className="text-xs text-muted-foreground">{type.description}</span></span>
                                    </label>
                                ))}
                            </CardContent>
                        </Card>

                        <Card className="border-border/60">
                            <CardHeader><CardTitle className="text-base">Dimensions et navigation</CardTitle></CardHeader>
                            <CardContent className="grid gap-4 sm:grid-cols-2">
                                <NumberField label="Longueur (m)" value={data.length} onChange={(value) => setData('length', value)} error={errors.length} />
                                <NumberField label="Largeur (m)" value={data.width} onChange={(value) => setData('width', value)} error={errors.width} />
                                <NumberField label="Tirant d’eau (m)" value={data.draft} onChange={(value) => setData('draft', value)} error={errors.draft} />
                                <NumberField label="Hauteur de mât (m)" value={data.mast_height} onChange={(value) => setData('mast_height', value)} error={errors.mast_height} />
                                <NumberField label="Vitesse moyenne (nds)" value={data.avg_speed} onChange={(value) => setData('avg_speed', value)} error={errors.avg_speed} />
                                <NumberField label="Autonomie carburant (M)" value={data.fuel_range} onChange={(value) => setData('fuel_range', value)} error={errors.fuel_range} step="1" />
                            </CardContent>
                        </Card>
                    </div>

                    <aside className="lg:sticky lg:top-4 lg:self-start">
                        <Card className="border-border/60">
                            <CardHeader><CardTitle className="text-base">Profil de navigation</CardTitle></CardHeader>
                            <CardContent className="space-y-4 text-sm">
                                <p><strong>{data.name || 'Votre bateau'}</strong>{data.model ? ` · ${data.model}` : ''}</p>
                                <p className="text-muted-foreground">Tirant d’eau: <strong>{data.draft || 0} m</strong></p>
                                <p className="text-muted-foreground">Hauteur de mât: <strong>{data.mast_height || 0} m</strong></p>
                                <p className="text-muted-foreground">Vitesse prévue: <strong>{data.avg_speed || 0} nds</strong></p>
                                <Button type="submit" className="w-full bg-sea-blue hover:bg-sea-blue/90" disabled={processing}><Save className="size-4" />Enregistrer le bateau</Button>
                                <Button asChild variant="outline" className="w-full"><Link href={routes.boat}><Ship className="size-4" />Voir le profil<ArrowRight className="size-4" /></Link></Button>
                            </CardContent>
                        </Card>
                    </aside>
                </form>
            </div>
        </>
    );
}

function Field({ label, error, className, children }: { label: string; error?: string; className?: string; children: React.ReactNode }) {
    return <div className={className}><Label className="mb-2 block">{label}</Label>{children}{error && <p className="mt-1 text-xs text-destructive">{error}</p>}</div>;
}

function NumberField({ label, value, onChange, error, step = '0.1' }: { label: string; value: number; onChange: (value: number) => void; error?: string; step?: string }) {
    return <Field label={label} error={error}><Input type="number" min="0" step={step} value={value} onChange={(event) => onChange(Number(event.target.value))} /></Field>;
}

DemoBoatSetup.layout = () => ({ breadcrumbs: [{ title: 'Mon bateau', href: mariwazeRoutesForPreview(false).boat }, { title: 'Configurer', href: '/bateau/configurer' }] });
