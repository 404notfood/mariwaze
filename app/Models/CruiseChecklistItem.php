<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CruiseChecklistItem extends Model
{
    protected $fillable = [
        'cruise_checklist_id',
        'section',
        'label',
        'quantity',
        'required',
        'checked',
    ];

    protected function casts(): array
    {
        return [
            'required' => 'boolean',
            'checked' => 'boolean',
        ];
    }

    /** @return BelongsTo<CruiseChecklist, $this> */
    public function checklist(): BelongsTo
    {
        return $this->belongsTo(CruiseChecklist::class, 'cruise_checklist_id');
    }
}
