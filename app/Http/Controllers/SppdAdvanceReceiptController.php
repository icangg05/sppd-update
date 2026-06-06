<?php

namespace App\Http\Controllers;

use App\Models\SppdAdvanceReceipt;
use App\Models\SppdRequest;
use Illuminate\Http\Request;

class SppdAdvanceReceiptController extends Controller
{
  /**
   * Create or update advance receipt (max 1 per user per SPPD).
   */
  public function storeOrUpdate(Request $request, SppdRequest $sppd)
  {
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

      return back()->with('success', 'Seluruh data kuitansi panjar berhasil disimpan.');
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
