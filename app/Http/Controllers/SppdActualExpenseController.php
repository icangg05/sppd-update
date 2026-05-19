<?php

namespace App\Http\Controllers;

use App\Models\SppdActualExpense;
use App\Models\SppdRequest;
use Illuminate\Http\Request;

class SppdActualExpenseController extends Controller
{
  public function store(Request $request, SppdRequest $sppd)
  {
    $validated = $request->validate([
      'user_id'     => 'required|exists:users,id',
      'description' => 'required|string|max:500',
      'amount'      => 'required|numeric|min:0',
    ]);

    $sppd->actualExpenses()->create($validated);

    return back()->with('success', 'Pengeluaran riil berhasil ditambahkan.');
  }

  public function update(Request $request, SppdRequest $sppd, SppdActualExpense $expense)
  {
    $validated = $request->validate([
      'description' => 'required|string|max:500',
      'amount'      => 'required|numeric|min:0',
    ]);

    $expense->update($validated);

    return back()->with('success', 'Pengeluaran riil berhasil diperbarui.');
  }

  public function destroy(SppdRequest $sppd, SppdActualExpense $expense)
  {
    $expense->delete();

    return back()->with('success', 'Pengeluaran riil berhasil dihapus.');
  }
}
