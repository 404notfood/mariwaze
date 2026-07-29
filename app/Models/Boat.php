<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read array<string, bool>|null $equipment_json
 */
class Boat extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'organization_id',
        'name',
        'model',
        'boat_type',
        'flag',
        'mmsi',
        'length',
        'width',
        'draft',
        'mast_height',
        'avg_speed',
        'fuel_range',
        'fleet_status',
        'berths',
        'fuel_consumption_per_hour',
        'equipment_json',
    ];

    /**
     * Équipements de sécurité reconnus par l'audit bateau et la checklist.
     *
     * @var array<string, string>
     */
    public const EQUIPMENT = [
        'vhf' => 'VHF',
        'ais' => 'AIS',
        'epirb' => 'Balise EPIRB',
        'liferaft' => 'Radeau de survie',
        'radar' => 'Radar',
        'autopilot' => 'Pilote automatique',
        'backup_battery' => 'Batterie secondaire',
        'lifejackets' => 'Gilets de sauvetage',
        'flares' => 'Feux à main',
        'fire_extinguisher' => 'Extincteurs',
        'first_aid' => 'Trousse de secours',
        'bilge_pump' => 'Pompe de cale',
    ];

    protected function casts(): array
    {
        return [
            'length' => 'float',
            'width' => 'float',
            'draft' => 'float',
            'mast_height' => 'float',
            'avg_speed' => 'float',
            'fuel_range' => 'integer',
            'berths' => 'integer',
            'fuel_consumption_per_hour' => 'float',
            'equipment_json' => 'array',
        ];
    }

    /**
     * Équipements du bateau sous forme de liste affichable.
     *
     * @return list<array{key: string, label: string, present: bool}>
     */
    public function equipment(): array
    {
        $equipment = [];

        foreach (self::EQUIPMENT as $key => $label) {
            $equipment[] = [
                'key' => $key,
                'label' => $label,
                'present' => $this->hasEquipment($key),
            ];
        }

        return $equipment;
    }

    public function hasEquipment(string $key): bool
    {
        $owned = $this->equipment_json;

        return is_array($owned) && (bool) ($owned[$key] ?? false);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function cruises(): HasMany
    {
        return $this->hasMany(Cruise::class);
    }
}
