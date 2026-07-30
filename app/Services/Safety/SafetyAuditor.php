<?php

namespace App\Services\Safety;

use App\Models\Boat;
use App\Models\CountryRegulation;
use App\Models\Cruise;
use App\Models\CruiseRoute;
use App\Models\EmergencyContact;
use App\Models\Port;

/**
 * Audit « Mon bateau est-il prêt ? » (doc/Intro.md, écrans 9-10).
 *
 * Remplace SecurityMockDataProvider : l'état de préparation découle des
 * équipements réellement déclarés sur le bateau, et la réglementation comme les
 * numéros d'urgence proviennent de la base.
 */
class SafetyAuditor
{
    public function __construct(
        private readonly RouteHazardService $hazards,
        private readonly CoastalCountryResolver $coasts,
    ) {}

    /**
     * Équipements requis selon la zone de navigation (division 240 française).
     *
     * @var array<string, list<string>>
     */
    private const REQUIRED_BY_AREA = [
        'coastal' => ['lifejackets', 'flares', 'fire_extinguisher', 'first_aid', 'vhf', 'bilge_pump'],
        'offshore' => ['lifejackets', 'flares', 'fire_extinguisher', 'first_aid', 'vhf', 'bilge_pump', 'liferaft', 'epirb', 'ais'],
        'inland' => ['lifejackets', 'fire_extinguisher', 'first_aid', 'bilge_pump'],
    ];

    /**
     * @return array<string, mixed>
     */
    public function audit(?Boat $boat, ?Cruise $cruise = null): array
    {
        $area = is_string($cruise?->navigation_area) ? $cruise->navigation_area : 'coastal';
        $required = self::REQUIRED_BY_AREA[$area] ?? self::REQUIRED_BY_AREA['coastal'];

        $checklist = [];
        $present = 0;

        foreach ($required as $key) {
            $has = $boat?->hasEquipment($key) ?? false;
            $present += $has ? 1 : 0;

            $checklist[] = [
                'key' => $key,
                'label' => Boat::EQUIPMENT[$key],
                'done' => $has,
                'required' => true,
            ];
        }

        // Équipements complémentaires : informatifs, hors calcul du taux.
        foreach (array_diff(array_keys(Boat::EQUIPMENT), $required) as $key) {
            $checklist[] = [
                'key' => $key,
                'label' => Boat::EQUIPMENT[$key],
                'done' => $boat?->hasEquipment($key) ?? false,
                'required' => false,
            ];
        }

        $readiness = (int) round(100 * $present / count($required));
        $route = $this->selectedRoute($cruise);
        $codes = $this->countryCodes($cruise, $route);

        return [
            'boat' => $boat === null ? null : [
                'name' => $boat->name,
                'type' => $boat->boat_type,
                'length' => $boat->length,
                'draft' => $boat->draft,
                'mmsi' => $boat->mmsi,
                'flag' => $boat->flag,
            ],
            'navigation_area' => $area,
            'readiness' => $readiness,
            'readiness_label' => $this->readinessLabel($readiness, $area),
            'missing' => collect($checklist)->where('required', true)->where('done', false)->pluck('label')->values()->all(),
            'checklist' => $checklist,
            'countries' => $this->countries($codes),
            'emergency' => $this->emergencyContacts($codes),
            'hazards' => $route === null || $cruise === null ? [] : $this->hazards->alertsForGeometry(
                $route->geometry ?? [],
                $cruise->departure_date,
                (int) $cruise->duration_days,
            ),
            'route_available' => $route !== null && count($route->geometry ?? []) > 1,
        ];
    }

    private function readinessLabel(int $readiness, string $area): string
    {
        $areaLabel = match ($area) {
            'offshore' => 'hauturière',
            'inland' => 'fluviale',
            default => 'côtière',
        };

        return match (true) {
            $readiness >= 100 => "Navigation {$areaLabel} : équipement complet",
            $readiness >= 75 => "Navigation {$areaLabel} : quelques équipements manquants",
            $readiness >= 40 => "Navigation {$areaLabel} : équipement insuffisant",
            default => "Navigation {$areaLabel} déconseillée en l'état",
        };
    }

    /**
     * Réglementations des pays traversés par l'itinéraire.
     *
     * @param  list<string>  $codes
     * @return array<int, array<string, mixed>>
     */
    private function countries(array $codes): array
    {
        if ($codes === []) {
            return [];
        }

        return CountryRegulation::query()
            ->whereIn('country_code', $codes)
            ->orderBy('country_name')
            ->get()
            ->map(fn (CountryRegulation $regulation): array => [
                'code' => $regulation->country_code,
                'name' => $regulation->country_name,
                'flag' => $regulation->flag_emoji,
                'docs' => $regulation->required_documents ?? [],
                'rules' => $regulation->rules,
                'anchoring_rules' => $regulation->anchoring_rules,
            ])
            ->all();
    }

    /**
     * @param  list<string>  $codes
     * @return array<int, array<string, mixed>>
     */
    private function emergencyContacts(array $codes): array
    {
        $query = EmergencyContact::query()->orderBy('position');

        if ($codes !== []) {
            $query->whereIn('country_code', $codes);
        }

        return $query->get()
            ->map(fn (EmergencyContact $contact): array => [
                'country_code' => $contact->country_code,
                'country_name' => $contact->country_name,
                'authority' => $contact->authority,
                'label' => $contact->label,
                'phone' => $contact->phone,
                'vhf_channel' => $contact->vhf_channel,
                'note' => $contact->note,
            ])
            ->all();
    }

    /** Route retenue de la croisière (ou première route calculée). */
    private function selectedRoute(?Cruise $cruise): ?CruiseRoute
    {
        if ($cruise === null) {
            return null;
        }

        return $cruise->routes()
            ->with('segments.port', 'segments.anchorage')
            ->orderByDesc('is_selected')
            ->first();
    }

    /**
     * Pays traversés : ports de départ et d'arrivée, escales de la route
     * retenue, puis échantillonnage du tracé (pays de la côte la plus proche).
     *
     * @return list<string>
     */
    private function countryCodes(?Cruise $cruise, ?CruiseRoute $route = null): array
    {
        if ($cruise === null) {
            return [];
        }

        $codes = [];

        foreach ([$cruise->departure_port, $cruise->destination_port] as $name) {
            $port = Port::query()->where('name', $name)->whereNotNull('country_code')->first();

            if ($port !== null) {
                $codes[] = $port->country_code;
            }
        }

        if ($route !== null) {
            foreach ($route->segments as $segment) {
                $codes[] = $segment->port?->country_code;
                $codes[] = $segment->anchorage?->country_code;
            }

            foreach ($this->samplePoints($route->geometry ?? []) as $point) {
                $codes[] = $this->coasts->resolve($point['latitude'], $point['longitude']);
            }
        }

        return array_values(array_unique(array_filter($codes)));
    }

    /**
     * Points répartis le long du tracé, pour la résolution des pays côtiers.
     *
     * @param  list<array{latitude: float, longitude: float}>  $geometry
     * @return list<array{latitude: float, longitude: float}>
     */
    private function samplePoints(array $geometry): array
    {
        $count = count($geometry);

        if ($count < 2) {
            return [];
        }

        $step = max(1, (int) ceil($count / 40));
        $points = [];

        for ($i = 0; $i < $count; $i += $step) {
            $points[] = $geometry[$i];
        }

        return $points;
    }

    /**
     * Contacts d'urgence les plus proches d'une position, pour le bouton urgence.
     *
     * @return array<int, array<string, mixed>>
     */
    public function nearestEmergencyContacts(float $latitude, float $longitude): array
    {
        $port = Port::query()
            ->whereNotNull('country_code')
            ->near($latitude, $longitude, 100)
            ->first();

        $query = EmergencyContact::query()->orderBy('position');

        if ($port !== null) {
            $query->where('country_code', $port->country_code);
        }

        $contacts = $query->get();

        if ($contacts->isEmpty()) {
            $contacts = EmergencyContact::query()->orderBy('position')->get();
        }

        return $contacts->map(fn (EmergencyContact $contact): array => [
            'authority' => $contact->authority,
            'label' => $contact->label,
            'phone' => $contact->phone,
            'vhf_channel' => $contact->vhf_channel,
            'country_name' => $contact->country_name,
            'note' => $contact->note,
        ])->all();
    }
}
