import { router } from '@inertiajs/react';
import { useState } from 'react';

export type Checklist = {
    name: string;
    items: {
        id: number;
        section: string;
        label: string;
        quantity: string | null;
        required: boolean;
        checked: boolean;
    }[];
};

export default function CruiseChecklist({
    slug,
    checklists,
}: {
    slug: string;
    checklists: Checklist[];
}) {
    const [pending, setPending] = useState<{
        id: number;
        checked: boolean;
    } | null>(null);
    const [error, setError] = useState('');
    const items = checklists.flatMap((checklist) => checklist.items);

    return (
        <section
            className="rounded-xl border p-5"
            aria-labelledby="checklist-title"
        >
            <h2 id="checklist-title" className="text-lg font-semibold">
                Checklist de préparation
            </h2>
            <p role="status" className="mt-1 text-sm text-muted-foreground">
                {items.filter((item) => item.checked).length}/{items.length}{' '}
                éléments cochés{pending !== null ? ' · Enregistrement…' : ''}
            </p>
            {error && (
                <p role="alert" className="text-sm text-destructive">
                    {error}
                </p>
            )}
            {checklists.map((checklist) => (
                <div key={checklist.name} className="mt-4">
                    <h3 className="font-medium">{checklist.name}</h3>
                    {checklist.items.map((item) => (
                        <label
                            key={item.id}
                            className="flex items-start gap-3 border-b py-3 text-sm"
                        >
                            <input
                                type="checkbox"
                                className="mt-1 size-5 shrink-0"
                                checked={
                                    pending?.id === item.id
                                        ? pending.checked
                                        : item.checked
                                }
                                disabled={pending !== null}
                                onChange={(event) => {
                                    setPending({
                                        id: item.id,
                                        checked: event.target.checked,
                                    });
                                    setError('');
                                    router.patch(
                                        `/croisieres/${slug}/checklist/${item.id}`,
                                        { checked: event.target.checked },
                                        {
                                            preserveScroll: true,
                                            onError: () =>
                                                setError(
                                                    'Impossible d’enregistrer cette coche. Réessayez.',
                                                ),
                                            onFinish: () => setPending(null),
                                        },
                                    );
                                }}
                            />
                            <span>
                                {item.label}
                                {item.quantity ? ` — ${item.quantity}` : ''}
                                {item.required ? ' (requis)' : ''}
                                <span className="block text-xs text-muted-foreground">
                                    {item.section}
                                </span>
                            </span>
                        </label>
                    ))}
                </div>
            ))}
            {items.length === 0 && (
                <p className="mt-3 text-sm text-muted-foreground">
                    Aucune checklist enregistrée pour cette croisière.
                </p>
            )}
        </section>
    );
}
