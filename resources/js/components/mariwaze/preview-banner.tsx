import { Link } from '@inertiajs/react';
import { Info } from 'lucide-react';

type Props = {
    preview?: boolean;
};

export default function PreviewBanner({ preview = false }: Props) {
    if (!preview) {
        return null;
    }

    return (
        <div className="flex items-center gap-3 rounded-xl border border-lagoon/30 bg-lagoon/10 px-4 py-3 text-sm text-foreground dark:border-lagoon/20 dark:bg-lagoon/5">
            <Info className="size-4 shrink-0 text-sea-blue dark:text-lagoon" />
            <span>
                Aperçu avec données fictives — explorez librement la démo MariWaze.
            </span>
            <Link
                href="/"
                className="ml-auto hidden shrink-0 text-sea-blue hover:underline sm:inline dark:text-lagoon"
            >
                Retour à l&apos;accueil
            </Link>
        </div>
    );
}
