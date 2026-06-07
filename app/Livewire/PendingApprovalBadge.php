<?php

namespace App\Livewire;

use App\Models\SppdApproval;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class PendingApprovalBadge extends Component
{
    public int $count = 0;

    public function mount(): void
    {
        $this->refreshCount();
    }

    #[On('approval-updated')]
    public function refreshCount(): void
    {
        $this->count = Auth::check()
            ? SppdApproval::readyForApprover(Auth::id())->count()
            : 0;
    }

    public function render()
    {
        return view('livewire.pending-approval-badge');
    }
}
