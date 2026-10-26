<?php

namespace App\Services\Routing;

use App\Models\Anchorage;
use App\Models\Boat;
use App\Models\Cruise;
use App\Models\CruiseRoute;
use App\Models\Port;
use App\Services\Marine\EmodnetBathymetryService;
use App\Services\Navigation\InlandRouteService;
use App\Services\Navigation\PortCoordinates;
use App\Services\Navigation\SeaRouteService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Génère et persiste plusieurs routes candidates pour une croisière.
 *
 * Le tracé maritime vient de SeaRouteService ; les escales sont choisies parmi
 * les ports et mouillages réels proches du tracé, filtrés par compatibilité
 * bateau, puis chaque route reçoit un score pondéré.
 */
class RouteGenerator
{
    /**
     * Rayons successifs de recherche d'escale, en milles nautiques.
     *
     * @var list<float>
     */
    private const SEARCH_RADII_NM = [15.0, 30.0, 60.0];

    public function __construct(
        private readonly PortCoordinates $ports,
        private readonly SeaRouteService $router,
        private readonly InlandRouteService $inland,
        private readonly StopFinder $stops,
        private readonly BoatCompatibilityService $compatibility,
        private readonly WeatherAnalyzer $weather,
        private readonly RouteScorer $scorer,
        private readonly EmodnetBathymetryService $bathymetry,
        private readonly RouteGeometry $routeGeometry,
    ) {}

    /**
     * Recalcule les routes candidates et les enregistre.
     *
     * @return list<CruiseRoute>
     */
    public function generate(Cruise $cruise): array
    {
        $departure = $this->ports->find($cruise->departure_port, $cruise->departure_place);
        $destination = $this->ports->find($cruise->destination_port, $cruise->destination_place);

        if ($departure === null || $destination === null) {
            return [];
        }

        $geometry = array_values($this->geometry($cruise, $departure, $destination));

        if (count($geometry) < 2) {
            return [];
        }

        $routes = [];

        foreach (RouteScenario::cases() as $scenario) {
            $routes[] = $this->buildScenario($cruise, $scenario, $geometry);
        }

        // La meilleure route devient la route sélectionnée par défaut.
        usort($routes, fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        return DB::transaction(function () use ($cruise, $routes): array {
            $cruise->routes()->delete();
            $saved = [];

            foreach ($routes as $index => $route) {
                $model = $cruise->routes()->create([
                    'scenario' => $route['scenario'],
                    'label' => $route['label'],
                    'is_selected' => $index === 0,
                    'distance_nm' => $route['distance_nm'],
                    'duration_days' => $route['duration_days'],
                    'stops' => count($route['segments']),
                    'score' => $route['score'],
                    'score_breakdown' => $route['breakdown'],
                    'geometry' => $route['geometry'],
                    'computed_at' => now(),
                ]);

                foreach ($route['segments'] as $position => $segment) {
                    $model->segments()->create($segment + ['position' => $position + 1]);
                }

                $saved[] = $model->load('segments');
            }

            return $saved;
        });
    }

    /**
     * Tracé du trajet : réseau de voies navigables pour les croisières
     * fluviales, moteur de route maritime sinon. Quand la mer ne mène pas au
     * port demandé (port en rivière, par exemple), le réseau fluvial sert de
     * repli.
     *
     * @param  array{latitude: float, longitude: float}  $departure
     * @param  array{latitude: float, longitude: float}  $destination
     * @return array<int, array{latitude: float, longitude: float}>
     */
    private function geometry(Cruise $cruise, array $departure, array $destination): array
    {
        if ($cruise->navigation_area === 'inland') {
            return $this->inland->route($departure, $destination)['geometry'] ?? [];
        }

        try {
            return $this->router->route($departure, $destination);
        } catch (\RuntimeException) {
            return $this->inland->route($departure, $destination)['geometry'] ?? [];
        }
    }

    /**
     * @param  list<array{latitude: float, longitude: float}>  $geometry
     * @return array<string, mixed>
     */
    private function buildScenario(Cruise $cruise, RouteScenario $scenario, array $geometry): array
    {
        $boat = $cruise->boat instanceof Boat ? $cruise->boat : null;
        $speed = $boat === null ? 6.0 : (float) $boat->avg_speed;
        $maxLegDistance = $scenario->maxHoursPerDay() * $speed;

        $waypoints = $this->routeGeometry->split($geometry, $maxLegDistance);

        // 1. Choisir une escale réelle autour de chaque point de passage théorique.
        $stops = [];

        foreach ($waypoints as $index => $waypoint) {
            $isLast = $index === count($waypoints) - 1;
            $stop = $isLast
                ? ['name' => $cruise->destination_port, 'port' => null, 'anchorage' => null, 'score' => 100]
                : $this->pickStop($boat, $waypoint['point'], $scenario);

            $place = $stop['anchorage'] ?? $stop['port'];
            $stop['point'] = $place === null || $isLast
                ? $waypoint['point']
                : ['latitude' => (float) $place->latitude, 'longitude' => (float) $place->longitude];
            $stop['index'] = $waypoint['index'];
            $stops[] = $stop;
        }

        // 2. Tracer chaque étape par la mer, d'escale réelle en escale réelle.
        $legs = $this->legGeometries($cruise, $geometry, $stops);

        // 3. Construire les segments jour par jour sur ces tracés.
        $segments = [];
        $weatherScores = [];
        $boatScores = [];
        $departureAt = Carbon::parse($cruise->departure_date)->setTime(8, 0);
        $day = 1;
        $totalDistance = 0.0;
        $routeGeometry = [];

        $previous = ['name' => $cruise->departure_port, 'point' => $geometry[0]];

        foreach ($stops as $index => $stop) {
            $legGeometry = $legs[$index];
            $distance = $this->routeGeometry->distance($legGeometry);
            $totalDistance += $distance;

            foreach ($legGeometry as $point) {
                if ($routeGeometry === [] || $routeGeometry[count($routeGeometry) - 1] !== $point) {
                    $routeGeometry[] = $point;
                }
            }

            try {
                $minDepth = $this->bathymetry->minDepthAlongPath($legGeometry);
            } catch (\Throwable) {
                $minDepth = null;
            }

            $hours = $speed > 0 ? $distance / $speed : null;
            $legWeather = $this->weather->scoreLeg(
                $stop['point']['latitude'],
                $stop['point']['longitude'],
                $departureAt->copy()->addDays($day - 1),
                (int) ceil($hours ?? 6),
            );

            $weatherScores[] = $legWeather['score'];
            $boatScores[] = $stop['score'];

            $segments[] = [
                'day' => $day,
                'from_name' => $previous['name'],
                'to_name' => $stop['name'],
                'from_latitude' => $previous['point']['latitude'],
                'from_longitude' => $previous['point']['longitude'],
                'to_latitude' => $stop['point']['latitude'],
                'to_longitude' => $stop['point']['longitude'],
                'port_id' => $stop['port']?->id,
                'anchorage_id' => $stop['anchorage']?->id,
                'distance_nm' => round($distance, 2),
                'duration_hours' => $hours === null ? null : round($hours, 2),
                'min_depth_meters' => $minDepth,
                'overnight' => $stop['anchorage'] !== null ? 'Mouillage' : 'Port',
                'weather_score' => $legWeather['score'],
                'weather_snapshot' => [
                    'label' => $legWeather['label'],
                    'detail' => $legWeather['detail'],
                    'wind_knots' => $legWeather['wind_knots'],
                    'wave_height_meters' => $legWeather['wave_height_meters'],
                    'available' => $legWeather['available'],
                    'coverage_percent' => $legWeather['coverage_percent'],
                ],
                'note' => $this->segmentNote($stop),
            ];

            $previous = ['name' => $stop['name'], 'point' => $stop['point']];
            $day++;
        }

        $scored = $this->scorer->score([
            'weather' => $weatherScores === [] ? 50 : (int) round(array_sum($weatherScores) / count($weatherScores)),
            'boat' => $boatScores === [] ? 50 : (int) round(array_sum($boatScores) / count($boatScores)),
            'safety' => $this->safetyScore($segments),
            'comfort' => $scenario->comfortBias(),
            'community' => $this->communityScore($segments),
        ]);

        return [
            'scenario' => $scenario->value,
            'label' => $scenario->label(),
            'distance_nm' => (int) round($totalDistance),
            'duration_days' => max(1, count($segments)),
            'segments' => $segments,
            'geometry' => $routeGeometry,
            'score' => $scored['score'],
            'breakdown' => $scored['breakdown'],
        ];
    }

    /**
     * Tracé de chaque étape. En mer, le moteur relie les escales réelles ;
     * sinon (fluvial, moteur indisponible), la géométrie d'origine est
     * découpée entre les points de passage, en raccordant les extrémités aux
     * escales retenues.
     *
     * @param  list<array{latitude: float, longitude: float}>  $geometry
     * @param  list<array{point: array{latitude: float, longitude: float}, index: int}>  $stops
     * @return list<list<array{latitude: float, longitude: float}>>
     */
    private function legGeometries(Cruise $cruise, array $geometry, array $stops): array
    {
        if ($cruise->navigation_area !== 'inland') {
            $chain = [
                $geometry[0],
                ...array_map(fn (array $stop): array => $stop['point'], $stops),
            ];

            try {
                $legs = $this->router->legs($chain);

                if (count($legs) === count($stops)) {
                    return $this->routeGeometry->stitch($legs, $chain);
                }
            } catch (\RuntimeException) {
                // Repli sur la géométrie d'origine ci-dessous.
            }
        }

        $legs = [];
        $previousIndex = 0;
        $previousPoint = $geometry[0];

        foreach ($stops as $stop) {
            $slice = array_slice($geometry, $previousIndex, max(1, $stop['index'] - $previousIndex) + 1);
            $slice[0] = $previousPoint;
            $slice[count($slice) - 1] = $stop['point'];
            $legs[] = $slice;
            $previousIndex = $stop['index'];
            $previousPoint = $stop['point'];
        }

        return $legs;
    }

    /**
     * Choisit l'escale la plus adaptée autour d'un point de passage.
     *
     * @param  array{latitude: float, longitude: float}  $point
     * @return array{name: string, port: Port|null, anchorage: Anchorage|null, score: int}
     */
    private function pickStop(?Boat $boat, array $point, RouteScenario $scenario): array
    {
        // Le rayon s'élargit tant qu'aucune escale n'est trouvée : au large, le
        // point de passage théorique peut être loin de toute côte.
        $port = null;
        $anchorage = null;

        foreach (self::SEARCH_RADII_NM as $radius) {
            $port = $this->stops->portsNear($point['latitude'], $point['longitude'], $radius, 5)->first();
            $anchorage = $this->stops->anchoragesNear($point['latitude'], $point['longitude'], $radius, 5)->first();

            if ($port !== null || $anchorage !== null) {
                break;
            }
        }

        // « Découverte » privilégie les mouillages, les autres scénarios les ports.
        $preferAnchorage = $scenario === RouteScenario::Discovery && $anchorage !== null;

        if ($preferAnchorage || ($port === null && $anchorage !== null)) {
            $score = $boat === null ? 75 : $this->compatibility->forAnchorage($boat, $anchorage)['score'];

            return ['name' => $anchorage->name, 'port' => null, 'anchorage' => $anchorage, 'score' => $score];
        }

        if ($port !== null) {
            $score = $boat === null ? 75 : $this->compatibility->forPort($boat, $port)['score'];

            return ['name' => $port->name, 'port' => $port, 'anchorage' => null, 'score' => $score];
        }

        return [
            'name' => sprintf('%.3f, %.3f', $point['latitude'], $point['longitude']),
            'port' => null,
            'anchorage' => null,
            'score' => 50,
        ];
    }

    /**
     * Sécurité : pénalise les étapes sans escale identifiée et les longues journées.
     *
     * @param  list<array<string, mixed>>  $segments
     */
    private function safetyScore(array $segments): int
    {
        if ($segments === []) {
            return 50;
        }

        $score = 100;

        foreach ($segments as $segment) {
            if ($segment['port_id'] === null && $segment['anchorage_id'] === null) {
                $score -= 8;
            }

            if (($segment['duration_hours'] ?? 0) > 10) {
                $score -= 5;
            }
        }

        return max(0, min(100, $score));
    }

    /**
     * Communauté : part des escales documentées par des avis.
     *
     * @param  list<array<string, mixed>>  $segments
     */
    private function communityScore(array $segments): int
    {
        $portIds = array_filter(array_column($segments, 'port_id'));
        $anchorageIds = array_filter(array_column($segments, 'anchorage_id'));

        if ($portIds === [] && $anchorageIds === []) {
            return 50;
        }

        $reviewed = 0;

        if ($portIds !== []) {
            $reviewed += Port::query()->whereIn('id', $portIds)->has('publicReviews')->count();
        }

        if ($anchorageIds !== []) {
            $reviewed += Anchorage::query()->whereIn('id', $anchorageIds)->has('publicReviews')->count();
        }

        $total = count($portIds) + count($anchorageIds);

        return (int) round(50 + 50 * ($reviewed / max(1, $total)));
    }

    /** @param array{name: string, port: Port|null, anchorage: Anchorage|null, score: int} $stop */
    private function segmentNote(array $stop): ?string
    {
        if ($stop['anchorage'] !== null) {
            return $stop['anchorage']->description ?? 'Mouillage issu des données OpenStreetMap, à confirmer avec la cartographie officielle.';
        }

        if ($stop['port'] !== null) {
            $services = $stop['port']->services();

            return $services === []
                ? 'Port issu des données OpenStreetMap, services à confirmer auprès de la capitainerie.'
                : 'Services signalés : '.implode(', ', $services).'.';
        }

        return null;
    }
}
