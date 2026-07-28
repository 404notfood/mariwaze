import { usePage } from '@inertiajs/react';
import {
    mariwazeRoutesFromPath,
    mariwazeRoutesForPreview,
    type MariwazeRoutes,
} from '@/lib/mariwaze-routes';

export function useMariwazeRoutes(preview?: boolean): MariwazeRoutes {
    const { url } = usePage();

    if (preview === true) {
        return mariwazeRoutesForPreview(true);
    }

    const pathname = new URL(
        url,
        typeof window !== 'undefined' ? window.location.origin : 'http://localhost',
    ).pathname;

    return mariwazeRoutesFromPath(pathname);
}
