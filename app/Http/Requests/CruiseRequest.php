<?php

namespace App\Http\Requests;

use App\Enums\NavigationArea;
use App\Enums\NavigationStyle;
use App\Services\Accounts\AccountContext;
use App\Services\Navigation\PortCoordinates;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CruiseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $organization = app(AccountContext::class)->currentOrCreate($this->user());

        return [
            'title' => ['required', 'string', 'max:120'],
            'departure_port' => ['required', 'string', 'max:120'],
            'destination_port' => ['required', 'string', 'max:120'],
            'departure_place' => ['nullable', 'string', 'max:80'],
            'destination_place' => ['nullable', 'string', 'max:80'],
            'departure_date' => ['required', 'date', 'after_or_equal:today'],
            'duration_days' => [Rule::excludeIf($this->boolean('auto_duration')), 'required', 'integer', 'between:1,180'],
            'auto_duration' => ['required', 'boolean'],
            'crew_count' => ['required', 'integer', 'between:1,20'],
            'navigation_area' => ['nullable', Rule::enum(NavigationArea::class)],
            'boat_id' => [
                'nullable',
                Rule::exists('boats', 'id')->where('organization_id', $organization->id),
            ],
            'navigation_style' => ['required', Rule::enum(NavigationStyle::class)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach (['departure', 'destination'] as $side) {
                $reference = $this->input($side.'_place');
                if ($reference === null || ! is_string($reference)) {
                    continue;
                }
                $place = app(PortCoordinates::class)->referenced($reference);
                if ($place === null || $place->name !== $this->input($side.'_port')) {
                    $validator->errors()->add($side.'_port', 'Sélectionnez à nouveau ce lieu dans les suggestions.');
                }
            }
            if ($this->filled('departure_place') && $this->input('departure_place') === $this->input('destination_place')) {
                $validator->errors()->add('destination_port', 'Choisissez une destination différente du départ.');
            }
        });
    }
}
