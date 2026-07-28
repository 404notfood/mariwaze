import { Form, Head, usePage } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/delete-user';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import type { Auth } from '@/types';

type PageProps = {
    auth: Auth;
};

type Account = {
    id: number;
    name: string;
    type: string;
    boats_count: number;
    members_count: number;
};

export default function Profile({
    mustVerifyEmail,
    status,
    account,
}: {
    mustVerifyEmail: boolean;
    status?: string;
    account?: Account | null;
}) {
    const { auth } = usePage<PageProps>().props;

    return (
        <>
            <Head title="Paramètres du profil" />

            <h1 className="sr-only">Paramètres du profil</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Profil"
                    description="Mettez à jour votre identité et l’adresse utilisée pour vous connecter."
                />

                <Form
                    {...ProfileController.update.form()}
                    options={{
                        preserveScroll: true,
                    }}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Nom</Label>

                                <Input
                                    id="name"
                                    className="mt-1 block w-full"
                                    defaultValue={auth.user.name}
                                    name="name"
                                    required
                                    autoComplete="name"
                                    placeholder="Nom complet"
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.name}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Adresse email</Label>

                                <Input
                                    id="email"
                                    type="email"
                                    className="mt-1 block w-full"
                                    defaultValue={auth.user.email}
                                    name="email"
                                    required
                                    autoComplete="username"
                                    placeholder="Adresse email"
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.email}
                                />
                            </div>

                            {mustVerifyEmail &&
                                auth.user.email_verified_at === null && (
                                    <div>
                                        <p className="-mt-4 text-sm text-muted-foreground">
                                            Votre adresse email n’est pas
                                            vérifiée.{' '}
                                            <Link
                                                href={send()}
                                                as="button"
                                                className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                            >
                                                Renvoyer l’email de
                                                vérification.
                                            </Link>
                                        </p>

                                        {status ===
                                            'verification-link-sent' && (
                                            <div className="mt-2 text-sm font-medium text-green-600">
                                                Un nouveau lien de vérification
                                                a été envoyé.
                                            </div>
                                        )}
                                    </div>
                                )}

                            <div className="space-y-4 rounded-lg border p-4">
                                <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                    <div className="space-y-1">
                                        <Heading
                                            variant="small"
                                            title="Compte & flotte"
                                            description="Ce compte regroupe vos bateaux, croisières et accès d’équipe."
                                        />
                                    </div>

                                    {account && (
                                        <Badge variant="secondary">
                                            {account.type === 'business'
                                                ? 'Pro'
                                                : account.type === 'demo'
                                                  ? 'Démo'
                                                  : 'Personnel'}
                                        </Badge>
                                    )}
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="organization_name">
                                        Nom du compte
                                    </Label>

                                    <Input
                                        id="organization_name"
                                        className="mt-1 block w-full"
                                        defaultValue={
                                            account?.name ?? auth.user.name
                                        }
                                        name="organization_name"
                                        placeholder="Nom du compte ou de la flotte"
                                    />

                                    <InputError
                                        message={errors.organization_name}
                                    />
                                </div>

                                <div className="grid gap-3 text-sm sm:grid-cols-2">
                                    <div className="rounded-md border bg-muted/30 p-3">
                                        <p className="text-muted-foreground">
                                            Bateaux
                                        </p>
                                        <p className="mt-1 text-lg font-semibold">
                                            {account?.boats_count ?? 0}
                                        </p>
                                    </div>
                                    <div className="rounded-md border bg-muted/30 p-3">
                                        <p className="text-muted-foreground">
                                            Membres
                                        </p>
                                        <p className="mt-1 text-lg font-semibold">
                                            {account?.members_count ?? 1}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div className="flex items-center gap-4">
                                <Button
                                    disabled={processing}
                                    data-test="update-profile-button"
                                >
                                    Enregistrer
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>

            <DeleteUser />
        </>
    );
}

Profile.layout = {
    breadcrumbs: [
        {
            title: 'Paramètres du profil',
            href: edit(),
        },
    ],
};
