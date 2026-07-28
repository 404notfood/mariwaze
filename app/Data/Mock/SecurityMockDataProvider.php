<?php

namespace App\Data\Mock;

use App\Data\Mock\Contracts\MockDataProviderInterface;

class SecurityMockDataProvider implements MockDataProviderInterface
{
    public function toArray(): array
    {
        return [
            'readiness' => 85,
            'readiness_label' => 'Navigation côtière adaptée',
            'checklist' => [
                ['label' => 'Gilets adaptés équipage', 'done' => true, 'category' => 'Équipage'],
                ['label' => 'VHF fonctionnelle', 'done' => true, 'category' => 'Communication'],
                ['label' => 'Batterie secondaire', 'done' => false, 'category' => 'Énergie'],
                ['label' => 'Mouillage complet', 'done' => true, 'category' => 'Ancrage'],
                ['label' => 'Extincteurs vérifiés', 'done' => true, 'category' => 'Sécurité'],
                ['label' => 'Feux de navigation', 'done' => true, 'category' => 'Sécurité'],
                ['label' => 'Cartographie à jour', 'done' => true, 'category' => 'Navigation'],
            ],
            'countries' => [
                ['name' => 'France', 'flag' => '🇫🇷', 'docs' => ['Permis côtier', 'Assurance RC'], 'rules' => 'Feux obligatoires de nuit'],
                ['name' => 'Espagne', 'flag' => '🇪🇸', 'docs' => ['Assurance', 'Pavillon visible'], 'rules' => 'Restrictions mouillage zones protégées'],
                ['name' => 'Italie', 'flag' => '🇮🇹', 'docs' => ['Assurance', 'Taxe touristique'], 'rules' => 'Annonce VHF en approche port'],
            ],
            'emergency' => [
                ['label' => 'Urgence générale', 'value' => '112'],
                ['label' => 'Secours maritime (FR)', 'value' => '196'],
                ['label' => 'VHF', 'value' => 'Canal 16'],
            ],
        ];
    }
}
