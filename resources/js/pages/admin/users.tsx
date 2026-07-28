import { Head, router, usePage } from '@inertiajs/react';
import { Save, ShieldCheck, UserRound } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { Auth } from '@/types';

type ManagedUser = {
    id: number;
    name: string;
    email: string;
    role: 'user' | 'admin';
    organizations_count: number;
    boats_count: number;
    created_at: string;
};

type Props = {
    users: ManagedUser[];
};

export default function AdminUsers({ users }: Props) {
    return (
        <>
            <Head title="Admin - Utilisateurs" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                    <Heading
                        title="Utilisateurs"
                        description="Attribuez les accès d'administration de la plateforme."
                    />
                    <Badge variant="outline" className="w-fit">
                        {users.length} compte{users.length > 1 ? 's' : ''}
                    </Badge>
                </div>

                <section className="overflow-hidden rounded-lg border bg-card">
                    <div className="hidden grid-cols-[minmax(220px,1fr)_120px_150px_180px] gap-4 border-b bg-muted/40 px-5 py-2 text-xs font-medium text-muted-foreground md:grid">
                        <span>Compte</span>
                        <span>Flotte</span>
                        <span>Rôle</span>
                        <span className="text-right">Action</span>
                    </div>

                    <div className="divide-y">
                        {users.map((user) => (
                            <UserRoleRow key={user.id} user={user} />
                        ))}
                    </div>
                </section>
            </div>
        </>
    );
}

AdminUsers.layout = {
    breadcrumbs: [
        { title: 'Admin', href: '/admin' },
        { title: 'Utilisateurs', href: '/admin/utilisateurs' },
    ],
};

function UserRoleRow({ user }: { user: ManagedUser }) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const [role, setRole] = useState(user.role);
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState<string>();
    const isCurrentUser = auth.user.id === user.id;
    const changed = role !== user.role;

    const save = () => {
        setProcessing(true);
        setError(undefined);
        router.put(
            `/admin/utilisateurs/${user.id}/role`,
            { role },
            {
                preserveScroll: true,
                onError: (errors) => setError(errors.role),
                onFinish: () => setProcessing(false),
            },
        );
    };

    return (
        <div className="grid gap-4 px-5 py-4 md:grid-cols-[minmax(220px,1fr)_120px_150px_180px] md:items-center">
            <div className="flex min-w-0 items-center gap-3">
                <div className="flex size-9 shrink-0 items-center justify-center rounded-md bg-muted">
                    {user.role === 'admin' ? (
                        <ShieldCheck className="size-4" />
                    ) : (
                        <UserRound className="size-4" />
                    )}
                </div>
                <div className="min-w-0">
                    <div className="flex items-center gap-2">
                        <p className="truncate text-sm font-medium">{user.name}</p>
                        {isCurrentUser ? <Badge variant="secondary">Vous</Badge> : null}
                    </div>
                    <p className="truncate text-xs text-muted-foreground">{user.email}</p>
                </div>
            </div>

            <p className="text-sm text-muted-foreground">
                {user.boats_count} bateau{user.boats_count > 1 ? 'x' : ''}
            </p>

            <div>
                <Select value={role} onValueChange={(value: 'user' | 'admin') => setRole(value)}>
                    <SelectTrigger className="w-full" aria-label={`Rôle de ${user.name}`}>
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="user" disabled={isCurrentUser}>
                            Utilisateur
                        </SelectItem>
                        <SelectItem value="admin">Administrateur</SelectItem>
                    </SelectContent>
                </Select>
                <InputError message={error} />
            </div>

            <div className="flex justify-end">
                <Button
                    variant={changed ? 'default' : 'outline'}
                    size="sm"
                    disabled={!changed || processing}
                    onClick={save}
                >
                    <Save className="size-4" />
                    Enregistrer
                </Button>
            </div>
        </div>
    );
}
