<?php

namespace App\Livewire\Sppd;

use App\Enums\DepartmentType;
use App\Enums\EmployeeType;
use App\Enums\SppdDomain;
use App\Enums\SppdStatus;
use App\Models\SppdApproval;
use App\Models\SppdRequest;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class SppdIndex extends Component
{
    use WithPagination;

    #[Url(keep: true)]
    public string $search = '';

    #[Url(keep: true)]
    public string $status = '';

    #[Url(keep: true)]
    public string $domain = '';

    #[Url(keep: true)]
    public string $jabatan = '';

    #[Url(keep: true)]
    public string $filter = '';

    private const SESSION_KEY = 'sppd.index.filters';

    public static function jabatanLabels(): array
    {
        return [
            ''            => 'Semua Jabatan',
            'kepala_opd'  => 'Kepala OPD',
            'eselon_staf' => 'Eselon III, IV & Staf',
            'anggota_dprd' => 'Anggota DPRD',
            'staff_dprd'  => 'Staff DPRD',
            'sekwan'      => 'Sekwan',
        ];
    }

    public static function savedFilters(): array
    {
        return session(self::SESSION_KEY, []);
    }

    public function isApprovalMode(): bool
    {
        return $this->filter === 'approval';
    }

    public function mount(): void
    {
        if ($this->isApprovalMode()) {
            return;
        }

        $saved = self::savedFilters();

        if ($this->jabatan === '' && ! empty($saved['jabatan'])) {
            $this->jabatan = $saved['jabatan'];
        }

        if ($this->status === '' && ! empty($saved['status'])) {
            $this->status = $saved['status'];
        }

        if ($this->domain === '' && ! empty($saved['domain'])) {
            $this->domain = $saved['domain'];
        }

        if ($this->search === '' && ! empty($saved['search'])) {
            $this->search = $saved['search'];
        }
    }

    public function activeFilterLabel(): string
    {
        return self::jabatanLabels()[$this->jabatan] ?? 'Semua Jabatan';
    }

    protected function persistFilters(): void
    {
        if ($this->isApprovalMode()) {
            return;
        }

        session([self::SESSION_KEY => [
            'jabatan' => $this->jabatan,
            'status'  => $this->status,
            'domain'  => $this->domain,
            'search'  => $this->search,
        ]]);
    }

    public function filterByJabatan(string $value): void
    {
        $this->jabatan = $value;
        $this->persistFilters();
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';

        if (! $this->isApprovalMode()) {
            $this->status = '';
            $this->domain = '';
            $this->jabatan = '';
            $this->filter = '';
            session()->forget(self::SESSION_KEY);
        }

        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->persistFilters();
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->persistFilters();
        $this->resetPage();
    }

    public function updatedDomain(): void
    {
        $this->persistFilters();
        $this->resetPage();
    }

    public function deleteSppd(int $id): void
    {
        $sppd = SppdRequest::findOrFail($id);

        if ($sppd->status->value === 'in_progress' && (Auth::id() === $sppd->creator_id || Auth::id() === $sppd->user_id)) {
            $sppd->delete();
            session()->flash('success', 'Pengajuan SPPD berhasil dibatalkan dan dihapus.');
        } else {
            session()->flash('error', 'Anda tidak memiliki hak untuk membatalkan pengajuan ini atau status SPPD tidak dalam proses.');
        }
    }

    public function render()
    {
        $query = SppdRequest::with(['user.department', 'category', 'budget.department']);
        $isApprovalMode = $this->isApprovalMode();

        if ($isApprovalMode) {
            $pendingSppdIds = SppdApproval::readyForApprover(Auth::id())
                ->pluck('sppd_request_id');
            $query->whereIn('id', $pendingSppdIds);
        } else {
            if ($this->status !== '') {
                $query->where('status', $this->status);
            }

            if ($this->domain !== '') {
                $query->where('domain', $this->domain);
            }

            if ($this->jabatan !== '') {
                $jabatan = $this->jabatan;
                $query->whereHas('user', function ($q) use ($jabatan) {
                    if ($jabatan === 'kepala_opd') {
                        $q->role('kepala_opd');
                    } elseif ($jabatan === 'eselon_ii') {
                        $q->role(['sekda', 'asisten', 'kepala_opd', 'sekwan']);
                    } elseif ($jabatan === 'eselon_staf') {
                        $q->role(['staf', 'admin_opd', 'sekretaris_opd', 'kasubid_kasubag', 'kabid_irban_kabag']);
                    } elseif ($jabatan === 'eselon_iv') {
                        $q->role(['kasubid_kasubag', 'sekcam', 'lurah', 'kapus']);
                    } elseif ($jabatan === 'staf') {
                        $q->role('staf');
                    } elseif ($jabatan === 'anggota_dprd') {
                        $q->where(function ($sub) {
                            $sub->role(['anggota_dprd', 'pimpinan_dprd'])
                                ->orWhere('employee_type', EmployeeType::DPRD);
                        });
                    } elseif ($jabatan === 'staff_dprd') {
                        $q->role('staf')->whereHas('department', function ($d) {
                            $d->where(function ($sub) {
                                $sub->where('type', DepartmentType::DPRD)
                                    ->orWhereHas('parent', function ($p) {
                                        $p->where('type', DepartmentType::DPRD);
                                    });
                            });
                        });
                    } elseif ($jabatan === 'sekwan') {
                        $q->role('sekwan');
                    }
                });
            }
        }

        if ($this->search !== '') {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('purpose', 'like', "%{$search}%")
                    ->orWhere('document_number', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($q2) => $q2->where('name', 'like', "%{$search}%"));
            });
        }

        $sppds = $query->latest()->paginate(15);
        $statuses = SppdStatus::cases();
        $domains = SppdDomain::cases();
        $title = $isApprovalMode ? 'Persetujuan' : 'Daftar SPPD';

        $activeFilterLabel = $this->activeFilterLabel();

        return view('livewire.sppd.index', compact('sppds', 'statuses', 'domains', 'isApprovalMode', 'activeFilterLabel'))
            ->title($title);
    }
}
