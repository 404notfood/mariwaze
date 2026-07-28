<?php

namespace App\Data\Mock;

use App\Data\Mock\Contracts\MockDataProviderInterface;

class BoatMockDataProvider implements MockDataProviderInterface
{
    public function toArray(): array
    {
        return [
            'name' => 'Éole',
            'type' => 'voilier',
            'type_label' => 'Voilier',
            'flag' => 'France',
            'mmsi' => '227123456',
            'length' => 12.4,
            'width' => 3.8,
            'draft' => 2.1,
            'mast_height' => 18.5,
            'avg_speed' => 6.5,
            'fuel_range' => 180,
            'readiness' => 85,
            'readiness_label' => 'Navigation côtière adaptée',
            'equipment' => [
                ['label' => 'VHF', 'present' => true],
                ['label' => 'AIS', 'present' => true],
                ['label' => 'Balise EPIRB', 'present' => true],
                ['label' => 'Radeau de survie', 'present' => true],
                ['label' => 'Radar', 'present' => false],
                ['label' => 'Pilote automatique', 'present' => true],
                ['label' => 'Batterie secondaire', 'present' => false],
            ],
            'types' => [
                ['id' => 'voilier', 'label' => 'Voilier', 'icon' => '⛵', 'description' => 'Monocoque ou bermudien, navigation voile'],
                ['id' => 'catamaran', 'label' => 'Catamaran', 'icon' => '🛥️', 'description' => 'Bi-coque, stabilité et confort'],
                ['id' => 'moteur', 'label' => 'Moteur', 'icon' => '🚤', 'description' => 'Trawler, semi-rigide ou yacht'],
                ['id' => 'deriveur', 'label' => 'Dériveur', 'icon' => '⛵', 'description' => 'Tirant d&apos;eau réduit, côtes et mouillages'],
            ],
        ];
    }
}
