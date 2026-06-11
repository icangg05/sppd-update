<?php

namespace App\Http\Controllers;

use App\Models\SppdActualExpense;
use App\Models\SppdRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SppdActualExpenseController extends Controller
{
  /**
   * Store a newly created resource in storage.
   */
  public function store(Request $request, SppdRequest $sppd): RedirectResponse
  {
    abort_unless(auth()->user()->hasAnyRole(['admin_opd', 'super_admin']), 403, 'Aksi ini tidak diizinkan.');
    abort_unless(in_array($sppd->status->value, ['approved', 'completed']), 403, 'Aksi ini tidak diizinkan karena pengajuan SPPD belum disetujui sepenuhnya.');

    if ($request->has('user_ids')) {
      $validated = $request->validate([
        'user_ids'    => 'required|array',
        'user_ids.*'  => 'exists:users,id',
        'description' => 'required|string|max:500',
        'amount'      => 'required|numeric|min:0',
      ]);

      foreach ($validated['user_ids'] as $userId) {
        $sppd->actualExpenses()->create([
          'user_id'     => $userId,
          'description' => $validated['description'],
          'amount'      => $validated['amount'],
        ]);
      }

      return back()->with('success', 'Pengeluaran riil berhasil ditambahkan untuk pegawai terpilih.');
    }

    $validated = $request->validate([
      'user_id'     => 'required|exists:users,id',
      'description' => 'required|string|max:500',
      'amount'      => 'required|numeric|min:0',
    ]);

    $sppd->actualExpenses()->create($validated);

    return back()->with('success', 'Pengeluaran riil berhasil ditambahkan.');
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, SppdRequest $sppd, SppdActualExpense $expense): RedirectResponse
  {
    abort_unless(auth()->user()->hasAnyRole(['admin_opd', 'super_admin']), 403, 'Aksi ini tidak diizinkan.');
    abort_unless(in_array($sppd->status->value, ['approved', 'completed']), 403, 'Aksi ini tidak diizinkan karena pengajuan SPPD belum disetujui sepenuhnya.');

    $validated = $request->validate([
      'description' => 'required|string|max:500',
      'amount'      => 'required|numeric|min:0',
    ]);

    $expense->update($validated);

    return back()->with('success', 'Pengeluaran riil berhasil diperbarui.');
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(SppdRequest $sppd, SppdActualExpense $expense): RedirectResponse
  {
    abort_unless(auth()->user()->hasAnyRole(['admin_opd', 'super_admin']), 403, 'Aksi ini tidak diizinkan.');
    abort_unless(in_array($sppd->status->value, ['approved', 'completed']), 403, 'Aksi ini tidak diizinkan karena pengajuan SPPD belum disetujui sepenuhnya.');

    $expense->delete();

    return back()->with('success', 'Pengeluaran riil berhasil dihapus.');
  }
}

