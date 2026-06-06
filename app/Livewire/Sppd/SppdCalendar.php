<?php

namespace App\Livewire\Sppd;

use App\Enums\SppdStatus;
use App\Models\SppdRequest;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class SppdCalendar extends Component
{
    public function render()
    {
        $baseQuery = SppdRequest::query();

        // Filter by department hierarchy
        if (! Auth::user()->hasRole('super_admin')) {
            $dept = Auth::user()->department;
            if ($dept) {
                $allowedIds = $dept->getAllRelatedIds();
                $baseQuery->whereHas('user', function ($q) use ($allowedIds) {
                    $q->whereIn('department_id', $allowedIds);
                });
            } else {
                $baseQuery->whereHas('user', function ($q) {
                    $q->where('department_id', Auth::user()->department_id);
                });
            }
        }

        // Only show approved and completed
        $baseQuery->whereIn('status', [SppdStatus::APPROVED, SppdStatus::COMPLETED]);

        // 1. Get calendar events
        $sppds = (clone $baseQuery)->with(['user.department'])->get();

        $events = $sppds->map(function ($sppd) {
            return [
                'id' => $sppd->id,
                'title' => $sppd->user?->name.' - '.$sppd->purpose,
                'start' => $sppd->start_date->format('Y-m-d'),
                // FullCalendar 'end' is exclusive, so we add 1 day
                'end' => $sppd->end_date->addDay()->format('Y-m-d'),
                'url' => route('sppd.show', $sppd),
                'color' => $sppd->status === SppdStatus::COMPLETED ? '#10b981' : '#0ea5e9', // emerald-500 vs sky-500
            ];
        });

        // 2. Get upcoming travels (start_date >= today)
        $upcomingSppds = (clone $baseQuery)
            ->with(['user.department', 'destinations'])
            ->where('start_date', '>=', now()->startOfDay())
            ->orderBy('start_date', 'asc')
            ->limit(5)
            ->get();

        // 3. Stats
        $today = now()->startOfDay();
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $totalTravelsThisMonth = (clone $baseQuery)
            ->whereBetween('start_date', [$startOfMonth, $endOfMonth])
            ->count();

        $activeTravelsCount = (clone $baseQuery)
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->count();

        return view('livewire.sppd.sppd-calendar', [
            'events' => $events,
            'upcomingSppds' => $upcomingSppds,
            'totalTravelsThisMonth' => $totalTravelsThisMonth,
            'activeTravelsCount' => $activeTravelsCount,
        ])->title('Kalender SPPD');
    }
}
