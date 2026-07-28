<?php

namespace App\Data\Mock;

use App\Data\Mock\Contracts\MockDataProviderInterface;

class AnchorageMockDataProvider implements MockDataProviderInterface
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $anchorages = [];

    public function __construct()
    {
        $this->anchorages = [
            'cala-macarella' => $this->buildCalaMacarella(),
            'roccapina' => $this->buildRoccapina(),
        ];
    }

    public function toArray(): array
    {
        return array_values($this->anchorages);
    }

    public function find(string $slug): ?array
    {
        return $this->anchorages[$slug] ?? null;
    }

    private function buildCalaMacarella(): array
    {
        return [
            'slug' => 'cala-macarella',
            'name' => 'Cala Macarella',
            'region' => 'Minorque, Espagne',
            'rating' => 5,
            'reviews' => 312,
            'photos' => 245,
            'compatibility' => 'recommandé',
            'compatibility_score' => 95,
            'summary' => 'Mouillage paradisiaque, eau turquoise et protection excellente par vent de Nord.',
            'depth' => '4 – 8 m',
            'bottom' => 'Sable fin',
            'holding' => 'Excellente tenue',
            'protection' => ['Nord', 'NE', 'NO'],
            'exposure' => 'Exposé Est par forte tramontane',
            'ideal_for' => ['Famille', 'Snorkeling', 'Baignade'],
            'warnings' => ['Vent Est fort', 'Affluence juillet-août'],
            'tags' => ['Eau turquoise', 'Fond sable', 'Protection Nord'],
            'chart' => [
                'x' => 68,
                'y' => 42,
                'markers' => [
                    ['id' => 'cala-macarella', 'x' => 68, 'y' => 42, 'label' => 'Cala Macarella', 'type' => 'anchorage'],
                    ['id' => 'macarelleta', 'x' => 70, 'y' => 44, 'label' => 'Macarelleta', 'type' => 'anchorage'],
                ],
            ],
            'community' => [
                ['author' => 'Marie L.', 'rating' => 5, 'text' => 'Ancre parfaite par mistral. Arrivée tôt conseillée.', 'date' => 'Juin 2026'],
                ['author' => 'Pierre D.', 'rating' => 5, 'text' => 'Eau cristalline, idéal paddle et enfants.', 'date' => 'Août 2025'],
            ],
        ];
    }

    private function buildRoccapina(): array
    {
        return [
            'slug' => 'roccapina',
            'name' => 'Anse de Roccapina',
            'region' => 'Corse-du-Sud, France',
            'rating' => 4,
            'reviews' => 156,
            'photos' => 89,
            'compatibility' => 'compatible',
            'compatibility_score' => 82,
            'summary' => 'Mouillage sauvage remarquable, rochers de granit et coucher de soleil spectaculaire.',
            'depth' => '3 – 12 m',
            'bottom' => 'Sable et roche',
            'holding' => 'Bonne tenue',
            'protection' => ['Ouest', 'Sud-Ouest'],
            'exposure' => 'Sensibles aux vents d&apos;Est',
            'ideal_for' => ['Coucher de soleil', 'Photos', 'Calme'],
            'warnings' => ['Fond irrégulier', 'Rafales locales'],
            'tags' => ['Fond sable', 'Coucher de soleil', 'Spot photo'],
            'chart' => [
                'x' => 78,
                'y' => 38,
                'markers' => [
                    ['id' => 'roccapina', 'x' => 78, 'y' => 38, 'label' => 'Roccapina', 'type' => 'anchorage'],
                ],
            ],
            'community' => [
                ['author' => 'Luc B.', 'rating' => 4, 'text' => 'Magnifique au coucher du soleil. Mouillage par 6 m.', 'date' => 'Sept. 2025'],
            ],
        ];
    }
}
