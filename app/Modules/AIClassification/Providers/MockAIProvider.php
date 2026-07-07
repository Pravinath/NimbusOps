<?php

namespace App\Modules\AIClassification\Providers;

use App\Models\Complaint;
use App\Modules\AIClassification\Contracts\AIClassificationProvider;
use Illuminate\Support\Str;

class MockAIProvider implements AIClassificationProvider
{
    public function name(): string
    {
        return 'mock';
    }

    public function classify(Complaint $complaint): array
    {
        $text = Str::lower(
            $complaint->title.' '.$complaint->description
        );

        $category = $this->detectCategory($text);
        $priority = $this->detectPriority($text);
        $skill = $this->suggestSkill($category);
        $spareParts = $this->suggestSpareParts($category);

        $repeatedRisk = Str::contains($text, [
            'again',
            'repeated',
            'same issue',
            'still not working',
        ]);

        return [
            'issue_category' => $category,
            'predicted_priority' => $priority,
            'suggested_skill' => $skill,
            'suggested_spare_parts' => $spareParts,
            'suggested_sla_minutes' => $this->slaMinutes($priority),
            'repeated_complaint_risk' => $repeatedRisk,
            'summary' => Str::limit(
                "{$complaint->title}: {$complaint->description}",
                180
            ),
            'confidence_score' => $category === 'other' ? 65.00 : 88.00,
        ];
    }

    private function detectCategory(string $text): string
    {
        return match (true) {
            Str::contains($text, [
                'internet',
                'connection',
                'wifi',
                'no signal',
            ]) => 'internet_connectivity',

            Str::contains($text, [
                'router',
                'modem',
                'red light',
            ]) => 'router_issue',

            Str::contains($text, [
                'electric',
                'power',
                'socket',
                'short circuit',
            ]) => 'electrical_fault',

            Str::contains($text, [
                'water',
                'leak',
                'pipe',
                'tap',
            ]) => 'water_leak',

            Str::contains($text, [
                'air conditioner',
                'air conditioning',
                'ac not',
                'ac repair',
            ]) => 'ac_repair',

            Str::contains($text, [
                'fridge',
                'washing machine',
                'appliance',
                'microwave',
            ]) => 'appliance_issue',

            Str::contains($text, [
                'door',
                'window',
                'ceiling',
                'building',
                'facility',
            ]) => 'facility_maintenance',

            default => 'other',
        };
    }

    private function detectPriority(string $text): string
    {
        return match (true) {
            Str::contains($text, [
                'fire',
                'danger',
                'emergency',
                'electric shock',
                'critical',
            ]) => 'critical',

            Str::contains($text, [
                'urgent',
                'completely down',
                'not working',
                'no power',
                'major leak',
            ]) => 'high',

            Str::contains($text, [
                'slow',
                'intermittent',
                'sometimes',
            ]) => 'medium',

            default => 'low',
        };
    }

    private function suggestSkill(string $category): string
    {
        return match ($category) {
            'internet_connectivity',
            'router_issue' => 'network',

            'electrical_fault' => 'electrical',
            'water_leak' => 'plumbing',
            'ac_repair' => 'ac',
            'appliance_issue' => 'appliance',
            'facility_maintenance' => 'facility',
            default => 'general',
        };
    }

    private function suggestSpareParts(string $category): array
    {
        return match ($category) {
            'internet_connectivity' => ['network cable', 'connector'],
            'router_issue' => ['router', 'power adapter'],
            'electrical_fault' => ['fuse', 'circuit breaker'],
            'water_leak' => ['pipe fitting', 'seal tape'],
            'ac_repair' => ['air filter', 'capacitor'],
            'appliance_issue' => ['replacement fuse'],
            'facility_maintenance' => ['general repair materials'],
            default => [],
        };
    }

    private function slaMinutes(string $priority): int
    {
        return match ($priority) {
            'critical' => 120,
            'high' => 240,
            'medium' => 720,
            default => 1440,
        };
    }
}
