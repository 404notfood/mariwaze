<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'audience',
        'monthly_price_cents',
        'yearly_price_cents',
        'currency',
        'boat_limit',
        'member_limit',
        'stripe_product_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'monthly_price_cents' => 'integer',
            'yearly_price_cents' => 'integer',
            'boat_limit' => 'integer',
            'member_limit' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(PlanFeature::class, 'plan_feature')
            ->withPivot('included_quantity')
            ->withTimestamps();
    }
}
