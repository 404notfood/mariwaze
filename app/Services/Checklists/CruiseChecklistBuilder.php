<?php

namespace App\Services\Checklists;

use App\Models\ChecklistItem;
use App\Models\ChecklistTemplate;
use App\Models\Cruise;
use App\Models\CruiseChecklist;

class CruiseChecklistBuilder
{
    public function build(Cruise $cruise): CruiseChecklist
    {
        $template = ChecklistTemplate::query()
            ->with('sections.items')
            ->where('navigation_area', $cruise->navigation_area)
            ->where('is_active', true)
            ->first();

        if ($template === null) {
            $template = ChecklistTemplate::query()
                ->with('sections.items')
                ->where('navigation_area', 'coastal')
                ->where('is_active', true)
                ->first();
        }

        if ($template === null) {
            $template = $this->defaultTemplate();
        }

        $checklist = CruiseChecklist::create([
            'cruise_id' => $cruise->id,
            'checklist_template_id' => $template->id,
            'name' => 'Checklist '.$cruise->title,
        ]);

        foreach ($template->sections as $section) {
            foreach ($section->items as $item) {
                $checklist->items()->create([
                    'section' => $section->name,
                    'label' => $item->label,
                    'quantity' => $this->quantity($item, $cruise),
                    'required' => $item->required,
                ]);
            }
        }

        return $checklist->load('items');
    }

    private function quantity(ChecklistItem $item, Cruise $cruise): ?string
    {
        $label = mb_strtolower($item->label);

        if (str_contains($label, 'eau')) {
            return max(1, $cruise->crew_count * $cruise->duration_days * 3).' L';
        }

        if (str_contains($label, 'repas') || str_contains($label, 'nourriture')) {
            return max(1, $cruise->crew_count * $cruise->duration_days * 3).' portions';
        }

        if (str_contains($label, 'carburant') && $cruise->boat?->fuel_consumption_per_hour !== null) {
            $hours = max(1, (int) ceil($cruise->duration_days * 4));

            return (int) ceil($hours * $cruise->boat->fuel_consumption_per_hour).' L';
        }

        return null;
    }

    private function defaultTemplate(): ChecklistTemplate
    {
        $template = ChecklistTemplate::create([
            'name' => 'Preparation cotiere',
            'navigation_area' => 'coastal',
            'is_active' => true,
        ]);

        $section = $template->sections()->create([
            'name' => 'Avitaillement',
            'position' => 1,
        ]);

        foreach (['Eau potable', 'Repas equipage', 'Carburant'] as $position => $label) {
            $section->items()->create([
                'label' => $label,
                'position' => $position + 1,
                'required' => true,
            ]);
        }

        return $template->load('sections.items');
    }
}
