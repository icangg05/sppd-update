<?php

namespace App\Livewire;

use App\Livewire\Concerns\InteractsWithToast;
use App\Services\AppSettings;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class AppSettingIndex extends Component
{
  use InteractsWithToast;

  /** Nilai yang sedang diedit, per key. */
  public array $values = [];

  public function mount(AppSettings $settings): void
  {
    abort_unless(auth()->user()->hasRole('super_admin'), 403);

    // Daftar centang (definisi ber-`options`) diedit sebagai daftar key opsi. Nilai non-skalar
    // lain (array/objek hasil edit manual file) hanya ditampilkan, tidak diedit.
    $definitions = AppSettings::definitions();
    $this->values = collect($settings->all())
      ->filter(fn ($item, $key) => isset($definitions[$key]['options']) || is_scalar($item['value']) || $item['value'] === null)
      ->map(fn ($item, $key) => isset($definitions[$key]['options'])
        ? array_values(array_intersect(array_keys($definitions[$key]['options']), (array) $item['value']))
        : (string) $item['value'])
      ->all();
  }

  protected function rules(): array
  {
    $definitions = AppSettings::definitions();

    return collect(array_keys($this->values))
      ->flatMap(fn ($key) => ["values.{$key}" => $definitions[$key]['rules'] ?? ['nullable', 'string', 'max:2000']]
        + (isset($definitions[$key]['options']) ? ["values.{$key}.*" => ['string', 'in:' . implode(',', array_keys($definitions[$key]['options']))]] : []))
      ->all();
  }

  protected function validationAttributes(): array
  {
    $definitions = AppSettings::definitions();

    return collect(array_keys($this->values))
      ->mapWithKeys(fn ($key) => ["values.{$key}" => $definitions[$key]['label'] ?? $key])
      ->all();
  }

  public function save(AppSettings $settings): void
  {
    abort_unless(auth()->user()->hasRole('super_admin'), 403);

    $this->values = array_map(fn ($v) => is_string($v) ? trim($v) : $v, $this->values);
    $this->validate();

    // Pengaturan angka disimpan sebagai bilangan, bukan teks. Daftar centang disimpan
    // berurutan sesuai opsinya, agar urutan klik tidak terhitung sebagai perubahan.
    $definitions = AppSettings::definitions();
    $values = collect($this->values)
      ->map(fn ($value, $key) => match (true) {
        isset($definitions[$key]['options']) => array_values(array_intersect(array_keys($definitions[$key]['options']), (array) $value)),
        in_array('integer', $definitions[$key]['rules'] ?? [], true) => (int) $value,
        default => $value,
      })
      ->all();

    // Hanya key yang memang ada di file yang boleh ditulis.
    $changes = $settings->update(array_intersect_key($values, $settings->all()));

    if ($changes === []) {
      $this->toastInfo('Tidak ada perubahan untuk disimpan.');
      return;
    }

    activity('settings')
      ->causedBy(auth()->user())
      ->withChanges($changes)
      ->log('Pengaturan aplikasi diperbarui: ' . implode(', ', array_keys($changes['attributes'])));

    $this->toastSuccess('Pengaturan aplikasi berhasil disimpan.');
  }

  public function render(AppSettings $settings)
  {
    return view('livewire.settings.index', [
      'items'       => $settings->all(),
      'definitions' => AppSettings::definitions(),
    ])->title('Pengaturan Aplikasi');
  }
}
