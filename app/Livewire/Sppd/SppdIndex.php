<?php

namespace App\Livewire\Sppd;

use App\Enums\ApprovalStatus;
use App\Enums\DepartmentType;
use App\Enums\EmployeeType;
use App\Enums\SppdDomain;
use App\Enums\SppdStatus;
use App\Models\SppdApproval;
use App\Models\SppdRequest;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use Livewire\Attributes\Layout;

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

    public function mount(): void
    {
        if (empty($this->jabatan)) {
            $user = Auth::user();
            $isDprd = $user->department?->type?->value === 'dprd' || $user->department?->parent?->type?->value === 'dprd';
            $isSuperAdmin = $user->hasRole('super_admin');
            $this->jabatan = ($isSuperAdmin || $isDprd) ? 'anggota_dprd' : 'kepala_opd';
        }
    }

    public function filterByJabatan(string $value): void
    {
        $this->jabatan = $value;
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->status = '';
        $this->domain = '';
        $this->filter = '';
        
        $user = Auth::user();
        $isDprd = $user->department?->type?->value === 'dprd' || $user->department?->parent?->type?->value === 'dprd';
        $isSuperAdmin = $user->hasRole('super_admin');
        $this->jabatan = ($isSuperAdmin || $isDprd) ? 'anggota_dprd' : 'kepala_opd';
        
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedDomain(): void
    {
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

        // Filter by department hierarchy
        if (! Auth::user()->hasRole('super_admin')) {
            $dept = Auth::user()->department;
            if ($dept) {
                $allowedIds = $dept->getAllRelatedIds();
                $query->whereHas('user', function ($q) use ($allowedIds) {
                    $q->whereIn('department_id', $allowedIds);
                });
            } else {
                $query->whereHas('user', function ($q) {
                    $q->where('department_id', Auth::user()->department_id);
                });
            }
        }

        // Filter by status
        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        // Filter by domain
        if ($this->domain !== '') {
            $query->where('domain', $this->domain);
        }

        // Filter: show only pending approvals for current user
        if ($this->filter === 'approval') {
            $pendingSppdIds = SppdApproval::where('approver_id', Auth::id())
                ->where('status', ApprovalStatus::PENDING)
                ->pluck('sppd_request_id');
            $query->whereIn('id', $pendingSppdIds);
        }

        // Filter by Jabatan/Eselon
        if ($this->jabatan !== '') {
            $jabatan = $this->jabatan;
            $query->whereHas('user', function ($q) use ($jabatan) {
                if ($jabatan === 'kepala_opd') {
                    $q->role('kepala_opd');
                } elseif ($jabatan === 'eselon_ii') {
                    $q->role(['sekda', 'asisten', 'kepala_opd', 'sekwan']);
                } elseif ($jabatan === 'eselon_iii') {
                    $q->role(['sekretaris_opd', 'kabid_irban_kabag', 'camat']);
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

        // Search
        if ($this->search !== '') {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('purpose', 'like', "%{$search}%")
                    ->orWhere('document_number', 'like', "%{$search}%")
                    ->orWhereHas('user', fn($q2) => $q2->where('name', 'like', "%{$search}%"));
            });
        }

        $sppds = $query->latest()->paginate(15);
        $statuses = SppdStatus::cases();
        $domains = SppdDomain::cases();

        return view('livewire.sppd.index', compact('sppds', 'statuses', 'domains'))
            ->title('Daftar SPPD');
    }
}
