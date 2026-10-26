import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { useState } from 'react';
import { afterEach, describe, expect, it, vi } from 'vitest';
import PlaceSearch from './place-search';
import type { Place } from './place-search';

const places: Place[] = ['Marseille', 'Marseillan'].map((name, index) => ({
    reference: `port:${index + 1}`,
    name,
    region: null,
    country_code: 'FR',
    latitude: 43,
    longitude: 5,
    type: 'port',
}));

function Search({ onSelect = vi.fn() }: { onSelect?: (place: Place) => void }) {
    const [value, setValue] = useState('');

    return (
        <PlaceSearch
            label="Départ"
            value={value}
            onChange={setValue}
            onSelect={onSelect}
        />
    );
}

function response(results: Place[]) {
    return { ok: true, json: async () => ({ places: results }) } as Response;
}

afterEach(() => vi.unstubAllGlobals());

describe('PlaceSearch', () => {
    it('only searches worldwide after an explicit click, even when local suggestions exist', async () => {
        const guadeloupe = {
            ...places[0],
            name: 'Pointe-à-Pitre',
            reference: 'locality:42',
            country_code: 'GP',
            latitude: 16.24,
            longitude: -61.53,
        };
        const fetchMock = vi.fn().mockImplementation(async (url: string) =>
            url.includes('worldwide=1')
                ? {
                      ok: true,
                      json: async () => ({
                          places: [guadeloupe],
                          attribution: 'OpenStreetMap',
                      }),
                  }
                : response([{ ...places[0], name: 'Pointe à Pitre' }]),
        );
        vi.stubGlobal('fetch', fetchMock);
        const onSelect = vi.fn();
        render(<Search onSelect={onSelect} />);
        fireEvent.change(screen.getByRole('combobox'), {
            target: { value: 'Pointe-à-Pitre' },
        });
        await screen.findByRole('option', { name: /Pointe à Pitre/ });
        expect(fetchMock).toHaveBeenCalledTimes(1);
        expect(fetchMock.mock.calls[0][0]).not.toContain('worldwide');
        fireEvent.click(
            screen.getByRole('button', { name: 'Rechercher dans le monde' }),
        );
        const result = await screen.findByRole('option', {
            name: /Pointe-à-Pitre.*GP/,
        });
        expect(
            screen.getByRole('link', { name: '© OpenStreetMap' }),
        ).toHaveAttribute('href', 'https://www.openstreetmap.org/copyright');
        fireEvent.click(result);
        expect(onSelect).toHaveBeenCalledExactlyOnceWith(guadeloupe);
    });

    it('selects the last result with ArrowUp, wraps to the first and selects with Enter', async () => {
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue(response(places)));
        const onSelect = vi.fn();
        render(<Search onSelect={onSelect} />);
        const input = screen.getByRole('combobox');
        fireEvent.change(input, { target: { value: 'Mar' } });
        const options = await screen.findAllByRole('option');
        fireEvent.keyDown(input, { key: 'ArrowUp' });
        expect(options[1]).toHaveAttribute('aria-selected', 'true');
        expect(input).toHaveAttribute('aria-activedescendant', options[1].id);
        fireEvent.keyDown(input, { key: 'ArrowDown' });
        expect(options[0]).toHaveAttribute('aria-selected', 'true');
        fireEvent.keyDown(input, { key: 'Enter' });
        expect(onSelect).toHaveBeenCalledExactlyOnceWith(places[0]);
        expect(input).toHaveAttribute('aria-expanded', 'false');
    });

    it('clears keyboard selection on Escape and never references a nonexistent option', async () => {
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue(response(places)));
        const onSelect = vi.fn();
        render(<Search onSelect={onSelect} />);
        const input = screen.getByRole('combobox');
        fireEvent.keyDown(input, { key: 'ArrowDown' });
        expect(input).not.toHaveAttribute('aria-activedescendant');
        fireEvent.change(input, { target: { value: 'Mar' } });
        await screen.findAllByRole('option');
        fireEvent.keyDown(input, { key: 'ArrowDown' });
        fireEvent.keyDown(input, { key: 'Escape' });
        fireEvent.focus(input);
        fireEvent.keyDown(input, { key: 'Enter' });
        expect(onSelect).not.toHaveBeenCalled();
        expect(input).not.toHaveAttribute('aria-activedescendant');
        expect(screen.getAllByRole('option')[0]).toHaveAttribute(
            'tabindex',
            '-1',
        );
    });

    it('ignores a late response after the search text changes', async () => {
        let resolveFirst!: (value: Response) => void;
        const fetchMock = vi
            .fn()
            .mockImplementationOnce(
                () =>
                    new Promise<Response>((resolve) => {
                        resolveFirst = resolve;
                    }),
            )
            .mockResolvedValueOnce(response([places[1]]));
        vi.stubGlobal('fetch', fetchMock);
        render(<Search />);
        const input = screen.getByRole('combobox');
        fireEvent.change(input, { target: { value: 'Mars' } });
        await waitFor(() => expect(fetchMock).toHaveBeenCalledTimes(1));
        fireEvent.change(input, { target: { value: 'Marseillan' } });
        expect(fetchMock.mock.calls[0][1].signal.aborted).toBe(true);
        await screen.findByRole('option', { name: /Marseillan/ });
        resolveFirst(response([places[0]]));
        await waitFor(() =>
            expect(screen.getAllByRole('option')).toHaveLength(1),
        );
        expect(
            screen.queryByRole('option', { name: /Marseille / }),
        ).not.toBeInTheDocument();
    });

    it('announces failures and recovers on the next search', async () => {
        vi.stubGlobal(
            'fetch',
            vi
                .fn()
                .mockRejectedValueOnce(new Error('offline'))
                .mockResolvedValueOnce(response(places)),
        );
        render(<Search />);
        const input = screen.getByRole('combobox');
        fireEvent.focus(input);
        expect(screen.getByRole('status')).toHaveTextContent(
            'Saisissez au moins 2 caractères.',
        );
        fireEvent.change(input, { target: { value: 'Mar' } });
        await screen.findByText(
            'Recherche indisponible. Réessayez en modifiant le nom.',
        );
        fireEvent.change(input, { target: { value: 'Mars' } });
        await screen.findAllByRole('option');
        expect(screen.getByRole('status')).toHaveTextContent(
            '2 lieux proposés.',
        );
    });
});
