import { appRoutes, type MariwazeRoutes } from '@/lib/mariwaze-routes';

export function useMariwazeRoutes(): MariwazeRoutes {
    return appRoutes;
}
