<?php

namespace App\Data\Mock;

use App\Data\Mock\Contracts\MockDataProviderInterface;

class CruiseMockDataProvider implements MockDataProviderInterface
{
    public function toArray(): array
    {
        return $this->find('tour-corse-2027');
    }

    public function find(string $slug): array
    {
        if ($slug !== 'tour-corse-2027') {
            throw new \InvalidArgumentException("Croisière mock inconnue : {$slug}");
        }

        return $this->buildCruise($slug);
    }

    private function buildCruise(string $slug): array
    {
        return [
            'slug' => $slug,
            'title' => 'Tour Corse été 2027',
            'route' => 'Biarritz → Bonifacio',
            'departure_port' => 'Biarritz',
            'arrival_port' => 'Bonifacio',
            'departure_date' => '15 juillet 2027',
            'return_date' => '25 juillet 2027',
            'duration' => '10 jours',
            'status' => 'Préparation',
            'style' => 'Découverte',
            'crew' => 4,
            'experience' => 'Intermédiaire',
            'progress' => 68,
            'miles' => 350,
            'stops' => 8,
            'readiness' => 85,
            'scenarios' => [
                ['id' => 'decouverte', 'label' => 'Découverte', 'active' => true, 'miles' => 350, 'stops' => 8, 'days' => 10],
                ['id' => 'confort', 'label' => 'Confort', 'active' => false, 'miles' => 320, 'stops' => 10, 'days' => 10],
                ['id' => 'rapide', 'label' => 'Rapide', 'active' => false, 'miles' => 290, 'stops' => 5, 'days' => 8],
            ],
            'plan_b' => [
                'issue' => 'Houle en hausse prévue sur l’étape Bilbao → Santander.',
                'original' => 'Bilbao → Santander',
                'alternative' => 'Bilbao → Laredo',
                'difference' => '+ 25 min de navigation',
                'reasons' => [
                    'Mouillage mieux protégé de la houle du nord-ouest.',
                    'Étape ramenée à 4h15, adaptée à votre équipage.',
                    'Port refuge et ravitaillement disponibles.',
                ],
            ],
            'legs' => [
                [
                    'day' => 1,
                    'label' => 'J1',
                    'from' => 'Biarritz',
                    'to' => 'Saint-Jean-de-Luz',
                    'distance' => '18 M',
                    'duration' => '4h30',
                    'departure' => '08h00',
                    'weather' => 'Favorable',
                    'weather_detail' => 'Vent léger, houle faible',
                    'status' => 'done',
                    'anchorage_slug' => null,
                ],
                [
                    'day' => 2,
                    'label' => 'J2',
                    'from' => 'Saint-Jean-de-Luz',
                    'to' => 'Bilbao',
                    'distance' => '42 M',
                    'duration' => '8h15',
                    'departure' => '07h30',
                    'weather' => 'Correct',
                    'weather_detail' => 'Vent modéré NE, navigation confortable',
                    'status' => 'current',
                    'anchorage_slug' => null,
                ],
                [
                    'day' => 3,
                    'label' => 'J3',
                    'from' => 'Bilbao',
                    'to' => 'Santander',
                    'distance' => '65 M',
                    'duration' => '11h00',
                    'departure' => '06h00',
                    'weather' => 'Attention',
                    'weather_detail' => 'Houle en augmentation l&apos;après-midi',
                    'status' => 'upcoming',
                    'anchorage_slug' => null,
                ],
                [
                    'day' => 5,
                    'label' => 'J5',
                    'from' => 'Gijón',
                    'to' => 'Cala Macarella',
                    'distance' => '180 M',
                    'duration' => '2 jours',
                    'departure' => '05h30',
                    'weather' => 'Très favorable',
                    'weather_detail' => 'Fenêtre météo idéale',
                    'status' => 'upcoming',
                    'anchorage_slug' => 'cala-macarella',
                ],
                [
                    'day' => 8,
                    'label' => 'J8',
                    'from' => 'Cala Macarella',
                    'to' => 'Bonifacio',
                    'distance' => '95 M',
                    'duration' => '16h00',
                    'departure' => '07h00',
                    'weather' => 'Favorable',
                    'weather_detail' => 'Arrivée au coucher de soleil possible',
                    'status' => 'upcoming',
                    'anchorage_slug' => 'roccapina',
                ],
            ],
            'chart' => [
                'route' => [
                    ['x' => 8, 'y' => 72],
                    ['x' => 12, 'y' => 70],
                    ['x' => 18, 'y' => 65],
                    ['x' => 28, 'y' => 58],
                    ['x' => 42, 'y' => 52],
                    ['x' => 55, 'y' => 48],
                    ['x' => 68, 'y' => 42],
                    ['x' => 78, 'y' => 38],
                    ['x' => 88, 'y' => 35],
                ],
                'markers' => [
                    ['id' => 'biarritz', 'x' => 8, 'y' => 72, 'label' => 'Biarritz', 'type' => 'port'],
                    ['id' => 'saint-jean', 'x' => 12, 'y' => 70, 'label' => 'St-Jean-de-Luz', 'type' => 'port'],
                    ['id' => 'bilbao', 'x' => 18, 'y' => 65, 'label' => 'Bilbao', 'type' => 'port'],
                    ['id' => 'cala-macarella', 'x' => 68, 'y' => 42, 'label' => 'Cala Macarella', 'type' => 'anchorage'],
                    ['id' => 'roccapina', 'x' => 78, 'y' => 38, 'label' => 'Roccapina', 'type' => 'anchorage'],
                    ['id' => 'bonifacio', 'x' => 88, 'y' => 35, 'label' => 'Bonifacio', 'type' => 'port'],
                    ['id' => 'danger-1', 'x' => 35, 'y' => 55, 'label' => 'Zone militaire', 'type' => 'danger'],
                ],
                'currents_updated_at' => 'Prévision pour 08h00',
                'currents' => [
                    ['id' => 'current-1', 'x' => 14, 'y' => 28, 'direction' => 28, 'speed' => 0.6, 'label' => 'NE'],
                    ['id' => 'current-2', 'x' => 30, 'y' => 30, 'direction' => 18, 'speed' => 0.8, 'label' => 'NNE'],
                    ['id' => 'current-3', 'x' => 46, 'y' => 26, 'direction' => 42, 'speed' => 0.5, 'label' => 'NE'],
                    ['id' => 'current-4', 'x' => 62, 'y' => 25, 'direction' => 55, 'speed' => 0.7, 'label' => 'ENE'],
                    ['id' => 'current-5', 'x' => 18, 'y' => 50, 'direction' => 20, 'speed' => 0.9, 'label' => 'NNE'],
                    ['id' => 'current-6', 'x' => 40, 'y' => 54, 'direction' => 35, 'speed' => 1.1, 'label' => 'NE'],
                    ['id' => 'current-7', 'x' => 58, 'y' => 50, 'direction' => 48, 'speed' => 0.7, 'label' => 'ENE'],
                    ['id' => 'current-8', 'x' => 72, 'y' => 57, 'direction' => 60, 'speed' => 0.5, 'label' => 'ENE'],
                    ['id' => 'current-9', 'x' => 28, 'y' => 76, 'direction' => 12, 'speed' => 0.6, 'label' => 'NNE'],
                    ['id' => 'current-10', 'x' => 48, 'y' => 78, 'direction' => 25, 'speed' => 0.8, 'label' => 'NE'],
                ],
            ],
        ];
    }
}
