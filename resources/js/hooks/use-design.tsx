import { useSyncExternalStore } from 'react';

/**
 * Design de l'interface, indépendant du mode jour / nuit :
 * - `passerelle` : écran de traceur de cartes, dense, capitales et chiffres à chasse fixe ;
 * - `large` : « Au large », feuilles arrondies et tons sable et brume.
 */
export type Design = 'passerelle' | 'large';

export const designs: { value: Design; label: string; description: string }[] =
    [
        {
            value: 'passerelle',
            label: 'Passerelle',
            description: 'Écran d’instruments, dense et lisible de nuit.',
        },
        {
            value: 'large',
            label: 'Au large',
            description: 'Feuilles arrondies, tons sable et brume.',
        },
    ];

const listeners = new Set<() => void>();
let currentDesign: Design = 'passerelle';

const isDesign = (value: unknown): value is Design =>
    value === 'passerelle' || value === 'large';

const setCookie = (value: Design): void => {
    if (typeof document === 'undefined') {
        return;
    }

    document.cookie = `design=${value};path=/;max-age=${365 * 24 * 60 * 60};SameSite=Lax`;
};

const applyDesign = (design: Design): void => {
    if (typeof document === 'undefined') {
        return;
    }

    document.documentElement.dataset.design = design;
};

export function initializeDesign(): void {
    if (typeof window === 'undefined') {
        return;
    }

    // Le serveur a déjà posé l'attribut depuis le cookie ; on s'aligne dessus.
    const fromDocument = document.documentElement.dataset.design;
    const stored = localStorage.getItem('design');

    currentDesign = isDesign(fromDocument)
        ? fromDocument
        : isDesign(stored)
          ? stored
          : 'passerelle';

    applyDesign(currentDesign);
}

const subscribe = (callback: () => void) => {
    listeners.add(callback);

    return () => listeners.delete(callback);
};

export function useDesign() {
    const design = useSyncExternalStore(
        subscribe,
        () => currentDesign,
        () => 'passerelle' as Design,
    );

    const updateDesign = (value: Design): void => {
        currentDesign = value;
        localStorage.setItem('design', value);
        setCookie(value);
        applyDesign(value);
        listeners.forEach((listener) => listener());
    };

    return { design, updateDesign } as const;
}
