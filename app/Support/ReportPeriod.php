<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class ReportPeriod
{
    /**
     * @return array{type: string, value: string, label: string, slug: string, start: Carbon, end: Carbon}
     */
    public static function resolve(Request $request, bool $required = false): array
    {
        $fallbackMonth = $request->query('month');
        $usesLegacyMonth = is_string($fallbackMonth) && ! $request->query->has('period_type');
        $periodRules = $required && ! $usesLegacyMonth ? 'required' : 'sometimes';
        $validated = $request->validate([
            'period_type' => [$periodRules, 'in:daily,weekly,monthly'],
            'period_value' => [$periodRules, 'string', 'max:10'],
            'month' => ['sometimes', 'date_format:Y-m'],
        ]);
        $type = $validated['period_type'] ?? 'monthly';
        $value = $validated['period_value'] ?? $validated['month'] ?? now()->format('Y-m');

        if (! self::matchesFormat($value, $type)) {
            throw ValidationException::withMessages([
                'period_value' => 'Format periode laporan tidak valid.',
            ]);
        }

        if ($type === 'daily') {
            $start = Carbon::createFromFormat('!Y-m-d', $value);
            $end = $start->copy()->endOfDay();
            $label = $start->translatedFormat('d F Y');
            $slug = 'harian-'.$start->format('Y-m-d');
        } elseif ($type === 'weekly') {
            [$year, $week] = sscanf($value, '%4d-W%2d');
            $start = Carbon::now()->setISODate($year, $week)->startOfWeek(Carbon::MONDAY)->startOfDay();
            $end = $start->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();
            $label = $start->format('d/m/Y').' - '.$end->format('d/m/Y');
            $slug = 'mingguan-'.$start->format('Y-m-d');
        } else {
            $start = Carbon::createFromFormat('!Y-m', $value)->startOfMonth();
            $end = $start->copy()->endOfMonth()->endOfDay();
            $label = $start->translatedFormat('F Y');
            $slug = 'bulanan-'.$start->format('Y-m');
        }

        return compact('type', 'value', 'label', 'slug', 'start', 'end');
    }

    private static function matchesFormat(string $value, string $type): bool
    {
        if ($type === 'weekly') {
            if (preg_match('/^(\d{4})-W(\d{2})$/', $value, $matches) !== 1) {
                return false;
            }

            $start = Carbon::now()->setISODate((int) $matches[1], (int) $matches[2])->startOfWeek(Carbon::MONDAY);

            return $matches[2] >= 1 && $matches[2] <= 53
                && $start->format('o-\WW') === $value;
        }

        $format = $type === 'daily' ? 'Y-m-d' : 'Y-m';
        $pattern = $type === 'daily' ? '/^\d{4}-\d{2}-\d{2}$/' : '/^\d{4}-\d{2}$/';
        if (preg_match($pattern, $value) !== 1) {
            return false;
        }

        $date = Carbon::createFromFormat('!'.$format, $value);

        return $date !== false && $date->format($format) === $value;
    }
}
