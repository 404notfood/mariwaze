<?php

namespace App\Services\Safety;

use App\Models\Boat;
use App\Models\CountryRegulation;
use App\Models\Cruise;
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
            'countries' => $this->countries($cruise),
            'emergency' => $this->emergencyContacts($cruise),
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
     * Pays traversés, déduits des ports proches du départ et de l'arrivée.
     *
     * @return array<int, array<string, mixed>>
     */
    private function countries(?Cruise $cruise): array
    {
        $codes = $this->countryCodes($cruise);

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
     * @return array<int, array<string, mixed>>
     */
    private function emergencyContacts(?Cruise $cruise): array
    {
        $codes = $this->countryCodes($cruise);

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

    /**
     * @return list<string>
     */
    private function countryCodes(?Cruise $cruise): array
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

        return array_values(array_unique(array_filter($codes)));
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
