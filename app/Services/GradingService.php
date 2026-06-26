<?php

namespace App\Services;

/**
 * Central grading system. Converts a percentage into a grade (+ remark) using
 * the scale defined in config/grading.php, and exposes the scale so the app can
 * render the grading key on the report card.
 */
class GradingService
{
    /** The full grading scale (each row: grade, min, max, remark). */
    public function scale(): array
    {
        return config('grading.scale', []);
    }

    public function passPercentage(): float
    {
        return (float) config('grading.pass_percentage', 33);
    }

    /**
     * Resolve the scale row for a percentage. Returns null for null input.
     */
    public function gradeFor(?float $percentage): ?array
    {
        if ($percentage === null) {
            return null;
        }

        $pct = max(0, min(100, round($percentage, 2)));

        foreach ($this->scale() as $band) {
            if ($pct >= $band['min'] && $pct <= $band['max']) {
                return $band;
            }
        }

        return null;
    }

    /** Just the grade letter (e.g. "A1"), or null. */
    public function gradeLetter(?float $percentage): ?string
    {
        return $this->gradeFor($percentage)['grade'] ?? null;
    }

    /** The remark for a percentage (e.g. "Outstanding"), or null. */
    public function remarkFor(?float $percentage): ?string
    {
        return $this->gradeFor($percentage)['remark'] ?? null;
    }

    public function isPass(?float $percentage): bool
    {
        return $percentage !== null && $percentage >= $this->passPercentage();
    }
}
