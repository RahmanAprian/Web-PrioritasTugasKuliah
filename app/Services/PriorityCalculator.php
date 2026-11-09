<?php

namespace App\Services;

use App\Models\PrioritySetting;
use Carbon\Carbon;

class PriorityCalculator
{
    public function score(?string $deadline, float $contribution, string $difficulty, PrioritySetting $settings): ?float
    {
        if (!$deadline) return null;
        $days = (int) Carbon::today()->diffInDays(Carbon::parse($deadline)->startOfDay(), false);
        $urgency = $days <= 0 ? 1.0 : ($days < $settings->horizon_days ? 1 - ($days / $settings->horizon_days) : 0.0);
        $grade = $contribution <= 10 ? (1 / 3) : ($contribution <= 20 ? (2 / 3) : 1.0);
        $difficultyScore = ['Mudah' => (1 / 3), 'Sedang' => (2 / 3), 'Sulit' => 1.0][$difficulty];
        return round($settings->urgency_weight * $urgency + $settings->contribution_weight * $grade + $settings->difficulty_weight * $difficultyScore, 4);
    }

    public function category(?float $score): ?string
    {
        if ($score === null) return null;
        return $score >= 0.6667 ? 'Tinggi' : ($score >= 0.3333 ? 'Sedang' : 'Rendah');
    }
}
