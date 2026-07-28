<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaterwayEdge extends Model
{
    protected $fillable = [
        'from_node_id', 'to_node_id', 'name', 'distance_meters', 'lock_count',
        'min_depth_meters', 'max_air_draft_meters', 'max_length_meters', 'max_width_meters',
    ];

    protected function casts(): array
    {
        return [
            'distance_meters' => 'float',
            'min_depth_meters' => 'float',
            'max_air_draft_meters' => 'float',
            'max_length_meters' => 'float',
            'max_width_meters' => 'float',
        ];
    }
}
