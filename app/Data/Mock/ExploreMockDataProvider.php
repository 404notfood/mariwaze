<?php

namespace App\Data\Mock;

use App\Data\Mock\Contracts\MockDataProviderInterface;

class ExploreMockDataProvider implements MockDataProviderInterface
{
    public function toArray(): array
    {
        return [
            'spots' => [
                [
                    'slug' => 'cala-macarella',
                    'name' => 'Cala Macarella',
                    'type' => 'mouillage',
                    'region' => 'Minorque',
                    'rating' => 5,
                    'compatibility' => 'recommandé',
                    'tags' => ['Eau turquoise', 'Famille'],
                    'depth' => '4 – 8 m',
                ],
                [
                    'slug' => 'roccapina',
                    'name' => 'Anse de Roccapina',
                    'type' => 'mouillage',
                    'region' => 'Corse-du-Sud',
                    'rating' => 4,
                    'compatibility' => 'compatible',
                    'tags' => ['Coucher de soleil', 'Sauvage'],
                    'depth' => '3 – 12 m',
                ],
                [
                    'slug' => 'bonifacio',
                    'name' => 'Port de Bonifacio',
                    'type' => 'port',
                    'region' => 'Corse-du-Sud',
                    'rating' => 4,
                    'compatibility' => 'recommandé',
                    'tags' => ['Carburant', 'Provisions'],
                    'depth' => '2 – 6 m',
                ],
                [
                    'slug' => 'calvi',
                    'name' => 'Baie de Calvi',
                    'type' => 'mouillage',
                    'region' => 'Corse',
                    'rating' => 5,
                    'compatibility' => 'recommandé',
                    'tags' => ['Protection', 'Snorkeling'],
                    'depth' => '5 – 15 m',
                ],
            ],
            'chart' => [
                'markers' => [
                    ['id' => 'cala-macarella', 'x' => 68, 'y' => 42, 'label' => 'Cala Macarella', 'type' => 'anchorage'],
                    ['id' => 'roccapina', 'x' => 78, 'y' => 38, 'label' => 'Roccapina', 'type' => 'anchorage'],
                    ['id' => 'bonifacio', 'x' => 88, 'y' => 35, 'label' => 'Bonifacio', 'type' => 'port'],
                    ['id' => 'calvi', 'x' => 72, 'y' => 28, 'label' => 'Calvi', 'type' => 'anchorage'],
                ],
            ],
        ];
    }
}
