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

    public function reason(?string $deadline, float $contribution, string $difficulty, PrioritySetting $settings): ?string
    {
        if (!$deadline) return 'Tenggat belum ditentukan, jadi tugas belum masuk rekomendasi otomatis.';

        $days = (int) Carbon::today()->diffInDays(Carbon::parse($deadline)->startOfDay(), false);
        $urgency = $days <= 0 ? 1.0 : ($days < $settings->horizon_days ? 1 - ($days / $settings->horizon_days) : 0.0);
        $grade = $contribution <= 10 ? (1 / 3) : ($contribution <= 20 ? (2 / 3) : 1.0);
        $difficultyScore = ['Mudah' => (1 / 3), 'Sedang' => (2 / 3), 'Sulit' => 1.0][$difficulty];
        $weighted = [
            'Tenggat' => $settings->urgency_weight * $urgency,
            'Kontribusi nilai' => $settings->contribution_weight * $grade,
            'Tingkat kesulitan' => $settings->difficulty_weight * $difficultyScore,
        ];
        arsort($weighted);
        $factor = array_key_first($weighted);

        return match ($factor) {
            'Tenggat' => $days <= 0 ? 'Tenggat sudah tiba atau terlewat; urgensi menjadi faktor utama.' : 'Tenggat relatif dekat; urgensi paling besar memengaruhi rekomendasi.',
            'Kontribusi nilai' => 'Kontribusi nilai paling besar memengaruhi rekomendasi.',
            default => 'Tingkat kesulitan paling besar memengaruhi rekomendasi.',
        };
    }

    public function category(?float $score): ?string
    {
        if ($score === null) return null;
        return $score >= 0.6667 ? 'Tinggi' : ($score >= 0.3333 ? 'Sedang' : 'Rendah');
    }

    public function normalizedWeights(PrioritySetting $settings): PrioritySetting
    {
        $copy = clone $settings;
        $copy->urgency_weight = .5;
        $copy->contribution_weight = .3;
        $copy->difficulty_weight = .2;
        $copy->horizon_days = 14;
        return $copy;
    }
}
