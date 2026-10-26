<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CruiseChecklist extends Model
{
    protected $fillable = [
        'cruise_id',
        'checklist_template_id',
        'name',
    ];

    /** @return BelongsTo<Cruise, $this> */
    public function cruise(): BelongsTo
    {
        return $this->belongsTo(Cruise::class);
    }

    /** @return BelongsTo<ChecklistTemplate, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ChecklistTemplate::class, 'checklist_template_id');
    }

    /** @return HasMany<CruiseChecklistItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(CruiseChecklistItem::class);
    }
}
