<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesSppdAccess;
use App\Models\SppdAdvanceReceipt;
use App\Models\SppdRequest;
use Illuminate\Http\Request;

class SppdAdvanceReceiptController extends Controller
{
  use AuthorizesSppdAccess;

  /**
   * Create or update advance receipt (max 1 per user per SPPD).
   */
  public function storeOrUpdate(Request $request, SppdRequest $sppd)
  {
    abort_unless(auth()->user()->hasAnyRole(['admin_opd', 'super_admin']), 403, 'Aksi ini tidak diizinkan.');
    $this->authorizeSppdAccess($sppd);
    abort_unless(in_array($sppd->status->value, ['approved', 'completed']), 403, 'Aksi ini tidak diizinkan karena pengajuan SPPD belum disetujui sepenuhnya.');

    if ($request->has('amounts')) {
      $validated = $request->validate([
        'amounts' => 'required|array',
        'amounts.*' => 'nullable|numeric|min:0',
      ]);

      foreach ($validated['amounts'] as $userId => $amount) {
        if ($amount === null || $amount === '') {
          // Delete existing receipt if cleared
          $sppd->advanceReceipts()->where('user_id', $userId)->delete();
          continue;
        }

        $receipt = $sppd->advanceReceipts()
          ->where('user_id', $userId)
          ->first();

        if ($receipt) {
          $receipt->update(['amount' => $amount]);
        } else {
          // Auto-generate receipt number
          $dept = $sppd->user->department;
          $code = $dept?->code ?? 'SPPD';
          $seq = SppdAdvanceReceipt::whereYear('created_at', now()->year)->count() + 1;
          $receiptNumber = sprintf('KP-%s-%s-%04d', $code, now()->year, $seq);

          $sppd->advanceReceipts()->create([
            'user_id'        => $userId,
            'amount'         => $amount,
            'receipt_number' => $receiptNumber,
          ]);
        }
      }

      $message = 'Seluruh data kuitansi panjar berhasil disimpan.';
      if ($request->wantsJson()) {
        // Kirim state kuitansi terbaru agar tombol/nomor bisa muncul tanpa reload.
        $receipts = $sppd->advanceReceipts()->get()->mapWithKeys(fn ($r) => [
          $r->user_id => ['number' => $r->receipt_number, 'hasPanjar' => $r->amount > 0],
        ]);

        return response()->json(['message' => $message, 'receipts' => $receipts]);
      }

      return back()->with('success', $message);
    }

    $validated = $request->validate([
      'user_id' => 'required|exists:users,id',
      'amount'  => 'required|numeric|min:0',
    ]);

    $receipt = $sppd->advanceReceipts()
      ->where('user_id', $validated['user_id'])
      ->first();

    if ($receipt) {
      $receipt->update(['amount' => $validated['amount']]);
      $message = 'Kuitansi panjar berhasil diperbarui.';
    } else {
      // Auto-generate receipt number
      $dept = $sppd->user->department;
      $code = $dept?->code ?? 'SPPD';
      $seq = SppdAdvanceReceipt::whereYear('created_at', now()->year)->count() + 1;
      $receiptNumber = sprintf('KP-%s-%s-%04d', $code, now()->year, $seq);

      $sppd->advanceReceipts()->create([
        'user_id'        => $validated['user_id'],
        'amount'         => $validated['amount'],
        'receipt_number' => $receiptNumber,
      ]);
      $message = 'Kuitansi panjar berhasil disimpan.';
    }

    return back()->with('success', $message);
  }
}
