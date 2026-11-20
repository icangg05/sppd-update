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
                $allowedIds = $dept->getScopedRelatedIds();
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

        // 1. Agenda kalender. Judul event hanya nama pelaksana (sel kalender sempit);
        //    rincian lain dibawa di extendedProps untuk modal "semua nama di tanggal ini".
        $sppds = (clone $baseQuery)->with(['user.department', 'destinations.regency'])->get();

        $events = $sppds->map(function (SppdRequest $sppd) {
            $completed = $sppd->status === SppdStatus::COMPLETED;
            $name = $sppd->user?->name ?? '-';

            return [
                'id' => $sppd->id,
                'title' => $name,
                'start' => $sppd->start_date->format('Y-m-d'),
                // FullCalendar 'end' is exclusive, so we add 1 day
                'end' => $sppd->end_date->copy()->addDay()->format('Y-m-d'),
                'url' => route('sppd.show', $sppd),
                'classNames' => ['sppd-ev', $completed ? 'sppd-ev--done' : 'sppd-ev--approved'],
                'extendedProps' => [
                    'name' => $name,
                    'purpose' => $sppd->purpose,
                    'department' => $sppd->user?->department?->name,
                    'destination' => $sppd->destinations->pluck('place_name')->filter()->unique()->implode(', '),
                    'period' => $sppd->periodLabel(shortMonth: true),
                    'days' => $sppd->duration_days,
                    'status' => $completed ? 'completed' : 'approved',
                ],
            ];
        })->values();

        // 2. Get upcoming travels (start_date >= today)
        $upcomingSppds = (clone $baseQuery)
            ->with(['user.department', 'destinations.regency'])
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

        // Berangkat dalam 7 hari ke depan (besok s.d. 7 hari lagi).
        $nextWeekStart = $today->copy()->addDay();
        $nextWeekEnd = $today->copy()->addDays(7);
        $departingNextWeekCount = (clone $baseQuery)
            ->whereBetween('start_date', [$nextWeekStart, $nextWeekEnd->copy()->endOfDay()])
            ->count();

        return view('livewire.sppd.sppd-calendar', [
            'events' => $events,
            'upcomingSppds' => $upcomingSppds,
            'totalTravelsThisMonth' => $totalTravelsThisMonth,
            'activeTravelsCount' => $activeTravelsCount,
            'departingNextWeekCount' => $departingNextWeekCount,
            'nextWeekStart' => $nextWeekStart,
            'nextWeekEnd' => $nextWeekEnd,
        ])->title('Kalender SPPD');
    }
}
