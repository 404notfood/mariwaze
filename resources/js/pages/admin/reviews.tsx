import { Head, router } from '@inertiajs/react';
import Check from 'lucide-react/icons/check';
import X from 'lucide-react/icons/x';
import { useState } from 'react';
import { Pagination } from '@/components/admin/pagination';
import type { PaginationLink } from '@/components/admin/pagination';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type ReviewStatus = 'pending' | 'approved' | 'rejected';

type ManagedReview = {
    id: number;
    author: string;
    email: string | null;
    place: string;
    rating: number;
    body: string | null;
    status: ReviewStatus;
    created_at: string | null;
};

type Props = {
    pendingCount: number;
    reviews: {
        data: ManagedReview[];
        total: number;
        links: PaginationLink[];
    };
};

export default function AdminReviews({ pendingCount, reviews }: Props) {
    return (
        <>
            <Head title="Admin - Avis" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                    <Heading
                        title="Modération des avis"
                        description="Contrôlez les contributions avant leur publication et leur prise en compte dans les notes."
                    />
                    <Badge variant={pendingCount > 0 ? 'default' : 'outline'}>
                        {pendingCount} en attente
                    </Badge>
                </div>

                <section className="grid gap-3">
                    {reviews.data.length === 0 ? (
                        <div className="rounded-lg border bg-card p-8 text-center text-sm text-muted-foreground">
                            Aucun avis à modérer.
                        </div>
                    ) : (
                        reviews.data.map((review) => (
                            <ReviewCard key={review.id} review={review} />
                        ))
                    )}
                </section>

                <Pagination links={reviews.links} label="Pagination des avis" />
            </div>
        </>
    );
}

AdminReviews.layout = {
    breadcrumbs: [
        { title: 'Admin', href: '/admin' },
        { title: 'Avis', href: '/admin/avis' },
    ],
};

function ReviewCard({ review }: { review: ManagedReview }) {
    const [processing, setProcessing] = useState(false);

    const moderate = (status: Exclude<ReviewStatus, 'pending'>) => {
        setProcessing(true);
        router.put(
            `/admin/avis/${review.id}`,
            { status },
            { preserveScroll: true, onFinish: () => setProcessing(false) },
        );
    };

    return (
        <article className="rounded-lg border bg-card p-5">
            <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div className="min-w-0 space-y-3">
                    <div className="flex flex-wrap items-center gap-2">
                        <p className="font-medium">{review.author}</p>
                        <span
                            aria-label={`${review.rating} étoiles`}
                            className="text-amber-500"
                        >
                            {'★'.repeat(review.rating)}
                        </span>
                        <StatusBadge status={review.status} />
                    </div>
                    <p className="text-xs text-muted-foreground">
                        {review.email ?? 'Adresse indisponible'} ·{' '}
                        {review.place}
                        {review.created_at
                            ? ` · ${new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium' }).format(new Date(review.created_at))}`
                            : ''}
                    </p>
                    <p className="text-sm whitespace-pre-wrap">
                        {review.body || 'Avis sans commentaire.'}
                    </p>
                </div>

                <div className="flex shrink-0 gap-2">
                    <Button
                        size="sm"
                        onClick={() => moderate('approved')}
                        disabled={processing || review.status === 'approved'}
                    >
                        <Check className="size-4" />
                        Approuver
                    </Button>
                    <Button
                        size="sm"
                        variant="destructive"
                        onClick={() => moderate('rejected')}
                        disabled={processing || review.status === 'rejected'}
                    >
                        <X className="size-4" />
                        Rejeter
                    </Button>
                </div>
            </div>
        </article>
    );
}

function StatusBadge({ status }: { status: ReviewStatus }) {
    const labels: Record<ReviewStatus, string> = {
        pending: 'En attente',
        approved: 'Approuvé',
        rejected: 'Rejeté',
    };

    return (
        <Badge variant={status === 'rejected' ? 'destructive' : 'secondary'}>
            {labels[status]}
        </Badge>
    );
}
