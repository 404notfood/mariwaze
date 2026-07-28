<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CruiseChecklist extends Model
{
    use HasFactory;

    protected $fillable = [
        'cruise_id',
        'checklist_template_id',
        'name',
    ];

    public function cruise(): BelongsTo
    {
        return $this->belongsTo(Cruise::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ChecklistTemplate::class, 'checklist_template_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CruiseChecklistItem::class);
    }
}
