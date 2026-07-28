<?php

namespace App\Data\Mock;

class DashboardMockDataProvider
{
    public function toArray(): array
    {
        return [
            'user' => [
                'name' => 'Capitaine Martin',
                'boat' => 'Éole — Voilier 12 m',
            ],
            'cruise' => [
                'slug' => 'nouvelle-croisiere',
                'title' => 'Croisière à préparer',
                'route' => 'Choisissez votre départ et votre arrivée',
                'departure' => 'Date à définir',
                'duration' => 'À définir',
                'status' => 'Préparation',
                'progress' => 68,
                'miles' => 350,
                'stops' => 8,
                'readiness' => 85,
            ],
            'weather' => [
                'summary' => 'Favorable pour votre voilier',
                'wind' => '12 nds NE',
                'swell' => '0,8 m — Faible',
                'departure' => '08h00',
                'confidence' => 92,
            ],
            'briefing' => [
                'label' => 'Briefing du jour - J2',
                'from' => 'Saint-Jean-de-Luz',
                'to' => 'Bilbao',
                'departure' => '07h30',
                'arrival' => '15h45',
                'wind' => '12-15 nds NE',
                'swell' => '0,8 m, faible',
                'summary' => 'Navigation confortable pour votre voilier et votre équipage.',
                'attention' => [
                    'Trafic soutenu à l’approche de Bilbao.',
                    'Vérifier la batterie secondaire avant le départ.',
                ],
            ],
            'steps' => [
                ['day' => 'J1', 'from' => 'Biarritz', 'to' => 'Saint-Jean-de-Luz', 'distance' => '18 M', 'status' => 'done'],
                ['day' => 'J2', 'from' => 'Saint-Jean-de-Luz', 'to' => 'Bilbao', 'distance' => '42 M', 'status' => 'current'],
                ['day' => 'J3', 'from' => 'Bilbao', 'to' => 'Santander', 'distance' => '65 M', 'status' => 'upcoming'],
                ['day' => 'J4', 'from' => 'Santander', 'to' => 'Gijón', 'distance' => '78 M', 'status' => 'upcoming'],
            ],
            'checklist' => [
                ['label' => 'VHF fonctionnelle', 'done' => true],
                ['label' => 'Gilets adaptés équipage', 'done' => true],
                ['label' => 'Batterie secondaire', 'done' => false],
                ['label' => 'Mouillage complet', 'done' => true],
                ['label' => 'Cartographie à jour', 'done' => true],
            ],
            'anchorages' => [
                [
                    'slug' => 'cala-macarella',
                    'name' => 'Cala Macarella',
                    'rating' => 5,
                    'tags' => ['Eau turquoise', 'Protection Nord', 'Famille'],
                    'photos' => 245,
                ],
                [
                    'slug' => 'roccapina',
                    'name' => 'Anse de Roccapina',
                    'rating' => 4,
                    'tags' => ['Fond sable', 'Coucher de soleil'],
                    'photos' => 89,
                ],
            ],
            'alerts' => [
                [
                    'type' => 'attention',
                    'message' => 'La météo évolue sur votre prochaine étape (J3).',
                ],
                [
                    'type' => 'info',
                    'message' => 'Nouvelle photo ajoutée sur Cala Macarella.',
                ],
            ],
        ];
    }
}
