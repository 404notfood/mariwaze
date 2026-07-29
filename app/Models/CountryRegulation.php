<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CountryRegulation extends Model
{
    protected $fillable = [
        'country_code',
        'country_name',
        'flag_emoji',
        'required_documents',
        'rules',
        'anchoring_rules',
    ];

    protected function casts(): array
    {
        return [
            'required_documents' => 'array',
        ];
    }

    /** @return HasMany<EmergencyContact, $this> */
    public function emergencyContacts(): HasMany
    {
        return $this->hasMany(EmergencyContact::class, 'country_code', 'country_code')
            ->orderBy('position');
    }
}
