<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaterwayNode extends Model
{
    protected $fillable = ['latitude', 'longitude'];

    protected function casts(): array
    {
        return ['latitude' => 'float', 'longitude' => 'float'];
    }
}
