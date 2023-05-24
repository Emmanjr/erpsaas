<?php

namespace App\Filament\Pages\Widgets\Companies\Charts;

use App\Models\Company;
use Filament\Widgets\StatsOverviewWidget;

class CompanyStatsOverview extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 3;

    protected function getColumns(): int
    {
        return 3;
    }

    /**
     * Holt's Linear Trend Method
     */
    protected function holtLinearTrend($data, $alpha, $beta): array
    {
        $level = $data[0];
        $trend = $data[1] - $data[0];

        $forecast = [];
        for ($i = 0; $i < count($data); $i++) {
            $prev_level = $level;
            $level = $alpha * $data[$i] + (1 - $alpha) * ($prev_level + $trend);
            $trend = $beta * ($level - $prev_level) + (1 - $beta) * $trend;
            $forecast[] = $level + $trend;
        }

        return $forecast;
    }

    /**
     * Adjusts the alpha and beta parameters based on the model's performance
     */
    protected function adjustTrendParameters($data, $alpha, $beta): array
    {
        $minError = PHP_INT_MAX;
        $bestAlpha = $alpha;
        $bestBeta = $beta;

        // try different alpha and beta values within a reasonable range
        for ($alpha = 0.1; $alpha <= 1; $alpha += 0.1) {
            for ($beta = 0.1; $beta <= 1; $beta += 0.1) {
                $forecast = $this->holtLinearTrend($data, $alpha, $beta);
                $error = $this->calculateError($data, $forecast);
                if ($error < $minError) {
                    $minError = $error;
                    $bestAlpha = $alpha;
                    $bestBeta = $beta;
                }
            }
        }

        return [$bestAlpha, $bestBeta];
    }

    /**
     * Calculates the sum of squared errors between the actual data and the forecast
     */
    protected function calculateError($data, $forecast): float
    {
        $error = 0;
        for ($i = 0; $i < count($data); $i++) {
            $error += pow($data[$i] - $forecast[$i], 2);
        }

        return $error;
    }

    /**
     * Chart Options
     */
    protected function getCards(): array
    {
        // Define constants
        $alpha = 0.8;
        $beta = 0.2;

        // Define time variables
        $startOfYear = today()->startOfYear();
        $today = today();

        // Get Company Data
        $companyData = Company::selectRaw("COUNT(*) as aggregate, YEARWEEK(created_at, 3) as week")
            ->whereBetween('created_at', [$startOfYear, $today])
            ->groupByRaw('week')
            ->get();

        // Initialize weeks
        $weeks = [];
        for ($week = $startOfYear->copy(); $week->lte($today); $week->addWeek()) {
            $weeks[$week->format('oW')] = 0;
        }

        // Get Weekly Data for Company Data
        $weeklyData = collect($weeks)->mapWithKeys(static function ($value, $week) use ($companyData) {
            $matchingData = $companyData->firstWhere('week', $week);
            return [$week => $matchingData ? $matchingData->aggregate : 0];
        });

        // Calculate total companies per week
        $totalCompanies = $weeklyData->reduce(static function ($carry, $value) {
            $carry[] = ($carry ? end($carry) : 0) + $value;
            return $carry;
        }, []);

        // Calculate new companies and percentage change per week
        $newCompanies = [0];
        $weeklyPercentageChange = [0];
        for ($i = 1; $i < count($totalCompanies); $i++) {
            $newCompanies[] = $totalCompanies[$i] - $totalCompanies[$i - 1];
            $weeklyPercentageChange[] = ($newCompanies[$i] / $totalCompanies[$i - 1]) * 100;
        }

        // Calculate average weekly growth rate
        $totalWeeks = $startOfYear->diffInWeeks($today);
        $averageWeeklyGrowthRate = round(array_sum($weeklyPercentageChange) / $totalWeeks, 2);

        $weeklyDataArray = $weeklyData->values()->toArray();

        // Adjust alpha and beta parameters
        [$alpha, $beta] = $this->adjustTrendParameters($weeklyDataArray, $alpha, $beta);

        // Calculate Holt's Linear Trend Forecast for next week
        $holtForecast = $this->holtLinearTrend($weeklyDataArray, $alpha, $beta);
        $expectedNewCompanies = round(end($holtForecast));

        // Company Stats Overview Cards
        return [
            StatsOverviewWidget\Card::make("New Companies Forecast (Holt's Linear Trend)", $expectedNewCompanies),
            StatsOverviewWidget\Card::make('Average Weekly Growth Rate', $averageWeeklyGrowthRate . '%'),
            StatsOverviewWidget\Card::make('Personal Companies', Company::sum('personal_company')),
        ];
    }
}