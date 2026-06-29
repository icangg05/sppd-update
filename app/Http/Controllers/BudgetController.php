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
    $budget->delete();

    return redirect()->route('master.budgets.index')
      ->with('success', 'Data anggaran berhasil dihapus.');
  }
}
