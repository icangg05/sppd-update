<?php

namespace App\Livewire;

use App\Models\PositionRequest;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class PendingPositionRequestBadge extends Component
{
  public int $count = 0;

  public function mount(): void
  {
    $this->refreshCount();
  }

  #[On('position-request-updated')]
  public function refreshCount(): void
  {
    $this->count = Auth::check() && Auth::user()->hasRole('super_admin')
      ? PositionRequest::pending()->count()
      : 0;
  }

  public function render()
  {
    return view('livewire.pending-position-request-badge');
  }
}
