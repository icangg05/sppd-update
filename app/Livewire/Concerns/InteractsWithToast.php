<?php

namespace App\Livewire\Concerns;

trait InteractsWithToast
{
  public function toast(string $message, string $type = 'success', ?string $title = null): void
  {
    $this->dispatch('toast', type: $type, message: $message, title: $title);
  }

  public function toastSuccess(string $message, ?string $title = null): void
  {
    $this->toast($message, 'success', $title);
  }

  public function toastError(string $message, ?string $title = null): void
  {
    $this->toast($message, 'error', $title);
  }

  public function toastInfo(string $message, ?string $title = null): void
  {
    $this->toast($message, 'info', $title);
  }

  public function toastWarning(string $message, ?string $title = null): void
  {
    $this->toast($message, 'warning', $title);
  }
}
