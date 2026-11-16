<?php

namespace App\Http\Controllers;

use App\Models\Budget;

class BudgetController extends Controller
{
  public function show(Budget $budget)
  {
    return view('budgets.show', compact('budget'));
  }

  public function destroy(Budget $budget)
  {
    $user = auth()->user();
    abort_unless($user->can('budget.delete'), 403, 'Anda tidak memiliki izin menghapus DPA.');
    abort_unless($user->hasRole('super_admin') || $budget->department_id === $user->department_id, 403, 'DPA ini di luar instansi Anda.');

    $budget->delete();

    return redirect()->route('master.budgets.index')
      ->with('success', 'Data anggaran berhasil dihapus.');
  }
}
