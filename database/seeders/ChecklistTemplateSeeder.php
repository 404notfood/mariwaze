<?php

namespace Database\Seeders;

use App\Models\ChecklistTemplate;
use Illuminate\Database\Seeder;

class ChecklistTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            'coastal' => [
                'name' => 'Preparation cotiere',
                'sections' => [
                    'Avitaillement' => ['Eau potable', 'Repas equipage', 'Encas de quart', 'Gaz cuisine'],
                    'Securite' => ['Gilets verifies', 'VHF chargee', 'Trousse de secours', 'Mouillage pret'],
                    'Navigation' => ['Meteo consultee', 'Ports de repli notes', 'Documents bateau'],
                ],
            ],
            'offshore' => [
                'name' => 'Preparation hauturiere',
                'sections' => [
                    'Autonomie' => ['Eau potable avec marge', 'Nourriture longue duree', 'Carburant reserve', 'Batteries et recharge'],
                    'Securite hauturiere' => ['Radeau de survie', 'Balise EPIRB', 'AIS verifie', 'Quarts organises'],
                    'Route' => ['Fenetre meteo', 'Ports de deroutement', 'Plan de communication'],
                ],
            ],
            'inland' => [
                'name' => 'Preparation fluviale',
                'sections' => [
                    'Contraintes fluviales' => ['Vignette fluviale', 'Hauteur sous ponts', 'Tirant eau confirme', 'Horaires ecluses'],
                    'Vie a bord' => ['Eau potable', 'Repas equipage', 'Tri dechets', 'Electricite quai'],
                    'Navigation' => ['Cartes canaux', 'Points carburant', 'Haltes prevues'],
                ],
            ],
        ];

        foreach ($templates as $area => $definition) {
            $template = ChecklistTemplate::updateOrCreate(
                ['navigation_area' => $area],
                ['name' => $definition['name'], 'is_active' => true],
            );

            $sectionPosition = 1;

            foreach ($definition['sections'] as $sectionName => $items) {
                $section = $template->sections()->updateOrCreate(
                    ['name' => $sectionName],
                    ['position' => $sectionPosition],
                );

                foreach ($items as $itemIndex => $itemLabel) {
                    $section->items()->updateOrCreate(
                        ['label' => $itemLabel],
                        ['position' => $itemIndex + 1, 'required' => true],
                    );
                }

                $sectionPosition++;
            }
        }
    }
}
