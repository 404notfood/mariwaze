<?php

namespace Tests\Feature;

use App\Exceptions\MarineConditionsUnavailable;
use App\Services\Marine\Contracts\WeatherProviderInterface;
use App\Services\Routing\RouteScenario;
use App\Services\Routing\RouteScorer;
use App\Services\Routing\WeatherAnalyzer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteScoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_weighted_score_follows_the_documented_formula(): void
    {
        $result = app(RouteScorer::class)->score([
            'weather' => 100, 'boat' => 100, 'safety' => 100, 'comfort' => 100, 'community' => 100,
        ]);

        $this->assertSame(100, $result['score']);
    }

    public function test_weather_carries_the_heaviest_weight(): void
    {
        $scorer = app(RouteScorer::class);

        $badWeather = $scorer->score(['weather' => 0, 'boat' => 100, 'safety' => 100, 'comfort' => 100, 'community' => 100]);
        $badComfort = $scorer->score(['weather' => 100, 'boat' => 100, 'safety' => 100, 'comfort' => 0, 'community' => 100]);

        // 35 % de pénalité contre 10 % : la météo doit peser davantage.
        $this->assertLessThan($badComfort['score'], $badWeather['score']);
        $this->assertSame(65, $badWeather['score']);
        $this->assertSame(90, $badComfort['score']);
    }

    public function test_the_breakdown_details_each_component(): void
    {
        $result = app(RouteScorer::class)->score(['weather' => 80, 'boat' => 60]);

        $this->assertSame(80, $result['breakdown']['weather']['value']);
        $this->assertSame(0.35, $result['breakdown']['weather']['weight']);
        // Les composants absents retombent sur une valeur neutre.
        $this->assertSame(50, $result['breakdown']['safety']['value']);
    }

    public function test_calm_weather_scores_higher_than_a_gale(): void
    {
        $analyzer = new WeatherAnalyzer($this->weatherReturning([
            ['time' => '2026-08-01T08:00', 'wind_knots' => 8.0, 'gusts_knots' => 12.0, 'wave_height_meters' => 0.4],
        ]));

        $calm = $analyzer->scoreLeg(43.0, 5.0, now(), 1);

        $gale = (new WeatherAnalyzer($this->weatherReturning([
            ['time' => '2026-08-01T08:00', 'wind_knots' => 35.0, 'gusts_knots' => 45.0, 'wave_height_meters' => 3.5],
        ])))->scoreLeg(43.0, 5.0, now(), 1);

        $this->assertSame(100, $calm['score']);
        $this->assertSame('Très favorable', $calm['label']);
        $this->assertSame(0, $gale['score']);
        $this->assertSame('Déconseillé', $gale['label']);
    }

    public function test_an_unavailable_forecast_returns_a_neutral_score(): void
    {
        $provider = new class implements WeatherProviderInterface
        {
            public function conditions(float $latitude, float $longitude): array
            {
                throw new MarineConditionsUnavailable('indisponible');
            }

            public function forecast(float $latitude, float $longitude, \DateTimeInterface $from, int $hours = 24): array
            {
                throw new MarineConditionsUnavailable('indisponible');
            }
        };

        $score = (new WeatherAnalyzer($provider))->scoreLeg(43.0, 5.0, now());

        $this->assertFalse($score['available']);
        $this->assertSame(50, $score['score']);
    }

    public function test_scenarios_define_distinct_daily_limits(): void
    {
        $this->assertGreaterThan(
            RouteScenario::Discovery->maxHoursPerDay(),
            RouteScenario::Fast->maxHoursPerDay(),
        );
        $this->assertSame('Découverte', RouteScenario::Discovery->label());
    }

    public function test_missing_measurements_cannot_produce_a_favorable_score(): void
    {
        foreach ([[], ['wind_knots' => 8.0], ['wind_knots' => null, 'gusts_knots' => null, 'wave_height_meters' => null]] as $row) {
            $score = (new WeatherAnalyzer($this->weatherReturning([$row])))->scoreLeg(43, 5, now(), 1);
            $this->assertFalse($score['available']);
            $this->assertSame('À confirmer', $score['label']);
            $this->assertSame(50, $score['score']);
            $this->assertLessThan(100, $score['coverage_percent']);
        }
    }

    public function test_partial_time_coverage_and_known_dangers_remain_visible(): void
    {
        $row = ['time' => '2026-08-01T08:00', 'wind_knots' => 8.0, 'gusts_knots' => 12.0, 'wave_height_meters' => 0.4];
        $score = (new WeatherAnalyzer($this->weatherReturning([$row])))->scoreLeg(43, 5, now(), 2);
        $this->assertSame(50, $score['coverage_percent']);
        $this->assertSame('À confirmer', $score['label']);

        $score = (new WeatherAnalyzer($this->weatherReturning([
            ['wind_knots' => 40.0, 'gusts_knots' => null, 'wave_height_meters' => 4.0],
        ])))->scoreLeg(43, 5, now(), 1);
        $this->assertSame(0, $score['score']);
        $this->assertSame('Déconseillé', $score['label']);
        $this->assertFalse($score['available']);
    }

    /** @param list<array<string, mixed>> $rows */
    private function weatherReturning(array $rows): WeatherProviderInterface
    {
        return new class($rows) implements WeatherProviderInterface
        {
            /** @param list<array<string, mixed>> $rows */
            public function __construct(private readonly array $rows) {}

            public function conditions(float $latitude, float $longitude): array
            {
                return [];
            }

            public function forecast(float $latitude, float $longitude, \DateTimeInterface $from, int $hours = 24): array
            {
                return $this->rows;
            }
        };
    }
}
