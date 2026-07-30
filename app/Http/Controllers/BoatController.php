<?php

namespace App\Http\Controllers;

use App\Models\Boat;
use App\Services\Accounts\AccountContext;
use App\Services\Safety\SafetyAuditor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BoatController extends Controller
{
    public function __construct(
        private readonly AccountContext $accounts,
        private readonly SafetyAuditor $auditor,
    ) {}

    public function show(Request $request): Response
    {
        $organization = $this->accounts->currentOrDemo($request->user());

        return Inertia::render('boat-show', [
            'boat' => $this->payload($this->boat($organization->id)),
            'fleet' => Boat::query()
                ->where('organization_id', $organization->id)
                ->latest()
                ->get()
                ->map(fn (Boat $boat) => $this->fleetPayload($boat))
                ->all(),
        ]);
    }

    public function setup(Request $request): Response
    {
        $organization = $this->accounts->currentOrDemo($request->user());

        return Inertia::render('boat-setup', [
            'boat' => $this->payload($this->boat($organization->id)),
            'catalog' => $this->catalog(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedBoat($request);

        $organization = $this->accounts->currentOrDemo($request->user());
        $boat = $this->boat($organization->id);
        $data['user_id'] = $request->user()?->id;
        $data['organization_id'] = $organization->id;
        $data['fleet_status'] ??= 'available';

        if ($boat === null) {
            Boat::create($data);
        } else {
            $boat->update($data);
        }

        return to_route('boat');
    }

    public function storeInFleet(Request $request): RedirectResponse
    {
        $organization = $this->accounts->currentOrDemo($request->user());
        $data = $this->validatedBoat($request);
        $data['user_id'] = $request->user()?->id;
        $data['organization_id'] = $organization->id;
        $data['fleet_status'] ??= 'available';

        Boat::create($data);

        return to_route('boat');
    }

    /**
     * Coche ou décoche un équipement de sécurité depuis la checklist du bateau.
     */
    public function updateEquipment(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'equipment' => ['required', 'array'],
            'equipment.*' => ['boolean'],
        ]);

        $organization = $this->accounts->currentOrDemo($request->user());
        $boat = $this->boat($organization->id);

        if ($boat === null) {
            return to_route('boat.setup');
        }

        $boat->update([
            'equipment_json' => [
                ...($boat->equipment_json ?? []),
                ...collect($data['equipment'])
                    ->only(array_keys(Boat::EQUIPMENT))
                    ->map(fn ($value): bool => (bool) $value)
                    ->all(),
            ],
        ]);

        return back();
    }

    private function boat(int $organizationId): ?Boat
    {
        return Boat::query()
            ->where('organization_id', $organizationId)
            ->latest()
            ->first();
    }

    /** @return array<string, mixed> */
    private function validatedBoat(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'model' => ['nullable', 'string', 'max:120'],
            'boat_type' => ['required', 'in:voilier,catamaran,moteur,deriveur'],
            'flag' => ['nullable', 'string', 'max:80'],
            'mmsi' => ['nullable', 'digits:9'],
            'length' => ['required', 'numeric', 'between:2,80'],
            'width' => ['required', 'numeric', 'between:1,20'],
            'draft' => ['required', 'numeric', 'between:0,8'],
            'mast_height' => ['nullable', 'numeric', 'between:0,70'],
            'avg_speed' => ['required', 'numeric', 'between:1,40'],
            'fuel_range' => ['nullable', 'integer', 'between:0,5000'],
            'fleet_status' => ['nullable', 'in:available,maintenance,booked,inactive'],
            'berths' => ['nullable', 'integer', 'between:0,80'],
            'fuel_consumption_per_hour' => ['nullable', 'numeric', 'between:0,500'],
            'equipment' => ['nullable', 'array'],
            'equipment.*' => ['boolean'],
        ]);

        // Seuls les équipements connus sont conservés, sous forme de booléens.
        if (array_key_exists('equipment', $data)) {
            $data['equipment_json'] = collect($data['equipment'] ?? [])
                ->only(array_keys(Boat::EQUIPMENT))
                ->map(fn ($value): bool => (bool) $value)
                ->all();
        }

        unset($data['equipment']);

        return $data;
    }

    /** @return array<int, array<string, int|float|string>> */
    private function catalog(): array
    {
        return [
            ['model' => 'Dufour 390', 'boat_type' => 'voilier', 'length' => 11.94, 'width' => 3.99, 'draft' => 1.95, 'mast_height' => 17.7, 'avg_speed' => 6.5, 'fuel_range' => 180],
            ['model' => 'Beneteau Oceanis 40.1', 'boat_type' => 'voilier', 'length' => 12.87, 'width' => 4.18, 'draft' => 2.17, 'mast_height' => 18.2, 'avg_speed' => 6.5, 'fuel_range' => 195],
            ['model' => 'Lagoon 42', 'boat_type' => 'catamaran', 'length' => 12.80, 'width' => 7.70, 'draft' => 1.25, 'mast_height' => 20.65, 'avg_speed' => 7.0, 'fuel_range' => 300],
            ['model' => 'Jeanneau Merry Fisher 895', 'boat_type' => 'moteur', 'length' => 8.90, 'width' => 2.99, 'draft' => 0.62, 'mast_height' => 3.15, 'avg_speed' => 22.0, 'fuel_range' => 250],
        ];
    }

    /** @return array<string, mixed> */
    private function payload(?Boat $boat): array
    {
        $types = [
            ['id' => 'voilier', 'label' => 'Voilier', 'icon' => 'S', 'description' => 'Monocoque ou bermudien, navigation voile'],
            ['id' => 'catamaran', 'label' => 'Catamaran', 'icon' => 'C', 'description' => 'Bi-coque, stabilité et confort'],
            ['id' => 'moteur', 'label' => 'Moteur', 'icon' => 'M', 'description' => 'Trawler, semi-rigide ou yacht'],
            ['id' => 'deriveur', 'label' => 'Dériveur', 'icon' => 'D', 'description' => 'Tirant d’eau réduit, côtes et mouillages'],
        ];
        $data = $boat?->only(['name', 'model', 'boat_type', 'flag', 'mmsi', 'length', 'width', 'draft', 'mast_height', 'avg_speed', 'fuel_range']) ?? [
            'name' => '', 'model' => '', 'boat_type' => 'voilier', 'flag' => 'France', 'mmsi' => '',
            'length' => 10, 'width' => 3.2, 'draft' => 1.8, 'mast_height' => 15, 'avg_speed' => 6, 'fuel_range' => 120,
        ];
        $type = (string) $data['boat_type'];
        $audit = $this->auditor->audit($boat);

        return [
            ...$data,
            'type' => $type,
            'type_label' => collect($types)->firstWhere('id', $type)['label'],
            'readiness' => $audit['readiness'],
            'readiness_label' => $audit['readiness_label'],
            'types' => $types,
            'equipment' => ($boat ?? new Boat)->equipment(),
        ];
    }

    /** @return array<string, mixed> */
    private function fleetPayload(Boat $boat): array
    {
        return [
            'id' => $boat->id,
            'name' => $boat->name,
            'model' => $boat->model,
            'type' => $boat->boat_type,
            'status' => $boat->fleet_status,
            'berths' => $boat->berths,
            'avg_speed' => $boat->avg_speed,
        ];
    }
}
