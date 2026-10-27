<?php

namespace Tests\Unit;

use App\Models\Task;
use App\Services\OccurrenceExpander;
use Carbon\CarbonImmutable;
use Tests\TestCase;

/**
 * Cálculo de fechas de cada tipo de recurrencia (sin base de datos).
 */
class OccurrenceExpanderTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $attributes
     * @return list<string>
     */
    private function dates(array $attributes, string $from, string $to): array
    {
        $task = new Task(['title' => 'Tarea', ...$attributes]);

        return (new OccurrenceExpander)->datesFor(
            $task,
            CarbonImmutable::parse($from),
            CarbonImmutable::parse($to),
        );
    }

    public function test_single_task_only_on_its_date(): void
    {
        $this->assertSame(['2026-10-15'], $this->dates(['task_date' => '2026-10-15'], '2026-10-12', '2026-10-18'));
        $this->assertSame([], $this->dates(['task_date' => '2026-10-15'], '2026-10-16', '2026-10-18'));
    }

    public function test_daily_starts_on_task_date_and_stops_at_until(): void
    {
        $dates = $this->dates(
            ['task_date' => '2026-10-14', 'recurrence' => 'daily', 'recurrence_until' => '2026-10-16'],
            '2026-10-12',
            '2026-10-18',
        );

        $this->assertSame(['2026-10-14', '2026-10-15', '2026-10-16'], $dates);
    }

    public function test_weekly_uses_recurrence_days_monday_to_sunday(): void
    {
        $dates = $this->dates(
            ['task_date' => '2026-10-12', 'recurrence' => 'weekly', 'recurrence_days' => [1, 3, 7]],
            '2026-10-12',
            '2026-10-25',
        );

        $this->assertSame(['2026-10-12', '2026-10-14', '2026-10-18', '2026-10-19', '2026-10-21', '2026-10-25'], $dates);
    }

    public function test_weekly_without_days_repeats_on_the_task_weekday(): void
    {
        $dates = $this->dates(
            ['task_date' => '2026-10-15', 'recurrence' => 'weekly'],
            '2026-10-01',
            '2026-10-31',
        );

        $this->assertSame(['2026-10-15', '2026-10-22', '2026-10-29'], $dates);
    }

    public function test_monthly_skips_months_without_that_day(): void
    {
        $dates = $this->dates(
            ['task_date' => '2026-01-31', 'recurrence' => 'monthly'],
            '2026-01-01',
            '2026-05-31',
        );

        $this->assertSame(['2026-01-31', '2026-03-31', '2026-05-31'], $dates);
    }

    public function test_yearly_on_february_29_only_in_leap_years(): void
    {
        $dates = $this->dates(
            ['task_date' => '2024-02-29', 'recurrence' => 'yearly'],
            '2024-01-01',
            '2029-12-31',
        );

        $this->assertSame(['2024-02-29', '2028-02-29'], $dates);
    }

    public function test_nothing_before_task_date(): void
    {
        $dates = $this->dates(
            ['task_date' => '2026-11-01', 'recurrence' => 'daily'],
            '2026-10-01',
            '2026-10-31',
        );

        $this->assertSame([], $dates);
    }
}
