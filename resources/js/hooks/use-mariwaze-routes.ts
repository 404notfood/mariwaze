import { appRoutes } from '@/lib/mariwaze-routes';
import type { MariwazeRoutes } from '@/lib/mariwaze-routes';

export function useMariwazeRoutes(): MariwazeRoutes {
    return appRoutes;
}
