import { Head, router } from '@inertiajs/react';
import Check from 'lucide-react/icons/check';
import CircleDollarSign from 'lucide-react/icons/circle-dollar-sign';
import Euro from 'lucide-react/icons/euro';
import Save from 'lucide-react/icons/save';
import Settings2 from 'lucide-react/icons/settings-2';
import Ship from 'lucide-react/icons/ship';
import Users from 'lucide-react/icons/users';
import type { FormEvent, ReactNode } from 'react';
import { useMemo, useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';

type Feature = {
    id: number;
    name: string;
    slug: string;
    category: string;
    description?: string | null;
    is_billable_option?: boolean;
};

type Plan = {
    id: number;
    name: string;
    slug: string;
    audience: 'personal' | 'business';
    monthly_price_cents: number;
    yearly_price_cents: number | null;
    currency: string;
    boat_limit: number | null;
    member_limit: number | null;
    is_active: boolean;
    features: Feature[];
};

type PlanForm = {
    name: string;
    audience: 'personal' | 'business';
    monthly_price_euros: string;
    yearly_price_euros: string;
    boat_limit: string;
    member_limit: string;
    is_active: boolean;
    feature_ids: number[];
};

type Props = {
    plans: Plan[];
    features: Feature[];
};

const categoryLabels: Record<string, string> = {
    navigation: 'Navigation',
    exports: 'Exports et préparation',
    pro: 'Professionnels',
};

const priceFormatter = new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
});

export default function AdminPricing({ plans, features }: Props) {
    const [selectedPlanId, setSelectedPlanId] = useState(plans[0]?.id ?? null);
    const selectedPlan =
        plans.find((plan) => plan.id === selectedPlanId) ?? plans[0];
    const activePlans = plans.filter((plan) => plan.is_active).length;

    return (
        <>
            <Head title="Admin - Tarifs et options" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                    <Heading
                        title="Tarifs et options"
                        description="Configurez les prix, les limites de flotte et les fonctionnalités incluses."
                    />

                    <div className="flex flex-wrap gap-2">
                        <SummaryBadge
                            icon={Euro}
                            label={`${plans.length} offres`}
                        />
                        <SummaryBadge
                            icon={Check}
                            label={`${activePlans} actives`}
                        />
                        <SummaryBadge
                            icon={Settings2}
                            label={`${features.length} options`}
                        />
                    </div>
                </div>

                {selectedPlan ? (
                    <div className="grid min-h-0 gap-6 xl:grid-cols-[240px_minmax(0,1fr)]">
                        <PlanSelector
                            plans={plans}
                            selectedPlanId={selectedPlan.id}
                            onSelect={setSelectedPlanId}
                        />
                        <PlanEditor
                            key={selectedPlan.id}
                            plan={selectedPlan}
                            features={features}
                        />
                    </div>
                ) : (
                    <div className="rounded-lg border bg-card px-6 py-12 text-center">
                        <CircleDollarSign className="mx-auto size-7 text-muted-foreground" />
                        <h2 className="mt-3 font-semibold">
                            Aucune offre configurée
                        </h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Le catalogue tarifaire est vide.
                        </p>
                    </div>
                )}
            </div>
        </>
    );
}

AdminPricing.layout = {
    breadcrumbs: [
        { title: 'Admin', href: '/admin' },
        { title: 'Tarifs et options', href: '/admin/tarifs' },
    ],
};

function PlanSelector({
    plans,
    selectedPlanId,
    onSelect,
}: {
    plans: Plan[];
    selectedPlanId: number;
    onSelect: (id: number) => void;
}) {
    return (
        <aside>
            <p className="mb-2 text-xs font-medium text-muted-foreground uppercase">
                Offres
            </p>
            <div className="grid grid-cols-2 gap-2 sm:grid-cols-3 xl:grid-cols-1">
                {plans.map((plan) => (
                    <button
                        key={plan.id}
                        type="button"
                        onClick={() => onSelect(plan.id)}
                        className={cn(
                            'min-w-0 rounded-md border px-3 py-3 text-left transition-colors',
                            plan.id === selectedPlanId
                                ? 'border-primary bg-primary/5'
                                : 'bg-card hover:bg-accent/50',
                        )}
                    >
                        <span className="flex items-center justify-between gap-2">
                            <span className="truncate text-sm font-medium">
                                {plan.name}
                            </span>
                            <span
                                className={cn(
                                    'size-2 shrink-0 rounded-full',
                                    plan.is_active
                                        ? 'bg-emerald-500'
                                        : 'bg-muted-foreground/40',
                                )}
                            />
                        </span>
                        <span className="mt-1 block text-xs text-muted-foreground">
                            {priceFormatter.format(
                                plan.monthly_price_cents / 100,
                            )}
                            /mois
                        </span>
                    </button>
                ))}
            </div>
        </aside>
    );
}

function PlanEditor({ plan, features }: { plan: Plan; features: Feature[] }) {
    const initial = useMemo<PlanForm>(() => toForm(plan), [plan]);
    const [form, setForm] = useState<PlanForm>(initial);
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const changed = JSON.stringify(form) !== JSON.stringify(initial);

    const featureGroups = useMemo(
        () =>
            features.reduce<Record<string, Feature[]>>((groups, feature) => {
                (groups[feature.category] ??= []).push(feature);

                return groups;
            }, {}),
        [features],
    );

    const monthlyPrice = parseEuro(form.monthly_price_euros);
    const yearlyPrice = parseEuro(form.yearly_price_euros);
    const yearlySaving =
        monthlyPrice !== null && yearlyPrice !== null
            ? monthlyPrice * 12 - yearlyPrice
            : null;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        setProcessing(true);
        setErrors({});

        router.put(
            `/admin/tarifs/${plan.id}`,
            {
                name: form.name,
                audience: form.audience,
                monthly_price_cents: eurosToCents(form.monthly_price_euros),
                yearly_price_cents: eurosToCents(form.yearly_price_euros),
                boat_limit: form.boat_limit || null,
                member_limit: form.member_limit || null,
                is_active: form.is_active,
                feature_ids: form.feature_ids,
            },
            {
                preserveScroll: true,
                onError: (newErrors) => setErrors(newErrors),
                onFinish: () => setProcessing(false),
            },
        );
    };

    const toggleFeature = (featureId: number) => {
        setForm((current) => ({
            ...current,
            feature_ids: current.feature_ids.includes(featureId)
                ? current.feature_ids.filter((id) => id !== featureId)
                : [...current.feature_ids, featureId],
        }));
    };

    return (
        <form
            onSubmit={submit}
            className="overflow-hidden rounded-lg border bg-card"
        >
            <header className="flex flex-col gap-4 border-b px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div className="min-w-0">
                    <div className="flex items-center gap-2">
                        <h2 className="truncate text-lg font-semibold">
                            {plan.name}
                        </h2>
                        <Badge variant={form.is_active ? 'default' : 'outline'}>
                            {form.is_active ? 'Active' : 'Masquée'}
                        </Badge>
                    </div>
                    <p className="mt-1 text-xs text-muted-foreground">
                        Identifiant : {plan.slug}
                    </p>
                </div>
                <Button disabled={!changed || processing} className="sm:w-auto">
                    <Save className="size-4" />
                    {processing ? 'Enregistrement…' : 'Enregistrer'}
                </Button>
            </header>

            <div className="divide-y">
                <EditorSection
                    title="Informations commerciales"
                    description="Nom public, audience et visibilité de l'offre."
                >
                    <div className="grid gap-4 md:grid-cols-2">
                        <Field label="Nom de l'offre" error={errors.name}>
                            <Input
                                value={form.name}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        name: event.target.value,
                                    })
                                }
                            />
                        </Field>
                        <Field label="Audience" error={errors.audience}>
                            <Select
                                value={form.audience}
                                onValueChange={(
                                    audience: PlanForm['audience'],
                                ) => setForm({ ...form, audience })}
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="personal">
                                        Particuliers
                                    </SelectItem>
                                    <SelectItem value="business">
                                        Professionnels / loueurs
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </Field>
                    </div>

                    <label className="mt-4 flex items-center gap-3 rounded-md border p-3 text-sm">
                        <Checkbox
                            checked={form.is_active}
                            onCheckedChange={(checked) =>
                                setForm({
                                    ...form,
                                    is_active: checked === true,
                                })
                            }
                        />
                        <span>
                            <span className="block font-medium">
                                Offre visible
                            </span>
                            <span className="text-xs text-muted-foreground">
                                L'offre peut être présentée aux nouveaux
                                abonnés.
                            </span>
                        </span>
                    </label>
                </EditorSection>

                <EditorSection
                    title="Prix"
                    description="Montants affichés en euros, taxes comprises."
                >
                    <div className="grid gap-4 md:grid-cols-2">
                        <Field
                            label="Prix mensuel"
                            error={errors.monthly_price_cents}
                            suffix="€"
                        >
                            <Input
                                inputMode="decimal"
                                value={form.monthly_price_euros}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        monthly_price_euros: event.target.value,
                                    })
                                }
                            />
                        </Field>
                        <Field
                            label="Prix annuel"
                            error={errors.yearly_price_cents}
                            suffix="€"
                        >
                            <Input
                                inputMode="decimal"
                                placeholder="Aucun tarif annuel"
                                value={form.yearly_price_euros}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        yearly_price_euros: event.target.value,
                                    })
                                }
                            />
                        </Field>
                    </div>
                    {yearlySaving !== null && yearlySaving > 0 ? (
                        <p className="mt-3 text-sm text-emerald-700 dark:text-emerald-400">
                            L'abonnement annuel économise{' '}
                            {priceFormatter.format(yearlySaving)} par an.
                        </p>
                    ) : null}
                </EditorSection>

                <EditorSection
                    title="Limites du compte"
                    description="Laissez un champ vide pour ne pas appliquer de limite."
                >
                    <div className="grid gap-4 md:grid-cols-2">
                        <Field
                            label="Nombre de bateaux"
                            error={errors.boat_limit}
                            icon={Ship}
                        >
                            <Input
                                type="number"
                                min="1"
                                placeholder="Illimité"
                                value={form.boat_limit}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        boat_limit: event.target.value,
                                    })
                                }
                            />
                        </Field>
                        <Field
                            label="Nombre de membres"
                            error={errors.member_limit}
                            icon={Users}
                        >
                            <Input
                                type="number"
                                min="1"
                                placeholder="Illimité"
                                value={form.member_limit}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        member_limit: event.target.value,
                                    })
                                }
                            />
                        </Field>
                    </div>
                </EditorSection>

                <EditorSection
                    title="Fonctionnalités incluses"
                    description={`${form.feature_ids.length} option${form.feature_ids.length > 1 ? 's' : ''} incluse${form.feature_ids.length > 1 ? 's' : ''} dans cette offre.`}
                >
                    <div className="space-y-5">
                        {Object.entries(featureGroups).map(
                            ([category, categoryFeatures]) => (
                                <div key={category}>
                                    <p className="mb-2 text-xs font-medium text-muted-foreground uppercase">
                                        {categoryLabels[category] ?? category}
                                    </p>
                                    <div className="grid gap-2 lg:grid-cols-2">
                                        {categoryFeatures.map((feature) => {
                                            const checked =
                                                form.feature_ids.includes(
                                                    feature.id,
                                                );

                                            return (
                                                <label
                                                    key={feature.id}
                                                    className={cn(
                                                        'flex cursor-pointer items-start gap-3 rounded-md border p-3 text-sm transition-colors',
                                                        checked
                                                            ? 'border-primary/50 bg-primary/5'
                                                            : 'hover:bg-accent/40',
                                                    )}
                                                >
                                                    <Checkbox
                                                        checked={checked}
                                                        onCheckedChange={() =>
                                                            toggleFeature(
                                                                feature.id,
                                                            )
                                                        }
                                                    />
                                                    <span className="min-w-0">
                                                        <span className="flex flex-wrap items-center gap-2 font-medium">
                                                            {feature.name}
                                                            {feature.is_billable_option ? (
                                                                <Badge variant="outline">
                                                                    Option
                                                                    payante
                                                                </Badge>
                                                            ) : null}
                                                        </span>
                                                        {feature.description ? (
                                                            <span className="mt-1 block text-xs text-muted-foreground">
                                                                {
                                                                    feature.description
                                                                }
                                                            </span>
                                                        ) : null}
                                                    </span>
                                                </label>
                                            );
                                        })}
                                    </div>
                                </div>
                            ),
                        )}
                    </div>
                </EditorSection>
            </div>
        </form>
    );
}

function toForm(plan: Plan): PlanForm {
    return {
        name: plan.name,
        audience: plan.audience,
        monthly_price_euros: formatEditablePrice(plan.monthly_price_cents),
        yearly_price_euros:
            plan.yearly_price_cents === null
                ? ''
                : formatEditablePrice(plan.yearly_price_cents),
        boat_limit: plan.boat_limit?.toString() ?? '',
        member_limit: plan.member_limit?.toString() ?? '',
        is_active: plan.is_active,
        feature_ids: plan.features.map((feature) => feature.id),
    };
}

function formatEditablePrice(cents: number): string {
    return (cents / 100).toFixed(cents % 100 === 0 ? 0 : 2).replace('.', ',');
}

function parseEuro(value: string): number | null {
    if (value.trim() === '') {
        return null;
    }

    const parsed = Number(value.replace(',', '.'));

    return Number.isFinite(parsed) ? parsed : null;
}

function eurosToCents(value: string): number | null {
    const parsed = parseEuro(value);

    return parsed === null ? null : Math.round(parsed * 100);
}

function EditorSection({
    title,
    description,
    children,
}: {
    title: string;
    description: string;
    children: ReactNode;
}) {
    return (
        <section className="grid gap-5 px-5 py-6 lg:grid-cols-[220px_minmax(0,1fr)]">
            <div>
                <h3 className="text-sm font-semibold">{title}</h3>
                <p className="mt-1 text-sm text-muted-foreground">
                    {description}
                </p>
            </div>
            <div>{children}</div>
        </section>
    );
}

function Field({
    label,
    error,
    suffix,
    icon: Icon,
    children,
}: {
    label: string;
    error?: string;
    suffix?: string;
    icon?: typeof Ship;
    children: ReactNode;
}) {
    return (
        <div className="grid gap-2">
            <Label className="flex items-center gap-2">
                {Icon ? (
                    <Icon className="size-4 text-muted-foreground" />
                ) : null}
                {label}
            </Label>
            <div className="relative [&>input]:pr-9">
                {children}
                {suffix ? (
                    <span className="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-muted-foreground">
                        {suffix}
                    </span>
                ) : null}
            </div>
            <InputError message={error} />
        </div>
    );
}

function SummaryBadge({
    icon: Icon,
    label,
}: {
    icon: typeof Settings2;
    label: string;
}) {
    return (
        <div className="flex h-9 items-center gap-2 rounded-md border bg-card px-3 text-sm">
            <Icon className="size-4 text-muted-foreground" />
            <span>{label}</span>
        </div>
    );
}
