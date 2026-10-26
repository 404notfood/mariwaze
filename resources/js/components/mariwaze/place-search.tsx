import { useEffect, useId, useRef, useState } from 'react';
import { Input } from '@/components/ui/input';

export type Place = {
    reference: string | null;
    name: string;
    region: string | null;
    country_code: string | null;
    latitude: number;
    longitude: number;
    type: string;
};

export default function PlaceSearch({
    label,
    value,
    onChange,
    onSelect,
    id: inputId,
}: {
    id?: string;
    label: string;
    value: string;
    onChange: (value: string) => void;
    onSelect: (place: Place) => void;
}) {
    const id = useId();
    const [results, setResults] = useState<Place[]>([]);
    const [open, setOpen] = useState(false);
    const [status, setStatus] = useState('');
    const [active, setActive] = useState(-1);
    const list = useRef<HTMLUListElement>(null);
    const expanded = open && results.length > 0;

    useEffect(() => {
        if (expanded && active >= 0) {
            list.current
                ?.querySelector('[aria-selected="true"]')
                ?.scrollIntoView?.({ block: 'nearest' });
        }
    }, [active, expanded]);

    useEffect(() => {
        if (!open || value.trim().length < 2) {
            return;
        }

        const controller = new AbortController();
        const timer = window.setTimeout(async () => {
            setStatus('Recherche en cours…');

            try {
                const response = await fetch(
                    `/api/places?q=${encodeURIComponent(value.trim())}`,
                    {
                        signal: controller.signal,
                        headers: { Accept: 'application/json' },
                    },
                );

                if (!response.ok) {
                    throw new Error();
                }

                const data = (await response.json()) as { places: Place[] };

                if (controller.signal.aborted) {
                    return;
                }

                setResults(data.places);
                setActive(-1);
                setStatus(
                    data.places.length
                        ? `${data.places.length} lieux proposés.`
                        : 'Aucun lieu trouvé. Essayez un autre nom.',
                );
            } catch {
                if (!controller.signal.aborted) {
                    setResults([]);
                    setStatus(
                        'Recherche indisponible. Réessayez en modifiant le nom.',
                    );
                }
            }
        }, 300);

        return () => {
            window.clearTimeout(timer);
            controller.abort();
        };
    }, [value, open]);

    const choose = (place: Place) => {
        onSelect(place);
        setOpen(false);
        setActive(-1);
        setResults([]);
        setStatus('');
    };

    return (
        <div
            className="relative"
            onBlur={(event) => {
                if (!event.currentTarget.contains(event.relatedTarget)) {
                    setOpen(false);
                    setActive(-1);
                }
            }}
        >
            <Input
                id={inputId ?? id}
                aria-label={label}
                role="combobox"
                aria-expanded={expanded}
                aria-controls={expanded ? `${id}-options` : undefined}
                aria-describedby={`${id}-status`}
                aria-autocomplete="list"
                aria-activedescendant={
                    expanded && results[active] ? `${id}-${active}` : undefined
                }
                autoComplete="off"
                value={value}
                placeholder="Rechercher un port ou un mouillage"
                onChange={(event) => {
                    onChange(event.target.value);
                    setResults([]);
                    setActive(-1);
                    setStatus('');
                    setOpen(true);
                }}
                onFocus={() => setOpen(true)}
                onKeyDown={(event) => {
                    if (event.nativeEvent.isComposing) {
                        return;
                    }

                    if (event.key === 'Escape') {
                        setOpen(false);
                        setActive(-1);
                    }

                    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                        event.preventDefault();
                        setOpen(true);
                        setActive((index) => {
                            if (results.length === 0) {
                                return -1;
                            }

                            if (index < 0) {
                                return event.key === 'ArrowDown'
                                    ? 0
                                    : results.length - 1;
                            }

                            return (
                                (index +
                                    (event.key === 'ArrowDown' ? 1 : -1) +
                                    results.length) %
                                results.length
                            );
                        });
                    }

                    if (
                        event.key === 'Enter' &&
                        open &&
                        active >= 0 &&
                        results[active]
                    ) {
                        event.preventDefault();
                        choose(results[active]);
                    }
                }}
            />
            {open && results.length > 0 && (
                <ul
                    ref={list}
                    id={`${id}-options`}
                    role="listbox"
                    aria-label={`Suggestions : ${label}`}
                    className="absolute z-30 mt-1 max-h-64 w-full overflow-auto rounded-md border bg-background p-1 shadow-lg"
                >
                    {results.map((place, index) => (
                        <li
                            key={place.reference ?? `${place.name}-${index}`}
                            role="presentation"
                        >
                            <button
                                id={`${id}-${index}`}
                                role="option"
                                aria-selected={index === active}
                                type="button"
                                tabIndex={-1}
                                className={`w-full rounded p-3 text-left text-sm hover:bg-muted ${index === active ? 'bg-muted' : ''}`}
                                onMouseDown={(event) => event.preventDefault()}
                                onClick={() => choose(place)}
                            >
                                <span className="block font-medium">
                                    {place.name}
                                </span>
                                <span className="block text-xs text-muted-foreground">
                                    {[
                                        place.region,
                                        place.country_code,
                                        place.type === 'anchorage'
                                            ? 'Mouillage'
                                            : place.type === 'port'
                                              ? 'Port'
                                              : 'Localité',
                                    ]
                                        .filter(Boolean)
                                        .join(' · ')}{' '}
                                    — {place.latitude.toFixed(3)},{' '}
                                    {place.longitude.toFixed(3)}
                                </span>
                            </button>
                        </li>
                    ))}
                </ul>
            )}
            <p
                id={`${id}-status`}
                role="status"
                className="mt-1 text-xs text-muted-foreground"
            >
                {open
                    ? value.trim().length < 2
                        ? 'Saisissez au moins 2 caractères.'
                        : status
                    : ''}
            </p>
        </div>
    );
}
