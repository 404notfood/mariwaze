import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export function Pagination({
    links,
    label,
}: {
    links: PaginationLink[];
    label: string;
}) {
    return (
        <nav aria-label={label} className="flex flex-wrap justify-center gap-1">
            {links.map((link) => (
                <Button
                    key={link.label}
                    asChild={link.url !== null}
                    variant={link.active ? 'default' : 'outline'}
                    size="sm"
                    disabled={link.url === null}
                >
                    {link.url ? (
                        <Link href={link.url} preserveScroll>
                            {paginationLabel(link.label)}
                        </Link>
                    ) : (
                        <span>{paginationLabel(link.label)}</span>
                    )}
                </Button>
            ))}
        </nav>
    );
}

function paginationLabel(label: string) {
    if (label.includes('Previous')) {
        return 'Précédent';
    }

    if (label.includes('Next')) {
        return 'Suivant';
    }

    return label;
}
