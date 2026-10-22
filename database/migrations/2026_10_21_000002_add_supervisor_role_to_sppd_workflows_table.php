<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kriteria alur "Atasan langsung pemohon" dan penghapusan tahap opsional.
 *
 * Setiap tahap alur kini wajib dijalankan. Alur yang punya tahap opsional
 * (mis. Kabid) dipecah menjadi dua alur yang dipilih dari atasan langsung pemohon:
 *  - alur asal untuk pemohon beratasan role tahap itu (tahapnya menjadi wajib);
 *  - varian tanpa tahap itu untuk pemohon beratasan role tahap sesudahnya.
 * Penanda tangan SPT/SPPD tidak berubah (tahap opsional tidak pernah menandatangani).
 */
return new class extends Migration
{
  public function up(): void
  {
    Schema::table('sppd_workflows', function (Blueprint $table) {
      // null = tidak dibedakan (dipilih eksplisit di form Alur Persetujuan).
      $table->json('supervisor_role')->nullable()->after('destination');
    });

    DB::transaction(function () {
      $labels = DB::table('roles')->pluck('label', 'name');
      $label = fn (string $role) => $labels[$role] ?? ucwords(str_replace('_', ' ', $role));

      foreach (DB::table('sppd_workflows')->orderBy('id')->get() as $workflow) {
        $steps = json_decode($workflow->steps ?? '[]', true) ?: [];
        $optionalIndex = collect($steps)->search(fn ($step) => is_array($step) && filter_var($step['optional'] ?? false, FILTER_VALIDATE_BOOLEAN));
        $required = array_values(array_map(fn ($step) => is_array($step) ? array_diff_key($step, ['optional' => true]) : $step, $steps));

        $next = $optionalIndex === false ? null : ($steps[$optionalIndex + 1] ?? null);
        if ($next === null) {
          DB::table('sppd_workflows')->where('id', $workflow->id)->update(['steps' => json_encode($required)]);
          continue;
        }

        $optionalRole = $steps[$optionalIndex]['role'];
        $nextRole = is_array($next) ? $next['role'] : $next;

        DB::table('sppd_workflows')->where('id', $workflow->id)->update([
          'name'            => "{$workflow->name} (atasan {$label($optionalRole)})",
          'steps'           => json_encode($required),
          'supervisor_role' => json_encode([$optionalRole]),
          'updated_at'      => now(),
        ]);

        $variant = $required;
        unset($variant[$optionalIndex]);
        DB::table('sppd_workflows')->insert([
          'name'            => "{$workflow->name} (atasan {$label($nextRole)})",
          'department_type' => $workflow->department_type,
          'applicant_role'  => $workflow->applicant_role,
          'destination'     => $workflow->destination,
          'supervisor_role' => json_encode([$nextRole]),
          'steps'           => json_encode(array_values($variant)),
          'is_active'       => $workflow->is_active,
          'created_at'      => now(),
          'updated_at'      => now(),
        ]);
      }
    });
  }

  public function down(): void
  {
    Schema::table('sppd_workflows', function (Blueprint $table) {
      $table->dropColumn('supervisor_role');
    });
  }
};
