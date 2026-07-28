import { Head, Link } from '@inertiajs/react';
import { ArrowRight, Compass, MapPin, Route } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Location = { name: string; latitude: number; longitude: number };

export default function Explore({ locations }: { locations: Location[] }) {
    const [departure, setDeparture] = useState('');
    const [destination, setDestination] = useState('');
    const canContinue = departure.trim() !== '' && destination.trim() !== '' && departure.trim().toLocaleLowerCase() !== destination.trim().toLocaleLowerCase();

    return <>
        <Head title="Explorer" />
        <main className="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-8 p-4 md:p-6">
            <header>
                <div className="mb-2 flex items-center gap-2 text-sea-blue"><Compass className="size-5" /><span className="text-sm font-medium">Préparer un itinéraire</span></div>
                <h1 className="text-2xl font-bold md:text-3xl">Explorer les ports</h1>
                <p className="mt-2 text-muted-foreground">Saisissez deux ports pour démarrer une croisière. Les ports connus restent proposés en suggestion.</p>
            </header>

            <section className="grid gap-5 border-y border-border/60 py-6 md:grid-cols-[1fr_auto_1fr] md:items-end">
                <div>
                    <Label htmlFor="explorer-departure" className="mb-2 block">Départ</Label>
                    <Input id="explorer-departure" list="known-ports" value={departure} onChange={(event) => setDeparture(event.target.value)} placeholder="Ex. Saint-Malo" autoComplete="off" />
                </div>
                <Route className="mb-2 hidden size-5 text-muted-foreground md:block" />
                <div>
                    <Label htmlFor="explorer-destination" className="mb-2 block">Arrivée</Label>
                    <Input id="explorer-destination" list="known-ports" value={destination} onChange={(event) => setDestination(event.target.value)} placeholder="Ex. Marseille" autoComplete="off" />
                </div>
            </section>

            <datalist id="known-ports">{locations.map((location) => <option key={location.name} value={location.name} />)}</datalist>

            <div className="flex justify-end">
                {canContinue ? <Button asChild className="bg-sea-blue hover:bg-sea-blue/90"><Link href={`/croisieres/nouvelle?departure=${encodeURIComponent(departure.trim())}&destination=${encodeURIComponent(destination.trim())}`}>Créer la croisière<ArrowRight className="size-4" /></Link></Button> : <Button disabled>Créer la croisière<ArrowRight className="size-4" /></Button>}
            </div>

            <section className="grid gap-x-8 gap-y-3 border-t border-border/60 pt-6 sm:grid-cols-2 lg:grid-cols-3">
                {locations.map((location) => <div key={location.name} className="flex items-center gap-3 py-2"><MapPin className="size-4 shrink-0 text-sea-blue" /><div><p className="font-medium">{location.name}</p><p className="text-xs text-muted-foreground">{location.latitude.toFixed(3)}, {location.longitude.toFixed(3)}</p></div></div>)}
            </section>
        </main>
    </>;
}

Explore.layout = () => ({ breadcrumbs: [{ title: 'Explorer', href: '/explorer' }] });
