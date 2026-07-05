<?php

namespace App\Modules\Dispatch\Services;

use App\Models\Complaint;
use App\Models\Technician;
use Illuminate\Support\Collection;

class TechnicianSuggestionService
{
    public function suggest(Complaint $complaint): Collection
    {
        $complaint->loadMissing('aiClassification');

        $requiredSkill = $complaint->aiClassification?->suggested_skill
            ?? 'general';

        return Technician::with(['user', 'serviceArea'])
            ->whereHas('user', function ($query) {
                $query->where('status', 'active');
            })
            ->get()
            ->map(function (Technician $technician) use (
                $complaint,
                $requiredSkill
            ) {
                $score = 0;
                $reasons = [];

                if ($technician->skill_category === $requiredSkill) {
                    $score += 35;
                    $reasons[] = 'Skill matches complaint requirement';
                }

                if (
                    $complaint->service_area_id !== null
                    && $technician->service_area_id === $complaint->service_area_id
                ) {
                    $score += 25;
                    $reasons[] = 'Assigned to complaint service area';
                }

                if ($technician->availability_status === 'available') {
                    $score += 30;
                    $reasons[] = 'Currently available';
                } elseif ($technician->availability_status === 'busy') {
                    $score += 5;
                    $reasons[] = 'Currently busy';
                } else {
                    $reasons[] = 'Currently unavailable';
                }

                if (
                    in_array($complaint->priority, ['high', 'critical'], true)
                    && $technician->availability_status === 'available'
                ) {
                    $score += 10;
                    $reasons[] = 'Suitable for urgent complaint';
                }

                $workloadPenalty = min(
                    $technician->current_workload * 5,
                    25
                );

                $score -= $workloadPenalty;

                if ($technician->current_workload === 0) {
                    $reasons[] = 'No active workload';
                } else {
                    $reasons[] = "Current workload: {$technician->current_workload}";
                }

                return [
                    'technician' => $technician,
                    'score' => max($score, 0),
                    'assignable' => $technician->availability_status === 'available',
                    'reasons' => $reasons,
                ];
            })
            ->sortByDesc('score')
            ->values()
            ->take(10);
    }
}