<?php

namespace App\Services\Itinerary;

use App\Models\Cruise;
use App\Services\Navigation\InlandRouteService;
use App\Services\Navigation\PortCoordinates;
use App\Services\Navigation\SeaRouteService;

class DraftItineraryPlanner
{
    public function __construct(
        private readonly PortCoordinates $ports,
        private readonly CruiseDurationEstimator $durationEstimator,
        private readonly SeaRouteService $router,
        private readonly InlandRouteService $inlandRouter,
    ) {}

    /** @return array<string, mixed> */
    public function plan(Cruise $cruise): array
    {
        if ($this->isBrittanyTour($cruise)) {
            return $this->brittanyTour($cruise);
        }

        if ($this->isQuebecPassage($cruise)) {
            return $this->quebecPassage($cruise);
        }

        return $this->directPlan($cruise);
    }

    private function isBrittanyTour(Cruise $cruise): bool
    {
        return $this->normalize($cruise->departure_port) === 'saint-malo'
            && $this->normalize($cruise->destination_port) === 'vannes';
    }

    private function isQuebecPassage(Cruise $cruise): bool
    {
        return $this->normalize($cruise->departure_port) === 'saint-malo'
            && $this->normalize($cruise->destination_port) === 'quebec';
    }

    /** @return array<string, mixed> */
    private function quebecPassage(Cruise $cruise): array
    {
        $recommendedDays = match ($cruise->navigation_style) {
            'Rapide' => 24,
            'Découverte' => 33,
            'Famille' => 36,
            default => 28,
        };
        $legs = [
            $this->leg(1, 'Saint-Malo', 'Horta', '1 180 M', '8 jours', 'Fenêtre météo', 'Port', '160 €', 'Première traversée hauturière vers les Açores, avec escale de repos et avitaillement.'),
            $this->leg(10, 'Horta', 'Halifax', '1 450 M', '10 jours', 'Fenêtre météo', 'Port', '220 €', 'Traversée Atlantique ouest à préparer avec une fenêtre météo stable et des quarts organisés.'),
            $this->leg(22, 'Halifax', 'Québec', '700 M', '5 jours', 'Selon marées', 'Port', '300 €', 'Remontée du Saint-Laurent à planifier avec les contraintes de marée, trafic et pilotage.'),
        ];

        return [
            'miles' => 3330,
            'stops' => 3,
            'readiness' => 18,
            'scenarios' => [[
                'id' => 'atlantique-ouest', 'label' => 'Traversée Atlantique via les Açores', 'active' => true,
                'miles' => 3330, 'stops' => 3, 'days' => $recommendedDays,
            ]],
            'legs' => $legs,
            'budget' => [
                'ports' => 680, 'fuel' => 550, 'provisions' => 1120, 'total' => 2350,
                'note' => 'Estimation à affiner selon le bateau, l’équipage, les réserves de sécurité et les tarifs d’escale.',
            ],
            'plan_b' => [
                'issue' => 'La traversée impose une fenêtre météo et une autonomie validées avant chaque départ.',
                'original' => 'Horta → Halifax',
                'alternative' => 'Prolonger l’escale à Horta jusqu’à la prochaine fenêtre favorable',
                'difference' => 'Aucun mille supplémentaire',
                'reasons' => ['Port et services de grande croisière disponibles.', 'Réserve de sécurité conservée.', 'Départ reporté sans navigation de nuit côtière imposée.'],
            ],
            'duration_warning' => $cruise->duration_days < $recommendedDays
                ? "Le style {$cruise->navigation_style} recommande au minimum {$recommendedDays} jours pour cette traversée, hors aléas météo."
                : null,
        ];
    }

    /** @return array<string, mixed> */
    private function brittanyTour(Cruise $cruise): array
    {
        $recommendedDays = match ($cruise->navigation_style) {
            'Rapide' => 11,
            'Découverte' => 18,
            'Famille' => 19,
            default => 15,
        };
        $legs = [
            $this->leg(1, 'Saint-Malo', 'Saint-Quay-Portrieux', '34 M', '5h40', '07h00', 'Port', '42 €', 'Premier abri avant de longer la côte nord.'),
            $this->leg(3, 'Saint-Quay-Portrieux', 'Trébeurden', '36 M', '6h00', '07h30', 'Port', '36 €', 'Escale protégée, adaptée à une navigation de jour.'),
            $this->leg(5, 'Trébeurden', 'Aber-Wrac’h', '42 M', '7h00', '06h30', 'Mouillage', '0 €', 'Mouillage abrité et point de départ pour le Finistère.'),
            $this->leg(7, 'Aber-Wrac’h', 'Camaret-sur-Mer', '38 M', '6h20', '07h00', 'Port', '38 €', 'Refuge avant le passage du Raz de Sein.'),
            $this->leg(9, 'Camaret-sur-Mer', 'Audierne', '28 M', '4h40', '08h00', 'Mouillage', '0 €', 'Étape courte pour attendre une fenêtre favorable au Raz.'),
            $this->leg(11, 'Audierne', 'Concarneau', '42 M', '7h00', '06h30', 'Port', '45 €', 'Port complet pour ravitaillement et repos de l’équipage.'),
            $this->leg(13, 'Concarneau', 'Vannes', '53 M', '8h50', '06h00', 'Port', '48 €', 'Dernière étape avec arrivée prévue avant la nuit.'),
        ];

        return [
            'miles' => 273,
            'stops' => count($legs),
            'readiness' => 42,
            'scenarios' => [
                ['id' => 'bretagne-equilibre', 'label' => 'Tour Bretagne '.$cruise->navigation_style, 'active' => true, 'miles' => 273, 'stops' => count($legs), 'days' => $recommendedDays],
            ],
            'legs' => $legs,
            'budget' => [
                'ports' => 209,
                'fuel' => 95,
                'provisions' => 280,
                'total' => 584,
                'note' => 'Estimation pour 2 personnes. Les tarifs portuaires sont à confirmer avant réservation.',
            ],
            'plan_b' => [
                'issue' => 'Le passage du Raz de Sein demande une météo et une marée favorables.',
                'original' => 'Camaret-sur-Mer → Audierne',
                'alternative' => 'Nuit supplémentaire à Camaret-sur-Mer',
                'difference' => '0 mille additionnel',
                'reasons' => ['Port refuge disponible.', 'Départ décalé sur une fenêtre plus confortable.', 'Aucune étape de nuit imposée.'],
            ],
            'duration_warning' => $cruise->duration_days < $recommendedDays
                ? "Le style {$cruise->navigation_style} recommande au minimum {$recommendedDays} jours pour cet itinéraire."
                : null,
        ];
    }

    /** @return array<string, mixed> */
    private function directPlan(Cruise $cruise): array
    {
        $departure = $this->ports->find($cruise->departure_port);
        $destination = $this->ports->find($cruise->destination_port);

        if ($departure === null || $destination === null) {
            return $this->unlocatedPlan($cruise);
        }

        $route = $this->maritimeRoute($departure, $destination);

        if (! $route['available']) {
            $inlandRoute = $this->inlandRouter->route($departure, $destination);

            if ($inlandRoute !== null) {
                return $this->inlandPlan($cruise, $inlandRoute);
            }

            return $this->waterwayPlan($cruise);
        }

        $miles = $route['miles'];
        $recommendedDays = $this->durationEstimator->estimate(
            $cruise->departure_port,
            $cruise->destination_port,
            $cruise->navigation_style,
        );

        return [
            'miles' => $miles,
            'stops' => 0,
            'readiness' => 15,
            'scenarios' => [[
                'id' => 'maritime-route',
                'label' => 'Route maritime calculée',
                'active' => true,
                'miles' => $miles,
                'stops' => 0,
                'days' => $recommendedDays,
            ]],
            'legs' => [$this->leg(
                1,
                $cruise->departure_port,
                $cruise->destination_port,
                number_format($miles, 0, ',', ' ').' M',
                "Environ {$recommendedDays} jours",
                'Fenêtre météo à valider',
                'Port d’arrivée',
                'À confirmer auprès du port',
                'Distance calculée sur le réseau maritime. Ajoutez ou validez les escales avant le départ.',
            )],
            'plan_b' => [
                'issue' => 'Aucune escale intermédiaire n’est encore validée pour cette route.',
                'original' => $cruise->departure_port.' → '.$cruise->destination_port,
                'alternative' => 'Définir des escales sur la carte',
                'difference' => 'À calculer selon les ports retenus',
                'reasons' => ['La route ne traverse pas les terres.', 'Les conditions météo restent à consulter avant chaque départ.', 'Les tarifs portuaires doivent être confirmés auprès des capitaineries.'],
            ],
            'duration_warning' => $cruise->duration_days < $recommendedDays
                ? "Le style {$cruise->navigation_style} recommande au minimum {$recommendedDays} jours pour cette distance maritime."
                : null,
        ];
    }

    /** @param array{geometry: array<int, array{latitude: float, longitude: float}>, miles: int, locks: int} $route */
    /** @return array<string, mixed> */
    private function inlandPlan(Cruise $cruise, array $route): array
    {
        $hours = max(1, (int) ceil($route['miles'] / 4 + $route['locks'] * 0.5));
        $recommendedDays = max(1, (int) ceil($hours / 7));
        $duration = $hours >= 7 ? "Environ {$recommendedDays} jour(s)" : "Environ {$hours} h";

        return [
            'miles' => $route['miles'],
            'stops' => 0,
            'readiness' => 10,
            'route_mode' => 'inland',
            'route_geometry' => $route['geometry'],
            'scenarios' => [[
                'id' => 'inland-route', 'label' => 'Route fluviale calculee', 'active' => true,
                'miles' => $route['miles'], 'stops' => 0, 'days' => $recommendedDays,
            ]],
            'legs' => [$this->leg(1, $cruise->departure_port, $cruise->destination_port, number_format($route['miles'], 0, ',', ' ').' M', $duration, 'Horaires des ecluses a verifier', 'Port d\'arrivee', 'A confirmer', 'Distance calculee sur le reseau fluvial importe. Verifiez les avis VNF, les marees et les horaires avant le depart.')],
            'plan_b' => [
                'issue' => $route['locks'] > 0 ? "{$route['locks']} ecluse(s) sont prevues sur cette route." : 'Les avis de navigation doivent etre verifies avant le depart.',
                'original' => $cruise->departure_port.' -> '.$cruise->destination_port,
                'alternative' => 'Consulter les avis VNF et les horaires d\'ecluses',
                'difference' => 'Selon disponibilite des ouvrages et marees',
                'reasons' => ['Le trace suit le reseau fluvial importe.', 'Aucune ligne terrestre n\'est dessinee.', 'Les contraintes du bateau restent a valider.'],
            ],
            'duration_warning' => $cruise->duration_days < $recommendedDays
                ? "La voie fluviale recommande au minimum {$recommendedDays} jour(s), hors attente aux ecluses."
                : null,
        ];
    }

    /** @return array<string, mixed> */
    private function unlocatedPlan(Cruise $cruise): array
    {
        return [
            'miles' => 0,
            'stops' => 0,
            'readiness' => 0,
            'scenarios' => [['id' => 'draft', 'label' => 'Port à localiser', 'active' => true, 'miles' => 0, 'stops' => 0, 'days' => $cruise->duration_days]],
            'legs' => [$this->leg(1, $cruise->departure_port, $cruise->destination_port, 'Port à localiser', 'À calculer', 'À conseiller', 'À définir', 'À confirmer', 'Choisissez un port référencé dans Explorer ou renseignez ses coordonnées.')],
            'plan_b' => ['issue' => 'La route ne peut pas être calculée sans les coordonnées des deux ports.', 'original' => $cruise->departure_port.' → '.$cruise->destination_port, 'alternative' => 'Rechercher le port dans Explorer', 'difference' => 'En attente des coordonnées', 'reasons' => ['Les coordonnées sont nécessaires au moteur maritime.']],
        ];
    }

    /** @return array<string, mixed> */
    private function waterwayPlan(Cruise $cruise): array
    {
        return [
            'miles' => 0,
            'stops' => 0,
            'readiness' => 0,
            'route_available' => false,
            'scenarios' => [['id' => 'waterway', 'label' => 'Voie d’eau à vérifier', 'active' => true, 'miles' => 0, 'stops' => 0, 'days' => $cruise->duration_days]],
            'legs' => [$this->leg(1, $cruise->departure_port, $cruise->destination_port, 'Voie d’eau à calculer', 'À confirmer', 'Selon écluses et marées', 'À définir', 'À confirmer', 'Cette destination nécessite un moteur de voies navigables et les données d’écluses, indisponibles dans le routeur maritime.')],
            'plan_b' => ['issue' => 'Le routeur maritime ne fournit pas de chenal navigable jusqu’à cette destination.', 'original' => $cruise->departure_port.' → '.$cruise->destination_port, 'alternative' => 'Préparer la voie fluviale séparément', 'difference' => 'Dépend des écluses, marées et horaires', 'reasons' => ['Aucune ligne terrestre n’est affichée.', 'Les instructions fluviales doivent être vérifiées avec les documents locaux.']],
        ];
    }

    /** @param array{latitude: float, longitude: float} $departure @param array{latitude: float, longitude: float} $destination */
    /** @return array{available: bool, miles: int} */
    private function maritimeRoute(array $departure, array $destination): array
    {
        try {
            $path = $this->router->route($departure, $destination);
            if (count($path) < 2) {
                return ['available' => false, 'miles' => 0];
            }

            $miles = 0.0;

            foreach (array_chunk($path, 2, true) as $segment) {
                $segment = array_values($segment);
                if (count($segment) === 2) {
                    $miles += $this->nauticalMiles($segment[0], $segment[1]);
                }
            }

            return ['available' => $miles > 0, 'miles' => (int) round($miles)];
        } catch (\RuntimeException) {
            return ['available' => false, 'miles' => 0];
        }
    }

    /** @param array{latitude: float, longitude: float} $origin @param array{latitude: float, longitude: float} $destination */
    private function nauticalMiles(array $origin, array $destination): float
    {
        $latitude = deg2rad($destination['latitude'] - $origin['latitude']);
        $longitude = deg2rad($destination['longitude'] - $origin['longitude']);
        $a = sin($latitude / 2) ** 2
            + cos(deg2rad($origin['latitude'])) * cos(deg2rad($destination['latitude'])) * sin($longitude / 2) ** 2;

        return 3440.065 * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /** @return array<string, mixed> */
    private function leg(int $day, string $from, string $to, string $distance, string $duration, string $departure, string $overnight, string $estimatedCost, string $why): array
    {
        return [
            'day' => $day,
            'label' => 'J'.$day,
            'from' => $from,
            'to' => $to,
            'distance' => $distance,
            'duration' => $duration,
            'departure' => $departure,
            'weather' => 'À confirmer',
            'weather_detail' => 'Prévision marine à actualiser avant le départ.',
            'status' => 'upcoming',
            'anchorage_slug' => null,
            'overnight' => $overnight,
            'estimated_cost' => $estimatedCost,
            'why' => $why,
        ];
    }

    private function normalize(string $value): string
    {
        return str($value)->ascii()->lower()->trim()->toString();
    }
}
