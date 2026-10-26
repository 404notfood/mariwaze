<?php

namespace App\Http\Requests;

use App\Enums\BoatType;
use App\Enums\FleetStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BoatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'model' => ['nullable', 'string', 'max:120'],
            'boat_type' => ['required', Rule::enum(BoatType::class)],
            'flag' => ['nullable', 'string', 'max:80'],
            'mmsi' => ['nullable', 'digits:9'],
            'length' => ['required', 'numeric', 'between:2,80'],
            'width' => ['required', 'numeric', 'between:1,20'],
            'draft' => ['required', 'numeric', 'between:0,8'],
            'mast_height' => ['nullable', 'numeric', 'between:0,70'],
            'avg_speed' => ['required', 'numeric', 'between:1,40'],
            'fuel_range' => ['nullable', 'integer', 'between:0,5000'],
            'fleet_status' => ['nullable', Rule::enum(FleetStatus::class)],
            'berths' => ['nullable', 'integer', 'between:0,80'],
            'fuel_consumption_per_hour' => ['nullable', 'numeric', 'between:0,500'],
            'equipment' => ['nullable', 'array'],
            'equipment.*' => ['boolean'],
        ];
    }
}
